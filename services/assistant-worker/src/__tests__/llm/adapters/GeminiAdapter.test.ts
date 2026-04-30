import { describe, it, expect } from 'vitest';
import { GeminiAdapter } from '../../../llm/adapters/GeminiAdapter';
import type { ModelDefinition } from '../../../llm/ModelCatalog';
import type { LLMRequestState } from '../../../llm/LLMRequestBuilder';
import type { StandardMessage } from '../../../llm/types';

const model: ModelDefinition = {
  id: 'gemini/gemini-2.0-flash',
  provider: 'gemini',
  apiModelId: 'gemini-2.0-flash',
  capabilities: {
    temperature: true, topP: true,
    reasoning: false,
    vision: true, streaming: true,
    contextWindow: 1_048_576, maxOutputTokens: 8_192,
  },
};

function state(overrides: Partial<LLMRequestState> = {}): LLMRequestState {
  return { model, messages: [], ...overrides };
}

describe('GeminiAdapter — buildRequest', () => {
  const adapter = new GeminiAdapter('test-key');

  it('sets model id and maxOutputTokens', () => {
    const req = adapter.buildRequest(state({ maxTokens: 512 }));
    expect(req.model).toBe(model.apiModelId);
    expect(req.generationConfig.maxOutputTokens).toBe(512);
  });

  it('maps user text message to contents[] with role user', () => {
    const messages: StandardMessage[] = [
      { role: 'user', content: [{ type: 'text', text: 'hello' }] },
    ];
    const req = adapter.buildRequest(state({ messages }));
    expect(req.contents[0]).toMatchObject({ role: 'user', parts: [{ text: 'hello' }] });
  });

  it('maps user image message using fileData with url', () => {
    const messages: StandardMessage[] = [
      { role: 'user', content: [{ type: 'image', mimeType: 'image/png', url: 'https://example.com/img.png' }] },
    ];
    const req = adapter.buildRequest(state({ messages }));
    expect(req.contents[0].parts[0]).toMatchObject({
      fileData: { mimeType: 'image/png', fileUri: 'https://example.com/img.png' },
    });
  });

  it('maps assistant message with role model', () => {
    const messages: StandardMessage[] = [
      { role: 'assistant', content: 'Sure thing.' },
    ];
    const req = adapter.buildRequest(state({ messages }));
    expect(req.contents[0].role).toBe('model');
  });

  it('maps tool_call to functionCall part under model role', () => {
    const messages: StandardMessage[] = [
      { role: 'tool_call', id: 'tc_1', name: 'get_weather', input: { city: 'Madrid' } },
    ];
    const req = adapter.buildRequest(state({ messages }));
    expect(req.contents[0].role).toBe('model');
    expect(req.contents[0].parts[0]).toMatchObject({ functionCall: { name: 'get_weather', args: { city: 'Madrid' } } });
  });

  it('maps tool_result to functionResponse part under user role', () => {
    const messages: StandardMessage[] = [
      { role: 'tool_result', toolCallId: 'tc_1', content: '{"temp":22}' },
    ];
    const req = adapter.buildRequest(state({ messages }));
    expect(req.contents[0].role).toBe('user');
    expect(req.contents[0].parts[0]).toMatchObject({ functionResponse: { name: expect.any(String) } });
  });

  it('extracts system message as systemInstruction, not in contents', () => {
    const messages: StandardMessage[] = [
      { role: 'system', content: 'You are helpful.' },
      { role: 'user', content: [{ type: 'text', text: 'hi' }] },
    ];
    const req = adapter.buildRequest(state({ messages }));
    expect(req.systemInstruction).toMatchObject({ parts: [{ text: 'You are helpful.' }] });
    expect(req.contents.every((c: any) => c.role !== 'system')).toBe(true);
  });

  it('includes temperature when capability allows it', () => {
    const req = adapter.buildRequest(state({ temperature: 0.7 }));
    expect(req.generationConfig.temperature).toBe(0.7);
  });
});

describe('GeminiAdapter — normalizeResponse', () => {
  const adapter = new GeminiAdapter('test-key');

  it('normalizes a text STOP response', () => {
    const raw = {
      candidates: [{
        content: { role: 'model', parts: [{ text: 'Hello!' }] },
        finishReason: 'STOP',
      }],
      usageMetadata: { promptTokenCount: 10, candidatesTokenCount: 5 },
    };
    const result = adapter.normalizeResponse(raw as any);
    expect(result.content).toBe('Hello!');
    expect(result.stopReason).toBe('end_turn');
    expect(result.usage).toEqual({ inputTokens: 10, outputTokens: 5 });
  });

  it('normalizes a functionCall response', () => {
    const raw = {
      candidates: [{
        content: {
          role: 'model',
          parts: [{ functionCall: { name: 'get_weather', args: { city: 'Madrid' } } }],
        },
        finishReason: 'OTHER',
      }],
      usageMetadata: { promptTokenCount: 20, candidatesTokenCount: 8 },
    };
    const result = adapter.normalizeResponse(raw as any);
    expect(result.stopReason).toBe('tool_use');
    expect(result.toolCalls![0]).toMatchObject({ name: 'get_weather', input: { city: 'Madrid' } });
  });
});
