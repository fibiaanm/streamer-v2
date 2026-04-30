import type { LLMClient } from '../llm/LLMClient';
import type { LaravelClient } from '../api/LaravelClient';
import type { StandardMessage } from '../llm/types';
import { resolveModel } from '../llm/ModelResolver';
import { renderMemoryPrompt } from '../prompts/renderer';

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
    const { messages, memories } = (response as { data: { messages: DbMessage[]; memories: MemoryEntry[] } }).data;

    const model = resolveModel('free', 'memory');
    const builder = this.llm.for(model);

    const systemPrompt = await renderMemoryPrompt(messages, memories);
    const allMsgs: StandardMessage[] = [
      { role: 'system', content: systemPrompt },
      { role: 'user', content: [{ type: 'text', text: 'Update the memory banks based on the recent conversation.' }] },
    ];

    const result = await builder.messages(allMsgs).call();

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
}
