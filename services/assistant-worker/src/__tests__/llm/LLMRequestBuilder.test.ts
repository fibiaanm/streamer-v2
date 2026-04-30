import { describe, it, expect, vi } from 'vitest';
import { LLMRequestBuilder } from '../../llm/LLMRequestBuilder';
import type { LLMClient } from '../../llm/LLMClient';
import type { ModelDefinition } from '../../llm/ModelCatalog';
import type { StandardMessage } from '../../llm/types';

const model: ModelDefinition = {
  id: 'test/model',
  provider: 'anthropic',
  apiModelId: 'test-model',
  capabilities: {
    temperature: true, topP: true,
    reasoning: true, maxReasoningTokens: 5_000,
    vision: true, streaming: true,
    contextWindow: 200_000, maxOutputTokens: 8_192,
  },
};

const msg: StandardMessage = { role: 'user', content: [{ type: 'text', text: 'hello' }] };

function makeClient(): LLMClient {
  return { for: vi.fn(), execute: vi.fn() } as unknown as LLMClient;
}

describe('LLMRequestBuilder', () => {
  it('stores model', () => {
    const b = new LLMRequestBuilder(model, makeClient());
    expect(b.build().model).toBe(model);
  });

  it('stores messages', () => {
    const messages = [msg];
    const b = new LLMRequestBuilder(model, makeClient()).messages(messages);
    expect(b.build().messages).toBe(messages);
  });

  it('stores temperature', () => {
    const b = new LLMRequestBuilder(model, makeClient()).messages([]).temperature(0.7);
    expect(b.build().temperature).toBe(0.7);
  });

  it('stores topP', () => {
    const b = new LLMRequestBuilder(model, makeClient()).messages([]).topP(0.9);
    expect(b.build().topP).toBe(0.9);
  });

  it('stores maxTokens', () => {
    const b = new LLMRequestBuilder(model, makeClient()).messages([]).maxTokens(1_000);
    expect(b.build().maxTokens).toBe(1_000);
  });

  it('stores tools', () => {
    const tools = [{ name: 'get_weather', description: 'Get weather', inputSchema: {} }];
    const b = new LLMRequestBuilder(model, makeClient()).messages([]).tools(tools);
    expect(b.build().tools).toBe(tools);
  });

  it('.thinking() sets reasoning=true and optional budget', () => {
    const withBudget = new LLMRequestBuilder(model, makeClient()).messages([]).thinking(8_000).build();
    expect(withBudget.reasoning).toBe(true);
    expect(withBudget.reasoningBudget).toBe(8_000);
  });

  it('.thinking() without budget leaves reasoningBudget undefined', () => {
    const b = new LLMRequestBuilder(model, makeClient()).messages([]).thinking().build();
    expect(b.reasoning).toBe(true);
    expect(b.reasoningBudget).toBeUndefined();
  });

  it('each setter returns this for fluent chaining', () => {
    const b = new LLMRequestBuilder(model, makeClient());
    expect(b.messages([])).toBe(b);
    expect(b.temperature(0.5)).toBe(b);
    expect(b.topP(0.9)).toBe(b);
    expect(b.maxTokens(500)).toBe(b);
    expect(b.tools([])).toBe(b);
    expect(b.thinking()).toBe(b);
  });

  it('.call() delegates to client.execute and returns its result', async () => {
    const expected = { content: 'hi', toolCalls: undefined, usage: { inputTokens: 5, outputTokens: 10 }, stopReason: 'end_turn' as const };
    const client = { execute: vi.fn().mockResolvedValue(expected) } as unknown as LLMClient;

    const builder = new LLMRequestBuilder(model, client).messages([msg]);
    const result = await builder.call();

    expect(client.execute).toHaveBeenCalledWith(builder);
    expect(result).toBe(expected);
  });
});
