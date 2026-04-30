import OpenAI from 'openai';
import type { LLMRequestState } from '../LLMRequestBuilder';
import type { StandardResponse, ContentPart } from '../types';

export class OpenAIAdapter {
  private readonly client: OpenAI;

  constructor(apiKey: string, baseURL?: string) {
    this.client = new OpenAI({ apiKey, ...(baseURL ? { baseURL } : {}) });
  }

  buildRequest(state: LLMRequestState): Record<string, unknown> {
    const { model, messages, maxTokens, temperature, reasoning, tools } = state;
    const caps = model.capabilities;

    const mappedMessages = messages.map((m) => {
      if (m.role === 'system') {
        return { role: 'system', content: m.content };
      }

      if (m.role === 'user') {
        return {
          role: 'user',
          content: (m.content as ContentPart[]).map((p) => {
            if (p.type === 'text') return { type: 'text', text: p.text };
            return { type: 'image_url', image_url: { url: p.url } };
          }),
        };
      }

      if (m.role === 'assistant') {
        return { role: 'assistant', content: typeof m.content === 'string' ? m.content : '' };
      }

      if (m.role === 'tool_call') {
        return {
          role: 'assistant',
          content: null,
          tool_calls: [{
            id: m.id,
            type: 'function',
            function: { name: m.name, arguments: JSON.stringify(m.input) },
          }],
        };
      }

      if (m.role === 'tool_result') {
        return { role: 'tool', tool_call_id: m.toolCallId, content: m.content };
      }

      return m;
    });

    const req: Record<string, unknown> = {
      model: model.apiModelId,
      max_tokens: maxTokens ?? caps.maxOutputTokens,
      messages: mappedMessages,
    };

    if (caps.temperature && temperature !== undefined) req.temperature = temperature;
    if (caps.reasoning && reasoning) req.reasoning_effort = 'medium';
    if (tools?.length) {
      req.tools = tools.map((t) => ({
        type: 'function',
        function: { name: t.name, description: t.description, parameters: t.inputSchema },
      }));
    }

    return req;
  }

  async call(request: Record<string, unknown>): Promise<unknown> {
    return this.client.chat.completions.create(request as Parameters<typeof this.client.chat.completions.create>[0]);
  }

  normalizeResponse(raw: unknown): StandardResponse {
    const res = raw as { choices: Array<{ message: { content: string | null; tool_calls?: Array<{ id: string; function: { name: string; arguments: string } }> }; finish_reason: string }>; usage: { prompt_tokens: number; completion_tokens: number } };

    const choice = res.choices[0];
    const hasToolCalls = !!choice.message.tool_calls?.length;

    return {
      content: hasToolCalls ? null : (choice.message.content ?? null),
      stopReason: hasToolCalls ? 'tool_use' : 'end_turn',
      toolCalls: hasToolCalls
        ? choice.message.tool_calls!.map((tc) => ({
            id: tc.id,
            name: tc.function.name,
            input: JSON.parse(tc.function.arguments),
          }))
        : undefined,
      usage: { inputTokens: res.usage.prompt_tokens, outputTokens: res.usage.completion_tokens },
    };
  }
}
