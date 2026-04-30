import type { LLMRequestState } from '../LLMRequestBuilder';
import type { StandardResponse } from '../types';

export class EchoAdapter {
  buildRequest(state: LLMRequestState): string {
    const lastUser = [...state.messages].reverse().find((m) => m.role === 'user');
    if (!lastUser || lastUser.role !== 'user') return '';

    const { content } = lastUser;
    if (Array.isArray(content)) {
      const textPart = content.find((p) => p.type === 'text');
      return textPart?.text ?? '';
    }
    return typeof content === 'string' ? content : '';
  }

  async call(text: string): Promise<string> {
    await new Promise((resolve) => setTimeout(resolve, 1_000));
    return text;
  }

  normalizeResponse(raw: string): StandardResponse {
    return {
      content:   raw || '(echo: no user message found)',
      toolCalls: undefined,
      stopReason: 'end_turn',
      usage: { inputTokens: 0, outputTokens: 0 },
    };
  }
}
