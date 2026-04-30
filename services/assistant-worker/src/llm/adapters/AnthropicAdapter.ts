import Anthropic from '@anthropic-ai/sdk';
import type { LLMRequestState } from '../LLMRequestBuilder';
import type { StandardResponse, ContentPart } from '../types';

export class AnthropicAdapter {
  private readonly client: Anthropic;

  constructor(apiKey: string) {
    this.client = new Anthropic({ apiKey });
  }

  buildRequest(state: LLMRequestState): Record<string, unknown> {
    const { model, messages, maxTokens, temperature, reasoning, reasoningBudget, tools } = state;
    const caps = model.capabilities;

    let systemText: string | undefined;
    const filtered = messages.filter((m) => {
      if (m.role === 'system') {
        systemText = m.content as string;
        return false;
      }
      return true;
    });

    const mappedMessages = filtered.map((m) => {
      if (m.role === 'user') {
        return {
          role: 'user',
          content: m.content.map((p: ContentPart) => {
            if (p.type === 'text') return { type: 'text', text: p.text };
            return { type: 'image', source: { type: 'url', url: p.url } };
          }),
        };
      }

      if (m.role === 'assistant') {
        const text = typeof m.content === 'string' ? m.content : (m.content as ContentPart[]).map((p) => p.type === 'text' ? p.text : '').join('');
        return { role: 'assistant', content: [{ type: 'text', text }] };
      }

      if (m.role === 'tool_call') {
        return {
          role: 'assistant',
          content: [{ type: 'tool_use', id: m.id, name: m.name, input: m.input }],
        };
      }

      if (m.role === 'tool_result') {
        return {
          role: 'user',
          content: [{ type: 'tool_result', tool_use_id: m.toolCallId, content: m.content }],
        };
      }

      return m;
    });

    const req: Record<string, unknown> = {
      model: model.apiModelId,
      max_tokens: maxTokens ?? caps.maxOutputTokens,
      messages: mappedMessages,
    };

    if (systemText !== undefined) req.system = systemText;
    if (caps.temperature && temperature !== undefined) req.temperature = temperature;
    if (caps.reasoning && reasoning) {
      req.thinking = { type: 'enabled', budget_tokens: reasoningBudget ?? caps.maxReasoningTokens };
    }
    if (tools?.length) {
      req.tools = tools.map((t) => ({ name: t.name, description: t.description, input_schema: t.inputSchema }));
    }

    return req;
  }

  async call(request: Record<string, unknown>): Promise<unknown> {
    return this.client.messages.create(request as Parameters<typeof this.client.messages.create>[0]);
  }

  normalizeResponse(raw: unknown): StandardResponse {
    const res = raw as { content: Array<{ type: string; text?: string; id?: string; name?: string; input?: unknown }>; stop_reason: string; usage: { input_tokens: number; output_tokens: number } };

    const toolBlocks = res.content.filter((b) => b.type === 'tool_use');
    const textBlock = res.content.find((b) => b.type === 'text');

    const isToolUse = toolBlocks.length > 0;

    return {
      content: isToolUse ? null : (textBlock?.text ?? null),
      stopReason: isToolUse ? 'tool_use' : 'end_turn',
      toolCalls: isToolUse
        ? toolBlocks.map((b) => ({ id: b.id!, name: b.name!, input: b.input as Record<string, unknown> }))
        : undefined,
      usage: { inputTokens: res.usage.input_tokens, outputTokens: res.usage.output_tokens },
    };
  }
}
