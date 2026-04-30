export type ContentPart =
  | { type: 'text'; text: string }
  | { type: 'image'; mimeType: string; url: string };

export type StandardMessage =
  | { role: 'system'; content: string }
  | { role: 'user'; content: ContentPart[] }
  | { role: 'assistant'; content: string | ContentPart[] }
  | { role: 'tool_call'; id: string; name: string; input: Record<string, unknown> }
  | { role: 'tool_result'; toolCallId: string; content: string };

export interface ToolCall {
  id: string;
  name: string;
  input: Record<string, unknown>;
}

export interface StandardTool {
  name: string;
  description: string;
  inputSchema: Record<string, unknown>;
}

export interface StandardResponse {
  content: string | null;
  toolCalls?: ToolCall[];
  stopReason: 'end_turn' | 'tool_use';
  usage: { inputTokens: number; outputTokens: number };
}
