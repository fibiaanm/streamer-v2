/**
 * Master catalog of all LLM models available in the system.
 *
 * HOW TO ADD A MODEL
 * ------------------
 * 1. Pick a unique `id` using the convention `<provider>/<family>-<version>`,
 *    e.g. 'anthropic/claude-sonnet-4-6' or 'openai/gpt-4o'.
 * 2. Set `apiModelId` to the exact string the provider API expects.
 * 3. Only set `endpoint` if this model uses a non-default base URL
 *    (currently only needed for Grok, which reuses the OpenAI SDK).
 * 4. Fill `capabilities` accurately — adapters rely on these flags to decide
 *    which parameters to include in each API call.
 * 5. Reference the model from ModelResolver using its `id`.
 */

// ── Provider ──────────────────────────────────────────────────────────────────

/**
 * Supported LLM providers.
 * 'grok' reuses OpenAIAdapter with a custom `endpoint`.
 */
export type Provider = 'anthropic' | 'openai' | 'gemini' | 'grok';

// ── Capabilities ──────────────────────────────────────────────────────────────

export interface ModelCapabilities {
  /**
   * Accepts a `temperature` parameter.
   * Range is 0–1 for most providers; 0–2 for OpenAI.
   * Set false for o-series (OpenAI reasoning models) — they ignore it.
   */
  temperature: boolean;

  /**
   * Accepts a `top_p` / `topP` parameter.
   * Usually true for the same models that accept temperature.
   */
  topP: boolean;

  /**
   * Supports extended chain-of-thought / reasoning tokens before the final answer.
   * - Anthropic: `thinking` block with `budget_tokens`
   * - OpenAI: `reasoning_effort` ('low' | 'medium' | 'high') on o-series models
   * When true, set `maxReasoningTokens` to the recommended or maximum budget.
   */
  reasoning: boolean;

  /**
   * Default token budget for reasoning when `reasoning` is true.
   * Only relevant if reasoning === true.
   */
  maxReasoningTokens?: number;

  /**
   * Use `max_completion_tokens` instead of `max_tokens` in the API request.
   * Required for OpenAI models from gpt-4o onwards (gpt-5-nano, o-series, etc.).
   */
  maxCompletionTokens?: boolean;

  /**
   * Accepts image inputs (base64 or URL) alongside text messages.
   */
  vision: boolean;

  /**
   * Supports streaming responses (server-sent events / chunks).
   */
  streaming: boolean;

  /**
   * Approximate context window in tokens (input + output combined, or input-only
   * depending on provider documentation — note it in a comment on the entry).
   */
  contextWindow: number;

  /**
   * Maximum tokens the model can output in a single response.
   * Used to cap `max_tokens` in API calls.
   */
  maxOutputTokens: number;
}

// ── Model definition ──────────────────────────────────────────────────────────

export interface ModelDefinition {
  /** Internal identifier. Convention: '<provider>/<family>-<version>' */
  id: string;

  /** Provider that hosts this model */
  provider: Provider;

  /**
   * Exact model ID string sent to the provider API.
   * Check the provider's model list for the versioned ID (prefer pinned versions
   * over aliases like 'claude-sonnet-latest' to avoid unexpected behavior changes).
   */
  apiModelId: string;

  /**
   * Base URL for the provider's API.
   * Omit to use the SDK default (Anthropic, OpenAI, Gemini all have their own).
   * Required for Grok: 'https://api.x.ai/v1'
   */
  endpoint?: string;

  capabilities: ModelCapabilities;
}

// ── Catalog ───────────────────────────────────────────────────────────────────

/**
 * Add models here. The list is intentionally empty — populate as providers
 * are onboarded and API keys are configured in the environment.
 */
export const MODEL_CATALOG: ModelDefinition[] = [

  // ── Anthropic ──────────────────────────────────────────────────────────────

  {
    id: 'anthropic/claude-haiku-4-5',
    provider: 'anthropic',
    apiModelId: 'claude-haiku-4-5-20251001',
    capabilities: {
      temperature: true, topP: true,
      reasoning: false,
      vision: true, streaming: true,
      contextWindow: 200_000, maxOutputTokens: 8_192,
    },
  },
  // {
  //   id: 'anthropic/claude-sonnet-4-6',
  //   provider: 'anthropic',
  //   apiModelId: 'claude-sonnet-4-6',
  //   capabilities: {
  //     temperature: true, topP: true,
  //     reasoning: true, maxReasoningTokens: 10_000,
  //     vision: true, streaming: true,
  //     contextWindow: 200_000, maxOutputTokens: 16_000,
  //   },
  // },

  // ── OpenAI ─────────────────────────────────────────────────────────────────

  {
    id: 'openai/gpt-5.4-nano',
    provider: 'openai',
    apiModelId: 'gpt-5.4-nano-2026-03-17',
    capabilities: {
      temperature: true, topP: true,
      reasoning: false,
      vision: true, streaming: true,
      contextWindow: 128_000, maxOutputTokens: 16_384,
      maxCompletionTokens: true,
    },
  },
  {
    id: 'openai/gpt-5-nano',
    provider: 'openai',
    apiModelId: 'gpt-5-nano-2025-08-07',
    capabilities: {
      temperature: true, topP: true,
      reasoning: false,
      vision: true, streaming: true,
      contextWindow: 128_000, maxOutputTokens: 16_384,
      maxCompletionTokens: true,
    },
  },
  // {
  //   id: 'openai/gpt-4o',
  //   provider: 'openai',
  //   apiModelId: 'gpt-4o-2024-11-20',
  //   capabilities: {
  //     temperature: true, topP: true,
  //     reasoning: false,
  //     vision: true, streaming: true,
  //     contextWindow: 128_000, maxOutputTokens: 16_384,
  //   },
  // },
  // {
  //   id: 'openai/o3-mini',
  //   provider: 'openai',
  //   apiModelId: 'o3-mini',
  //   capabilities: {
  //     temperature: false, topP: false,
  //     reasoning: true, maxReasoningTokens: 100_000,
  //     vision: false, streaming: true,
  //     contextWindow: 200_000, maxOutputTokens: 100_000,
  //   },
  // },

  // ── Grok (xAI) — reuses OpenAIAdapter with custom endpoint ─────────────────
  //
  // {
  //   id: 'grok/grok-3',
  //   provider: 'grok',
  //   apiModelId: 'grok-3',
  //   endpoint: 'https://api.x.ai/v1',
  //   capabilities: {
  //     temperature: true, topP: true,
  //     reasoning: false,
  //     vision: true, streaming: true,
  //     contextWindow: 131_072, maxOutputTokens: 131_072,
  //   },
  // },

  // ── Gemini ─────────────────────────────────────────────────────────────────
  //
  // {
  //   id: 'gemini/gemini-2.0-flash',
  //   provider: 'gemini',
  //   apiModelId: 'gemini-2.0-flash',
  //   capabilities: {
  //     temperature: true, topP: true,
  //     reasoning: false,
  //     vision: true, streaming: true,
  //     contextWindow: 1_048_576, maxOutputTokens: 8_192,
  //   },
  // },

];

// ── Lookup helper ─────────────────────────────────────────────────────────────

/**
 * Retrieve a model definition by internal id.
 * Throws if the model is not registered — callers should only request
 * models that are known to be in the catalog.
 */
export function getModel(id: string): ModelDefinition {
  const model = MODEL_CATALOG.find((m) => m.id === id);
  if (!model) {
    throw new Error(`Model '${id}' is not registered in MODEL_CATALOG`);
  }
  return model;
}
