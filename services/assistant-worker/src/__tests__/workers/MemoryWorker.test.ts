import { describe, it, expect, vi } from 'vitest';

vi.mock('../../llm/ModelResolver', () => ({
  resolveModel: vi.fn().mockReturnValue({
    id: 'test/model',
    provider: 'anthropic',
    apiModelId: 'test-model',
    capabilities: {
      temperature: true, topP: true, reasoning: false,
      vision: true, streaming: true,
      contextWindow: 200_000, maxOutputTokens: 8_192,
    },
  }),
}));

vi.mock('../../prompts/renderer', () => ({
  renderMemoryPrompt: vi.fn().mockResolvedValue(''),
}));

import { MemoryWorker } from '../../workers/MemoryWorker';
import type { LLMClient } from '../../llm/LLMClient';
import type { LaravelClient } from '../../api/LaravelClient';

function makeLLMClient(): LLMClient {
  const builder: any = { messages: vi.fn(), call: vi.fn().mockResolvedValue({ content: '[]', stopReason: 'end_turn', usage: {} }) };
  builder.messages.mockReturnValue(builder);
  return { for: vi.fn().mockReturnValue(builder), execute: vi.fn() } as unknown as LLMClient;
}

function makeLaravel(messageCount: number): LaravelClient {
  return {
    get: vi.fn().mockResolvedValue({
      data: {
        messages:  Array.from({ length: messageCount }, (_, i) => ({ id: i + 1, content: 'msg', role: 'user', memory_processed: false })),
        memories:  [],
      },
    }),
    post:   vi.fn().mockResolvedValue({}),
    patch:  vi.fn().mockResolvedValue({}),
    delete: vi.fn().mockResolvedValue({}),
  } as unknown as LaravelClient;
}

const userId = 1;
const recentRun = new Date(Date.now() - 5 * 60 * 1_000);    // 5 min ago
const oldRun    = new Date(Date.now() - 35 * 60 * 1_000);   // 35 min ago

describe('MemoryWorker — shouldActivate', () => {
  it('activates when there are 8 or more unprocessed messages', async () => {
    const worker = new MemoryWorker(makeLLMClient(), makeLaravel(8));
    expect(await worker.shouldActivate(userId, recentRun)).toBe(true);
  });

  it('activates when 30 or more minutes have passed since last run (even with few messages)', async () => {
    const worker = new MemoryWorker(makeLLMClient(), makeLaravel(2));
    expect(await worker.shouldActivate(userId, oldRun)).toBe(true);
  });

  it('does not activate with fewer than 8 messages and less than 30 minutes since last run', async () => {
    const worker = new MemoryWorker(makeLLMClient(), makeLaravel(3));
    expect(await worker.shouldActivate(userId, recentRun)).toBe(false);
  });

  it('activates when exactly 8 unprocessed messages exist', async () => {
    const worker = new MemoryWorker(makeLLMClient(), makeLaravel(8));
    expect(await worker.shouldActivate(userId, recentRun)).toBe(true);
  });

  it('activates when exactly 30 minutes have passed', async () => {
    const worker  = new MemoryWorker(makeLLMClient(), makeLaravel(2));
    const exactly = new Date(Date.now() - 30 * 60 * 1_000);
    expect(await worker.shouldActivate(userId, exactly)).toBe(true);
  });
});

describe('MemoryWorker — run', () => {
  it('calls mark-processed after updating memories', async () => {
    const laravel = makeLaravel(8);
    const worker  = new MemoryWorker(makeLLMClient(), laravel);

    await worker.run(userId);

    expect(laravel.post).toHaveBeenCalledWith(
      expect.stringContaining('mark-processed'),
      expect.any(Object),
    );
  });
});
