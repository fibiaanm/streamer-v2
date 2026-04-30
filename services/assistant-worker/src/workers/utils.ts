import type { StandardMessage } from '../llm/types';

export function estimateTokens(messages: StandardMessage[]): number {
  return Math.ceil(JSON.stringify(messages).length / 4);
}

export function truncateToTokenLimit(messages: StandardMessage[], limit: number): StandardMessage[] {
  if (estimateTokens(messages) <= limit) return messages;

  let start = 0;
  while (start < messages.length - 1 && estimateTokens(messages.slice(start)) > limit) {
    start++;
  }
  return messages.slice(start);
}
