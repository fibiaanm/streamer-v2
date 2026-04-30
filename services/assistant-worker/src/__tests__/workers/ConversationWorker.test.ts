import { describe, it, expect, vi, beforeEach } from 'vitest';

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
  renderSystemPrompt: vi.fn().mockResolvedValue(''),
}));

import { ConversationWorker } from '../../workers/ConversationWorker';
import type { LLMClient } from '../../llm/LLMClient';
import type { LaravelClient } from '../../api/LaravelClient';
import type { StandardResponse } from '../../llm/types';

// ── Factories ─────────────────────────────────────────────────────────────────

function makeEndTurnResponse(): StandardResponse {
  return { content: 'Done.', toolCalls: undefined, stopReason: 'end_turn', usage: { inputTokens: 10, outputTokens: 5 } };
}

function makeToolUseResponse(): StandardResponse {
  return {
    content: null,
    toolCalls: [{ id: 'tc_1', name: 'get_weather', input: { city: 'Madrid' } }],
    stopReason: 'tool_use',
    usage: { inputTokens: 10, outputTokens: 5 },
  };
}

function makeLaravelClient(overrides: Partial<LaravelClient> = {}): LaravelClient {
  return {
    get:    vi.fn().mockResolvedValue({ data: { user: { name: 'Ana', timezone: 'UTC', defaultCurrency: 'USD', planTier: 'free' }, messages: [], memories: [] } }),
    post:   vi.fn().mockResolvedValue({}),
    patch:  vi.fn().mockResolvedValue({}),
    delete: vi.fn().mockResolvedValue({}),
    ...overrides,
  } as unknown as LaravelClient;
}

function makeBuilderMock(response: StandardResponse) {
  const builder: any = { messages: vi.fn(), tools: vi.fn(), call: vi.fn().mockResolvedValue(response) };
  builder.messages.mockReturnValue(builder);
  builder.tools.mockReturnValue(builder);
  return builder;
}

function makeLLMClient(response: StandardResponse): LLMClient {
  return { for: vi.fn().mockReturnValue(makeBuilderMock(response)), execute: vi.fn() } as unknown as LLMClient;
}

const baseJob = { type: 'process_message' as const, conversation_id: 1, message_id: 1, user_id: 1 };

// ── Tests ─────────────────────────────────────────────────────────────────────

describe('ConversationWorker', () => {
  it('sends typing indicator to Laravel before calling the LLM', async () => {
    const laravel = makeLaravelClient();
    const worker  = new ConversationWorker(makeLLMClient(makeEndTurnResponse()), laravel);

    await worker.process(baseJob);

    const postCalls = (laravel.post as any).mock.calls;
    const typingCall = postCalls.find((c: any[]) => (c[0] as string).includes('/typing'));
    expect(typingCall).toBeDefined();
  });

  it('saves assistant message to Laravel on end_turn (PHP emits MessageReceived)', async () => {
    const laravel = makeLaravelClient();
    const worker  = new ConversationWorker(makeLLMClient(makeEndTurnResponse()), laravel);

    await worker.process(baseJob);

    expect(laravel.post).toHaveBeenCalledWith(
      expect.stringContaining('messages'),
      expect.objectContaining({ role: 'assistant', content: 'Done.' }),
    );
  });

  it('saves error message to Laravel after 10 tool_use iterations', async () => {
    const llm    = makeLLMClient(makeToolUseResponse());
    const laravel = makeLaravelClient({ post: vi.fn().mockResolvedValue({}) });
    const worker  = new ConversationWorker(llm, laravel);

    await worker.process(baseJob);

    const postCalls = (laravel.post as any).mock.calls;
    const errorCall = postCalls.find((c: any[]) => c[1]?.role === 'assistant' && c[1]?.content?.includes('error'));
    expect(errorCall).toBeDefined();
  });

  it('truncates context messages when estimated tokens exceed 6000', async () => {
    const longContent = 'x'.repeat(6_000);
    const manyMessages = Array.from({ length: 5 }, (_, i) => ({
      id: i + 1, role: i % 2 === 0 ? 'user' : 'assistant', content: longContent,
    }));

    const laravel = makeLaravelClient({
      get: vi.fn().mockResolvedValue({
        data: {
          user: { name: 'Ana', timezone: 'UTC', defaultCurrency: 'USD', planTier: 'free' },
          messages: manyMessages,
          memories: [],
        },
      }),
    });

    const builderMock = makeBuilderMock(makeEndTurnResponse());
    const llm: LLMClient = { for: vi.fn().mockReturnValue(builderMock), execute: vi.fn() } as unknown as LLMClient;

    const worker = new ConversationWorker(llm, laravel);
    await worker.process(baseJob);

    const passedMessages = builderMock.messages.mock.calls[0][0];
    const { estimateTokens } = await import('../../workers/utils');
    expect(estimateTokens(passedMessages)).toBeLessThanOrEqual(6_000);
  });
});
