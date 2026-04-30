import { GoogleGenerativeAI } from '@google/generative-ai';
import type { LLMRequestState } from '../LLMRequestBuilder';
import type { StandardResponse, ContentPart } from '../types';

export class GeminiAdapter {
  private readonly genAI: GoogleGenerativeAI;

  constructor(apiKey: string) {
    this.genAI = new GoogleGenerativeAI(apiKey);
  }

  buildRequest(state: LLMRequestState): Record<string, unknown> {
    const { model, messages, maxTokens, temperature, tools } = state;
    const caps = model.capabilities;

    let systemInstruction: Record<string, unknown> | undefined;
    const filtered = messages.filter((m) => {
      if (m.role === 'system') {
        systemInstruction = { parts: [{ text: m.content }] };
        return false;
      }
      return true;
    });

    const contents = filtered.map((m) => {
      if (m.role === 'user') {
        return {
          role: 'user',
          parts: (m.content as ContentPart[]).map((p) => {
            if (p.type === 'text') return { text: p.text };
            return { fileData: { mimeType: p.mimeType, fileUri: p.url } };
          }),
        };
      }

      if (m.role === 'assistant') {
        const text = typeof m.content === 'string' ? m.content : (m.content as ContentPart[]).map((p) => p.type === 'text' ? p.text : '').join('');
        return { role: 'model', parts: [{ text }] };
      }

      if (m.role === 'tool_call') {
        return {
          role: 'model',
          parts: [{ functionCall: { name: m.name, args: m.input } }],
        };
      }

      if (m.role === 'tool_result') {
        return {
          role: 'user',
          parts: [{ functionResponse: { name: m.toolCallId, response: { content: m.content } } }],
        };
      }

      return { role: 'user', parts: [{ text: '' }] };
    });

    const generationConfig: Record<string, unknown> = {
      maxOutputTokens: maxTokens ?? caps.maxOutputTokens,
    };

    if (caps.temperature && temperature !== undefined) generationConfig.temperature = temperature;

    const req: Record<string, unknown> = {
      model: model.apiModelId,
      contents,
      generationConfig,
    };

    if (systemInstruction) req.systemInstruction = systemInstruction;
    if (tools?.length) {
      req.tools = [{
        functionDeclarations: tools.map((t) => ({
          name: t.name,
          description: t.description,
          parameters: t.inputSchema,
        })),
      }];
    }

    return req;
  }

  async call(request: Record<string, unknown>): Promise<unknown> {
    const model = this.genAI.getGenerativeModel({ model: request.model as string });
    return model.generateContent(request as Parameters<typeof model.generateContent>[0]);
  }

  normalizeResponse(raw: unknown): StandardResponse {
    const res = raw as { candidates: Array<{ content: { role: string; parts: Array<{ text?: string; functionCall?: { name: string; args: unknown } }> }; finishReason: string }>; usageMetadata: { promptTokenCount: number; candidatesTokenCount: number } };

    const candidate = res.candidates[0];
    const parts = candidate.content.parts;
    const funcParts = parts.filter((p) => p.functionCall);
    const textPart = parts.find((p) => p.text);

    const hasToolCalls = funcParts.length > 0;

    return {
      content: hasToolCalls ? null : (textPart?.text ?? null),
      stopReason: hasToolCalls ? 'tool_use' : 'end_turn',
      toolCalls: hasToolCalls
        ? funcParts.map((p, i) => ({
            id: `tc_${i}`,
            name: p.functionCall!.name,
            input: p.functionCall!.args as Record<string, unknown>,
          }))
        : undefined,
      usage: { inputTokens: res.usageMetadata.promptTokenCount, outputTokens: res.usageMetadata.candidatesTokenCount },
    };
  }
}
