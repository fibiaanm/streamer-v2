import { MODEL_CATALOG, type ModelDefinition } from './ModelCatalog';

type PlanTier = 'free' | 'paid';
type ModelContext = 'main' | 'memory';

const ECHO_STUB: ModelDefinition = {
  id: 'echo',
  provider: 'anthropic',
  apiModelId: 'echo',
  capabilities: {
    temperature: false, topP: false, reasoning: false,
    vision: false, streaming: false,
    contextWindow: 200_000, maxOutputTokens: 4_096,
  },
};

export function resolveModel(planTier: PlanTier, context: ModelContext): ModelDefinition {
  if (process.env.LLM_ECHO_MODE === 'true') return ECHO_STUB;

  const nano = MODEL_CATALOG.find((m) => m.id === 'openai/gpt-5.4-nano');
  if (nano) return nano;

  // Fallback hierarchy once more models are active
  if (context === 'memory' || planTier === 'free') {
    const model = MODEL_CATALOG.find((m) => /haiku/i.test(m.id));
    if (model) return model;
  }

  if (planTier === 'paid') {
    const model = MODEL_CATALOG.find((m) => /sonnet/i.test(m.id));
    if (model) return model;
  }

  const fallback = MODEL_CATALOG[0];
  if (fallback) return fallback;

  throw new Error('No models registered in MODEL_CATALOG. Add at least one model to proceed.');
}
