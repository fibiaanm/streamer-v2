import type { ModelDefinition, Provider } from './ModelCatalog';
import type { StandardResponse } from './types';
import type { LLMRequestBuilder } from './LLMRequestBuilder';
import { AnthropicAdapter } from './adapters/AnthropicAdapter';
import { OpenAIAdapter } from './adapters/OpenAIAdapter';
import { GeminiAdapter } from './adapters/GeminiAdapter';
import { EchoAdapter } from './adapters/EchoAdapter';

export interface LLMClient {
  for(model: ModelDefinition): LLMRequestBuilder;
  execute(builder: LLMRequestBuilder): Promise<StandardResponse>;
}

interface LLMAdapter {
  buildRequest(state: import('./LLMRequestBuilder').LLMRequestState): unknown;
  call(request: unknown): Promise<unknown>;
  normalizeResponse(raw: unknown): StandardResponse;
}

export class LLMClientImpl implements LLMClient {
  constructor(private readonly keys: Partial<Record<Provider, string>>) {}

  for(model: ModelDefinition): LLMRequestBuilder {
    const { LLMRequestBuilder: Builder } = require('./LLMRequestBuilder');
    return new Builder(model, this);
  }

  async execute(builder: LLMRequestBuilder): Promise<StandardResponse> {
    const state = builder.build();
    const adapter = this.getAdapter(state.model);
    const request = adapter.buildRequest(state);
    const raw = await adapter.call(request);
    return adapter.normalizeResponse(raw);
  }

  private getAdapter(model: ModelDefinition): LLMAdapter {
    if (process.env.LLM_ECHO_MODE === 'true') {
      return new EchoAdapter() as unknown as LLMAdapter;
    }
    switch (model.provider) {
      case 'anthropic':
        return new AnthropicAdapter(this.keys.anthropic ?? '');
      case 'openai':
        return new OpenAIAdapter(this.keys.openai ?? '');
      case 'grok':
        return new OpenAIAdapter(this.keys.grok ?? '', model.endpoint);
      case 'gemini':
        return new GeminiAdapter(this.keys.gemini ?? '');
    }
  }
}
