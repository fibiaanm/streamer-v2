import { describe, it, expect, vi } from 'vitest';
import { AnthropicAdapter } from '../../../llm/adapters/AnthropicAdapter';
import type { ModelDefinition } from '../../../llm/ModelCatalog';
import type { LLMRequestState } from '../../../llm/LLMRequestBuilder';
import type { StandardMessage } from '../../../llm/types';

const model: ModelDefinition = {
  id: 'anthropic/claude-sonnet-4-6',
  provider: 'anthropic',
  apiModelId: 'claude-sonnet-4-6',
  capabilities: {
    temperature: true, topP: true,
    reasoning: true, maxReasoningTokens: 10_000,
    vision: true, streaming: true,
    contextWindow: 200_000, maxOutputTokens: 16_000,
  },
};

const noReasoningModel: ModelDefinition = {
  ...model,
  id: 'anthropic/claude-haiku-4-5',
  apiModelId: 'claude-haiku-4-5-20251001',
  capabilities: { ...model.capabilities, reasoning: false, temperature: false },
};

function state(overrides: Partial<LLMRequestState> = {}): LLMRequestState {
  return { model, messages: [], ...overrides };
}

describe('AnthropicAdapter — buildRequest', () => {
  const adapter = new AnthropicAdapter('test-key');

  it('sets apiModelId and max_tokens', () => {
    const req = adapter.buildRequest(state({ maxTokens: 512 }));
    expect(req.model).toBe(model.apiModelId);
    expect(req.max_tokens).toBe(512);
  });

  it('falls back to capability maxOutputTokens when maxTokens not set', () => {
    const req = adapter.buildRequest(state());
    expect(req.max_tokens).toBe(model.capabilities.maxOutputTokens);
  });

  it('maps a user text message', () => {
    const messages: StandardMessage[] = [
      { role: 'user', content: [{ type: 'text', text: 'hello' }] },
    ];
    const req = adapter.buildRequest(state({ messages }));
    expect(req.messages[0]).toEqual({ role: 'user', content: [{ type: 'text', text: 'hello' }] });
  });

  it('maps a user image message using url source', () => {
    const messages: StandardMessage[] = [
      { role: 'user', content: [{ type: 'image', mimeType: 'image/png', url: 'https://example.com/img.png' }] },
    ];
    const req = adapter.buildRequest(state({ messages }));
    expect(req.messages[0].content[0]).toMatchObject({
      type: 'image',
      source: { type: 'url', url: 'https://example.com/img.png' },
    });
  });

  it('maps an assistant message with text', () => {
    const messages: StandardMessage[] = [
      { role: 'assistant', content: 'Sure, I can help.' },
    ];
    const req = adapter.buildRequest(state({ messages }));
    expect(req.messages[0]).toEqual({ role: 'assistant', content: [{ type: 'text', text: 'Sure, I can help.' }] });
  });

  it('maps a tool_call message as assistant tool_use block', () => {
    const messages: StandardMessage[] = [
      { role: 'tool_call', id: 'tc_1', name: 'get_weather', input: { city: 'Madrid' } },
    ];
    const req = adapter.buildRequest(state({ messages }));
    expect(req.messages[0].role).toBe('assistant');
    expect(req.messages[0].content[0]).toMatchObject({ type: 'tool_use', id: 'tc_1', name: 'get_weather' });
  });

  it('maps a tool_result message as user tool_result block', () => {
    const messages: StandardMessage[] = [
      { role: 'tool_result', toolCallId: 'tc_1', content: '{"temp":22}' },
    ];
    const req = adapter.buildRequest(state({ messages }));
    expect(req.messages[0].role).toBe('user');
    expect(req.messages[0].content[0]).toMatchObject({ type: 'tool_result', tool_use_id: 'tc_1' });
  });

  it('includes temperature when capability allows it', () => {
    const req = adapter.buildRequest(state({ temperature: 0.7 }));
    expect(req.temperature).toBe(0.7);
  });

  it('omits temperature when capability disallows it', () => {
    const req = adapter.buildRequest({ ...state({ temperature: 0.7 }), model: noReasoningModel });
    expect(req.temperature).toBeUndefined();
  });

  it('includes thinking block when reasoning capability is true and reasoning requested', () => {
    const req = adapter.buildRequest(state({ reasoning: true, reasoningBudget: 4_000 }));
    expect(req.thinking).toMatchObject({ type: 'enabled', budget_tokens: 4_000 });
  });

  it('omits thinking block when model does not support reasoning', () => {
    const req = adapter.buildRequest({ ...state({ reasoning: true }), model: noReasoningModel });
    expect(req.thinking).toBeUndefined();
  });

  it('extracts system message from messages and passes it as top-level system param', () => {
    const messages: StandardMessage[] = [
      { role: 'system', content: 'You are helpful.' },
      { role: 'user', content: [{ type: 'text', text: 'hello' }] },
    ];
    const req = adapter.buildRequest(state({ messages }));
    expect(req.system).toBe('You are helpful.');
    expect(req.messages.every((m: any) => m.role !== 'system')).toBe(true);
  });
});

describe('AnthropicAdapter — normalizeResponse', () => {
  const adapter = new AnthropicAdapter('test-key');

  it('normalizes a text end_turn response', () => {
    const raw = {
      content: [{ type: 'text', text: 'Hello there!' }],
      stop_reason: 'end_turn',
      usage: { input_tokens: 10, output_tokens: 5 },
    };
    const result = adapter.normalizeResponse(raw as any);
    expect(result.content).toBe('Hello there!');
    expect(result.stopReason).toBe('end_turn');
    expect(result.usage).toEqual({ inputTokens: 10, outputTokens: 5 });
    expect(result.toolCalls).toBeUndefined();
  });

  it('normalizes a tool_use response', () => {
    const raw = {
      content: [{ type: 'tool_use', id: 'tc_1', name: 'get_weather', input: { city: 'Madrid' } }],
      stop_reason: 'tool_use',
      usage: { input_tokens: 20, output_tokens: 8 },
    };
    const result = adapter.normalizeResponse(raw as any);
    expect(result.content).toBeNull();
    expect(result.stopReason).toBe('tool_use');
    expect(result.toolCalls).toHaveLength(1);
    expect(result.toolCalls![0]).toEqual({ id: 'tc_1', name: 'get_weather', input: { city: 'Madrid' } });
  });
});
