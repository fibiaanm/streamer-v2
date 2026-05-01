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

  // Advance to the next user message so tool_call/tool_result pairs are never orphaned.
  // The API requires every tool message to follow its corresponding tool_calls assistant turn.
  while (start < messages.length && messages[start].role !== 'user') {
    start++;
  }

  return messages.slice(start);
}
