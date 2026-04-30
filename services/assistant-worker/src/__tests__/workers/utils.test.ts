import { describe, it, expect } from 'vitest';
import { estimateTokens, truncateToTokenLimit } from '../../workers/utils';
import type { StandardMessage } from '../../llm/types';

const short: StandardMessage = { role: 'user', content: [{ type: 'text', text: 'hi' }] };

function longMsg(chars: number): StandardMessage {
  return { role: 'user', content: [{ type: 'text', text: 'x'.repeat(chars) }] };
}

describe('estimateTokens', () => {
  it('returns ceil(total_json_chars / 4)', () => {
    const messages = [short];
    const json = JSON.stringify(messages);
    expect(estimateTokens(messages)).toBe(Math.ceil(json.length / 4));
  });
});

describe('truncateToTokenLimit', () => {
  it('returns the original array when already within the limit', () => {
    const messages = [short];
    expect(truncateToTokenLimit(messages, 6_000)).toBe(messages);
  });

  it('always keeps the last message', () => {
    const latest: StandardMessage = { role: 'user', content: [{ type: 'text', text: 'latest' }] };
    const messages = [longMsg(8_000), longMsg(8_000), longMsg(8_000), latest];

    const result = truncateToTokenLimit(messages, 500);

    expect(result[result.length - 1]).toBe(latest);
  });

  it('result is within the token limit after truncation', () => {
    const messages = [longMsg(6_000), longMsg(6_000), longMsg(6_000), longMsg(6_000), short];
    const limit = 1_000;

    const result = truncateToTokenLimit(messages, limit);

    expect(estimateTokens(result)).toBeLessThanOrEqual(limit);
  });

  it('removes from the front, not from the end', () => {
    const first: StandardMessage  = { role: 'user',      content: [{ type: 'text', text: 'first' }] };
    const second: StandardMessage = { role: 'assistant', content: 'second' };
    const third: StandardMessage  = { role: 'user',      content: [{ type: 'text', text: 'x'.repeat(20_000) }] };
    const last: StandardMessage   = { role: 'user',      content: [{ type: 'text', text: 'last' }] };

    const result = truncateToTokenLimit([first, second, third, last], 400);

    expect(result.includes(first)).toBe(false);
    expect(result[result.length - 1]).toBe(last);
  });
});
