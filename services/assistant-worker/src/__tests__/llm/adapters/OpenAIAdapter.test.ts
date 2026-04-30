import { describe, it, expect } from 'vitest';
import { OpenAIAdapter } from '../../../llm/adapters/OpenAIAdapter';
import type { ModelDefinition } from '../../../llm/ModelCatalog';
import type { LLMRequestState } from '../../../llm/LLMRequestBuilder';
import type { StandardMessage } from '../../../llm/types';

const model: ModelDefinition = {
  id: 'openai/gpt-4o',
  provider: 'openai',
  apiModelId: 'gpt-4o-2024-11-20',
  capabilities: {
    temperature: true, topP: true,
    reasoning: false,
    vision: true, streaming: true,
    contextWindow: 128_000, maxOutputTokens: 16_384,
  },
};

const reasoningModel: ModelDefinition = {
  ...model,
  id: 'openai/o3-mini',
  apiModelId: 'o3-mini',
  capabilities: { ...model.capabilities, temperature: false, topP: false, reasoning: true },
};

function state(overrides: Partial<LLMRequestState> = {}): LLMRequestState {
  return { model, messages: [], ...overrides };
}

describe('OpenAIAdapter — buildRequest', () => {
  const adapter = new OpenAIAdapter('test-key');

  it('sets model and max_tokens', () => {
    const req = adapter.buildRequest(state({ maxTokens: 512 }));
    expect(req.model).toBe(model.apiModelId);
    expect(req.max_tokens).toBe(512);
  });

  it('maps a user text message', () => {
    const messages: StandardMessage[] = [
      { role: 'user', content: [{ type: 'text', text: 'hello' }] },
    ];
    const req = adapter.buildRequest(state({ messages }));
    expect(req.messages[0]).toMatchObject({ role: 'user' });
  });

  it('maps an image message using url type', () => {
    const messages: StandardMessage[] = [
      { role: 'user', content: [{ type: 'image', mimeType: 'image/png', url: 'https://example.com/img.png' }] },
    ];
    const req = adapter.buildRequest(state({ messages }));
    const imagePart = req.messages[0].content.find((p: any) => p.type === 'image_url');
    expect(imagePart).toMatchObject({ type: 'image_url', image_url: { url: 'https://example.com/img.png' } });
  });

  it('maps system message as role:system inline', () => {
    const messages: StandardMessage[] = [
      { role: 'system', content: 'You are helpful.' },
      { role: 'user', content: [{ type: 'text', text: 'hi' }] },
    ];
    const req = adapter.buildRequest(state({ messages }));
    expect(req.messages[0]).toMatchObject({ role: 'system', content: 'You are helpful.' });
  });

  it('maps tool_call as tool_calls array on assistant message', () => {
    const messages: StandardMessage[] = [
      { role: 'tool_call', id: 'tc_1', name: 'get_weather', input: { city: 'Madrid' } },
    ];
    const req = adapter.buildRequest(state({ messages }));
    expect(req.messages[0].role).toBe('assistant');
    expect(req.messages[0].tool_calls[0]).toMatchObject({
      id: 'tc_1', type: 'function',
      function: { name: 'get_weather' },
    });
  });

  it('maps tool_result as role:tool message', () => {
    const messages: StandardMessage[] = [
      { role: 'tool_result', toolCallId: 'tc_1', content: '{"temp":22}' },
    ];
    const req = adapter.buildRequest(state({ messages }));
    expect(req.messages[0]).toMatchObject({ role: 'tool', tool_call_id: 'tc_1', content: '{"temp":22}' });
  });

  it('includes temperature when capability allows it', () => {
    const req = adapter.buildRequest(state({ temperature: 0.7 }));
    expect(req.temperature).toBe(0.7);
  });

  it('omits temperature for reasoning models', () => {
    const req = adapter.buildRequest({ ...state({ temperature: 0.7 }), model: reasoningModel });
    expect(req.temperature).toBeUndefined();
  });

  it('includes reasoning_effort when reasoning capability is true and thinking() called', () => {
    const req = adapter.buildRequest({ ...state({ reasoning: true }), model: reasoningModel });
    expect(req.reasoning_effort).toBeDefined();
  });

  it('omits reasoning_effort for non-reasoning models', () => {
    const req = adapter.buildRequest(state({ reasoning: true }));
    expect(req.reasoning_effort).toBeUndefined();
  });
});

describe('OpenAIAdapter — normalizeResponse', () => {
  const adapter = new OpenAIAdapter('test-key');

  it('normalizes a text stop response', () => {
    const raw = {
      choices: [{ message: { role: 'assistant', content: 'Hello!', tool_calls: undefined }, finish_reason: 'stop' }],
      usage: { prompt_tokens: 10, completion_tokens: 5 },
    };
    const result = adapter.normalizeResponse(raw as any);
    expect(result.content).toBe('Hello!');
    expect(result.stopReason).toBe('end_turn');
    expect(result.usage).toEqual({ inputTokens: 10, outputTokens: 5 });
  });

  it('normalizes a tool_calls response', () => {
    const raw = {
      choices: [{
        message: {
          role: 'assistant', content: null,
          tool_calls: [{ id: 'tc_1', type: 'function', function: { name: 'get_weather', arguments: '{"city":"Madrid"}' } }],
        },
        finish_reason: 'tool_calls',
      }],
      usage: { prompt_tokens: 20, completion_tokens: 8 },
    };
    const result = adapter.normalizeResponse(raw as any);
    expect(result.stopReason).toBe('tool_use');
    expect(result.toolCalls![0]).toEqual({ id: 'tc_1', name: 'get_weather', input: { city: 'Madrid' } });
  });
});
