import { describe, it, expect } from 'vitest';
import { resolveModel } from '../../llm/ModelResolver';

// These tests will pass once models are registered in MODEL_CATALOG.
// ModelResolver uses the catalog to look up models by role.

describe('ModelResolver', () => {
  it('returns a Haiku-tier model for free plan + main context', () => {
    const model = resolveModel('free', 'main');
    expect(model.id).toMatch(/haiku/i);
  });

  it('returns a Sonnet-tier model for paid plan + main context', () => {
    const model = resolveModel('paid', 'main');
    expect(model.id).toMatch(/sonnet/i);
  });

  it('returns a Haiku-tier model for memory context regardless of plan tier', () => {
    expect(resolveModel('free', 'memory').id).toMatch(/haiku/i);
    expect(resolveModel('paid', 'memory').id).toMatch(/haiku/i);
  });
});
