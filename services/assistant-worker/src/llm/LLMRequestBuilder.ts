import type { ModelDefinition } from './ModelCatalog';
import type { StandardMessage, StandardTool, StandardResponse } from './types';
import type { LLMClient } from './LLMClient';

export interface LLMRequestState {
  model: ModelDefinition;
  messages: StandardMessage[];
  temperature?: number;
  topP?: number;
  maxTokens?: number;
  tools?: StandardTool[];
  reasoning?: boolean;
  reasoningBudget?: number;
}

export class LLMRequestBuilder {
  private state: LLMRequestState;

  constructor(
    model: ModelDefinition,
    private readonly client: LLMClient,
  ) {
    this.state = { model, messages: [] };
  }

  messages(msgs: StandardMessage[]): this {
    this.state.messages = msgs;
    return this;
  }

  temperature(val: number): this {
    this.state.temperature = val;
    return this;
  }

  topP(val: number): this {
    this.state.topP = val;
    return this;
  }

  maxTokens(val: number): this {
    this.state.maxTokens = val;
    return this;
  }

  tools(tools: StandardTool[]): this {
    this.state.tools = tools;
    return this;
  }

  thinking(budget?: number): this {
    this.state.reasoning = true;
    if (budget !== undefined) {
      this.state.reasoningBudget = budget;
    }
    return this;
  }

  build(): LLMRequestState {
    return this.state;
  }

  call(): Promise<StandardResponse> {
    return this.client.execute(this);
  }
}
