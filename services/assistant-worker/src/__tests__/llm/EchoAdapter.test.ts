import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { EchoAdapter } from '../../llm/adapters/EchoAdapter';
import type { LLMRequestState } from '../../llm/LLMRequestBuilder';

function makeState(messages: LLMRequestState['messages']): LLMRequestState {
  return {
    model: { id: 'echo', provider: 'anthropic', apiModelId: 'echo', capabilities: {} as any },
    messages,
  };
}

describe('EchoAdapter', () => {
  let adapter: EchoAdapter;

  beforeEach(() => {
    adapter = new EchoAdapter();
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('extracts the last user message text in buildRequest', () => {
    const state = makeState([
      { role: 'user', content: [{ type: 'text', text: 'first' }] },
      { role: 'assistant', content: 'reply' },
      { role: 'user', content: [{ type: 'text', text: 'second' }] },
    ]);
    expect(adapter.buildRequest(state)).toBe('second');
  });

  it('returns empty string when no user message exists', () => {
    const state = makeState([{ role: 'assistant', content: 'reply' }]);
    expect(adapter.buildRequest(state)).toBe('');
  });

  it('call resolves after 1 second with the same text', async () => {
    const promise = adapter.call('hello');
    vi.advanceTimersByTime(1_000);
    const result = await promise;
    expect(result).toBe('hello');
  });

  it('normalizeResponse wraps text in StandardResponse with end_turn', () => {
    const response = adapter.normalizeResponse('hello echo');
    expect(response.content).toBe('hello echo');
    expect(response.stopReason).toBe('end_turn');
    expect(response.toolCalls).toBeUndefined();
  });

  it('normalizeResponse provides fallback when text is empty', () => {
    const response = adapter.normalizeResponse('');
    expect(response.content).toBeTruthy();
  });
});
