import type { LLMClient } from '../llm/LLMClient';
import type { LaravelClient } from '../api/LaravelClient';
import type { StandardMessage } from '../llm/types';
import { resolveModel } from '../llm/ModelResolver';
import { renderSystemPrompt } from '../prompts/renderer';
import { truncateToTokenLimit } from './utils';
import { ToolExecutor } from './tools/ToolExecutor';
import { ToolRegistry } from './tools/ToolRegistry';
import { log } from '../logger';
import { report } from '../reporter';

const MAX_TOOL_ITERATIONS = 10;
const TOKEN_LIMIT = 6_000;

interface ProcessMessageJob {
  type: 'process_message';
  session_id: number;
  message_id: number;
  user_id: number;
}

interface DbMessage {
  id: string | number;
  role: string;
  content: string;
}

interface UserContext {
  name: string;
  timezone: string;
  defaultCurrency: string;
  planTier: 'free' | 'paid';
}

interface MemoryEntry {
  category: string;
  description: string;
  content: string;
}

function toStandardMessage(m: DbMessage): StandardMessage {
  if (m.role === 'user') {
    return { role: 'user', content: [{ type: 'text', text: m.content }] };
  }
  if (m.role === 'assistant') {
    return { role: 'assistant', content: m.content };
  }
  if (m.role === 'tool_summary') {
    return { role: 'assistant', content: `[Actions: ${m.content}]` };
  }
  if (m.role === 'tool_call') {
    try {
      const parsed = JSON.parse(m.content);
      return { role: 'tool_call', id: parsed.id, name: parsed.name, input: parsed.input };
    } catch {
      return { role: 'tool_call', id: '', name: '', input: {} };
    }
  }
  if (m.role === 'tool_result') {
    try {
      const parsed = JSON.parse(m.content);
      return { role: 'tool_result', toolCallId: parsed.toolCallId, content: parsed.content };
    } catch {
      return { role: 'tool_result', toolCallId: '', content: m.content };
    }
  }
  return { role: 'user', content: [{ type: 'text', text: m.content }] };
}

function summarizeTool(name: string, resultJson: string): string {
  try {
    const data = JSON.parse(resultJson) as Record<string, unknown>;
    switch (name) {
      case 'get_events': {
        const events = (data.data ?? []) as Array<{ id: string; content: string; event_at: string }>;
        if (!Array.isArray(events) || events.length === 0) return 'get_events: no events';
        const list = events.slice(0, 6).map(e => `"${e.content}" ${e.event_at?.slice(0, 10)} (id:${e.id})`).join(', ');
        return `get_events: ${events.length} event(s) [${list}]`;
      }
      case 'create_event': {
        const e = (data.data ?? {}) as { id: string; content: string; event_at: string };
        return `created "${e.content}" at ${e.event_at} (id:${e.id})`;
      }
      case 'update_event': {
        const e = (data.data ?? {}) as { id: string; content: string; event_at: string };
        return `updated event "${e.content}" at ${e.event_at} (id:${e.id})`;
      }
      case 'cancel_event': {
        const e = (data.data ?? {}) as { id: string; status: string };
        return `cancelled event (id:${e.id})`;
      }
      case 'snooze_event': {
        const e = (data.data ?? {}) as { id: string; event_at: string };
        return `snoozed event to ${e.event_at} (id:${e.id})`;
      }
      case 'detach_event_reference': {
        const e = (data.data ?? {}) as { id: string };
        return `detached reference from event (id:${e.id})`;
      }
      default:
        return `${name}: ok`;
    }
  } catch {
    return `${name}: ok`;
  }
}

export class ConversationWorker {
  constructor(
    private readonly llm: LLMClient,
    private readonly laravel: LaravelClient,
  ) {}

  async process(job: ProcessMessageJob): Promise<void> {
    try {
      await this.run(job);
    } catch (err) {
      report(err, { session_id: job.session_id });
      try {
        await this.laravel.post(`/api/v1/internal/sessions/${job.session_id}/messages`, {
          role: 'assistant',
          content: 'Sorry, something went wrong on my end. Please try again.',
        });
      } catch {
        // if this also fails, nothing we can do
      }
      throw err;
    }
  }

