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
  conversation_id: number;
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

export class ConversationWorker {
  constructor(
    private readonly llm: LLMClient,
    private readonly laravel: LaravelClient,
  ) {}

  async process(job: ProcessMessageJob): Promise<void> {
    try {
      await this.run(job);
    } catch (err) {
      report(err, { conversation_id: job.conversation_id });
      try {
        await this.laravel.post(`/api/v1/internal/conversations/${job.conversation_id}/messages`, {
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
    const contextResponse = await this.laravel.get(`/api/v1/internal/context/${job.user_id}`);
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

    await this.laravel.post(`/api/v1/internal/conversations/${job.conversation_id}/typing`, {});

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

      this.recordUsage(job.conversation_id, {
        type:          'text',
        provider:      model.provider,
        model:         model.id,
        input_tokens:  response.usage.inputTokens,
        output_tokens: response.usage.outputTokens,
        iteration:     toolIterations,
      });

      if (response.stopReason === 'end_turn') {
        await this.laravel.post(`/api/v1/internal/conversations/${job.conversation_id}/messages`, {
          role: 'assistant',
          content: response.content,
        });
        return;
      }

      if (toolIterations >= MAX_TOOL_ITERATIONS) {
        await this.laravel.post(`/api/v1/internal/conversations/${job.conversation_id}/messages`, {
          role: 'assistant',
          content: 'I encountered an error while processing your request. Please try again.',
        });
        return;
      }

      for (const toolCall of response.toolCalls ?? []) {
        const result = await executor.execute(toolCall);

        contextMsgs.push({ role: 'tool_call', id: toolCall.id, name: toolCall.name, input: toolCall.input });
        contextMsgs.push({ role: 'tool_result', toolCallId: toolCall.id, content: result });

        await this.laravel.post(`/api/v1/internal/conversations/${job.conversation_id}/messages`, {
          role: 'tool_call',
          content: JSON.stringify({ id: toolCall.id, name: toolCall.name, input: toolCall.input }),
        });
        await this.laravel.post(`/api/v1/internal/conversations/${job.conversation_id}/messages`, {
          role: 'tool_result',
          content: JSON.stringify({ toolCallId: toolCall.id, content: result }),
        });
      }

      builder.messages([systemMsg, ...truncateToTokenLimit(contextMsgs, TOKEN_LIMIT)]);
    }
  }

  private recordUsage(
    conversationId: number,
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
      .post(`/api/v1/internal/conversations/${conversationId}/usage`, payload)
      .catch((err: unknown) => {
        log.warn('token_usage_record_failed', { error: err instanceof Error ? err.message : String(err) });
      });
  }
}
