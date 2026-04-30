import type { LLMClient } from '../llm/LLMClient';
import type { LaravelClient } from '../api/LaravelClient';
import type { StandardMessage } from '../llm/types';
import { resolveModel } from '../llm/ModelResolver';
import { renderSystemPrompt } from '../prompts/renderer';
import { truncateToTokenLimit } from './utils';

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
    const contextResponse = await this.laravel.get(`/api/v1/assistant/internal/context/${job.user_id}`);
    const { user, messages: dbMessages, memories } = (contextResponse as { data: { user: UserContext; messages: DbMessage[]; memories: MemoryEntry[] } }).data;

    const systemPrompt = await renderSystemPrompt(user, memories);
    const systemMsg: StandardMessage = { role: 'system', content: systemPrompt };

    const historyMsgs = (dbMessages as DbMessage[]).map(toStandardMessage);
    const truncatedHistory = truncateToTokenLimit(historyMsgs, TOKEN_LIMIT);
    const contextMsgs = [...truncatedHistory];

    const model = resolveModel(user.planTier, 'main');
    const builder = this.llm.for(model);

    await this.laravel.post(`/api/v1/assistant/internal/conversations/${job.conversation_id}/typing`, {});

    builder.messages([systemMsg, ...truncateToTokenLimit(contextMsgs, TOKEN_LIMIT)]);

    let toolIterations = 0;

    while (true) {
      const response = await builder.call();

      if (response.stopReason === 'end_turn') {
        await this.laravel.post(`/api/v1/assistant/internal/conversations/${job.conversation_id}/messages`, {
          role: 'assistant',
          content: response.content,
        });
        return;
      }

      toolIterations++;

      if (toolIterations >= MAX_TOOL_ITERATIONS) {
        await this.laravel.post(`/api/v1/assistant/internal/conversations/${job.conversation_id}/messages`, {
          role: 'assistant',
          content: 'I encountered an error while processing your request. Please try again.',
        });
        return;
      }

      for (const toolCall of response.toolCalls ?? []) {
        contextMsgs.push({ role: 'tool_call', id: toolCall.id, name: toolCall.name, input: toolCall.input });
        contextMsgs.push({ role: 'tool_result', toolCallId: toolCall.id, content: '{}' });
      }

      builder.messages([systemMsg, ...truncateToTokenLimit(contextMsgs, TOKEN_LIMIT)]);
    }
  }
}