  private async run(job: ProcessMessageJob): Promise<void> {
    const contextResponse = await this.laravel.get(`/api/v1/internal/context/${job.session_id}`);
    const { user, messages: dbMessages, memories } = (contextResponse as { data: { user: UserContext; messages: DbMessage[]; memories: MemoryEntry[] } }).data;

    const systemPrompt = await renderSystemPrompt(user, memories);
    const systemMsg: StandardMessage = { role: 'system', content: systemPrompt };

    const historyMsgs = (dbMessages as DbMessage[]).map(toStandardMessage);
    const truncatedHistory = truncateToTokenLimit(historyMsgs, TOKEN_LIMIT);
    const contextMsgs = [...truncatedHistory];

    const model = resolveModel(user.planTier, 'main');
    log.info('llm_model_selected', { model: model.id, provider: model.provider, planTier: user.planTier });
    const builder = this.llm.for(model);
    const executor = new ToolExecutor(this.laravel, job.user_id);

    await this.laravel.post(`/api/v1/internal/sessions/${job.session_id}/typing`, {});

    builder
      .tools(ToolRegistry.for(user.planTier))
      .messages([systemMsg, ...truncateToTokenLimit(contextMsgs, TOKEN_LIMIT)]);

    let toolIterations = 0;

    while (true) {
      const response = await builder.call();

      toolIterations++;

      log.info('llm_response', {
        stopReason:    response.stopReason,
        hasToolCalls:  (response.toolCalls?.length ?? 0) > 0,
        model:         model.id,
        provider:      model.provider,
        input_tokens:  response.usage.inputTokens,
        output_tokens: response.usage.outputTokens,
        iteration:     toolIterations,
      });

      this.recordUsage(job.session_id, {
        type:          'text',
        provider:      model.provider,
        model:         model.id,
        input_tokens:  response.usage.inputTokens,
        output_tokens: response.usage.outputTokens,
        iteration:     toolIterations,
      });

      if (response.stopReason === 'end_turn') {
        await this.laravel.post(`/api/v1/internal/sessions/${job.session_id}/messages`, {
          role: 'assistant',
          content: response.content,
        });
        return;
      }

      if (toolIterations >= MAX_TOOL_ITERATIONS) {
        await this.laravel.post(`/api/v1/internal/sessions/${job.session_id}/messages`, {
          role: 'assistant',
          content: 'I encountered an error while processing your request. Please try again.',
        });
        return;
      }

      const iterationParts: string[] = [];

      for (const toolCall of response.toolCalls ?? []) {
        const result = await executor.execute(toolCall);

        // Virtual tool: send_options ends the turn without looping back to the LLM
        const parsed = JSON.parse(result) as Record<string, unknown>;
        if (parsed.__virtual__ === 'send_options') {
          if (iterationParts.length > 0) {
            await this.laravel.post(`/api/v1/internal/sessions/${job.session_id}/messages`, {
              role:    'tool_summary',
              content: iterationParts.join(' | '),
            });
          }
          await this.laravel.post(`/api/v1/internal/sessions/${job.session_id}/messages`, {
            role:    'assistant',
            content: String(parsed.content ?? ''),
            options: parsed.options,
          });
          return;
        }

        iterationParts.push(summarizeTool(toolCall.name, result));

        contextMsgs.push({ role: 'tool_call', id: toolCall.id, name: toolCall.name, input: toolCall.input });
        contextMsgs.push({ role: 'tool_result', toolCallId: toolCall.id, content: result });

        await this.laravel.post(`/api/v1/internal/sessions/${job.session_id}/messages`, {
          role: 'tool_call',
          content: JSON.stringify({ id: toolCall.id, name: toolCall.name, input: toolCall.input }),
        });
        await this.laravel.post(`/api/v1/internal/sessions/${job.session_id}/messages`, {
          role: 'tool_result',
          content: JSON.stringify({ toolCallId: toolCall.id, content: result }),
        });
      }

      if (iterationParts.length > 0) {
        await this.laravel.post(`/api/v1/internal/sessions/${job.session_id}/messages`, {
          role:    'tool_summary',
          content: iterationParts.join(' | '),
        });
      }

      builder.messages([systemMsg, ...truncateToTokenLimit(contextMsgs, TOKEN_LIMIT)]);
    }
  }

  private recordUsage(
    sessionId: number,
    payload: {
      type: string;
      provider: string;
      model: string;
      input_tokens: number;
      output_tokens: number;
      iteration: number;
    },
  ): void {
    // Fire-and-forget: usage tracking must never break the conversation flow
    this.laravel
      .post(`/api/v1/internal/sessions/${sessionId}/usage`, payload)
      .catch((err: unknown) => {
        log.warn('token_usage_record_failed', { error: err instanceof Error ? err.message : String(err) });
      });
  }
}
