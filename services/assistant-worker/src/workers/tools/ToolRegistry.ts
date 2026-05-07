import type { StandardTool } from '../../llm/types';
import { EVENT_TOOLS } from './eventTools';
import { LIST_TOOLS } from './listTools';
import { OPTION_TOOLS } from './optionTools';

type PlanTier = 'free' | 'paid';

const TOOLS_BY_TIER: Record<PlanTier, StandardTool[]> = {
  free: [...EVENT_TOOLS, ...LIST_TOOLS, ...OPTION_TOOLS],
  paid: [...EVENT_TOOLS, ...LIST_TOOLS, ...OPTION_TOOLS],
};

export const ToolRegistry = {
  for(planTier: PlanTier): StandardTool[] {
    return TOOLS_BY_TIER[planTier] ?? TOOLS_BY_TIER.free;
  },
};
