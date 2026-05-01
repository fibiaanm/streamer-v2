import type { LLMClient } from '../llm/LLMClient';
import type { LaravelClient } from '../api/LaravelClient';
import type { StandardMessage } from '../llm/types';
import { resolveModel } from '../llm/ModelResolver';
import { renderMemoryPrompt } from '../prompts/renderer';
import { log } from '../logger';

interface DbMessage {
  id: string | number;
  role: string;
  content: string;
  memory_processed: boolean;
}

interface MemoryEntry {
  category: string;
  description: string;
  content: string;
}

export class MemoryWorker {
  constructor(
    private readonly llm: LLMClient,
    private readonly laravel: LaravelClient,
  ) {}

  async shouldActivate(userId: number, lastRun: Date): Promise<boolean> {
    const response = await this.laravel.get(`/internal/context/${userId}`);
    const { messages } = (response as { data: { messages: DbMessage[]; memories: MemoryEntry[] } }).data;

    const minutesSinceLastRun = (Date.now() - lastRun.getTime()) / 60_000;
    return messages.length >= 8 || minutesSinceLastRun >= 30;
  }

  async run(userId: number): Promise<void> {
    const response = await this.laravel.get(`/internal/context/${userId}`);
    const { user, messages, memories } = (response as { data: { user: { name: string; timezone: string; defaultCurrency: string }; messages: DbMessage[]; memories: MemoryEntry[] } }).data;

    const model = resolveModel('free', 'memory');
    const builder = this.llm.for(model);

    const systemPrompt = await renderMemoryPrompt(user, messages, memories);
    const allMsgs: StandardMessage[] = [
      { role: 'system', content: systemPrompt },
      { role: 'user', content: [{ type: 'text', text: 'Update the memory banks based on the recent conversation.' }] },
    ];

    const result = await builder.messages(allMsgs).call();

    this.recordUsage(userId, {
      provider:      model.provider,
      model:         model.id,
      input_tokens:  result.usage.inputTokens,
      output_tokens: result.usage.outputTokens,
    });

    let newMemories: Array<{ category: string; description: string; content: string }> = [];
    try {
      newMemories = JSON.parse(result.content ?? '[]');
    } catch {
      newMemories = [];
    }

    for (const memory of newMemories) {
      await this.laravel.patch(`/internal/memories/${userId}/${memory.category}`, {
        description: memory.description,
        content: memory.content,
      });
    }

    const messageIds = messages.map((m) => m.id);
    await this.laravel.post(`/internal/mark-processed`, { message_ids: messageIds });
  }

  private recordUsage(userId: number, payload: { provider: string; model: string; input_tokens: number; output_tokens: number }): void {
    this.laravel
      .post(`/api/v1/internal/users/${userId}/usage`, { type: 'memory', ...payload })
      .catch((err: unknown) => {
        log.warn('MemoryWorker: token_usage_record_failed', { error: err instanceof Error ? err.message : String(err) });
      });
  }
}
