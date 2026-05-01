<template>
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-7">
    <div
      v-for="card in cards"
      :key="card.label"
      class="rounded-2xl border border-white/8 bg-white/3 p-5"
    >
      <p class="text-xs font-semibold uppercase tracking-widest text-white/35 mb-3">{{ card.label }}</p>
      <p class="text-2xl font-bold text-white/90 leading-none mb-1">{{ card.value }}</p>
      <p v-if="card.sub" class="text-xs text-white/35">{{ card.sub }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { UsageSummary } from '@/types/admin'

const props = defineProps<{ summary: UsageSummary | null }>()

const fmt = (n: number) =>
  n >= 1_000_000 ? `${(n / 1_000_000).toFixed(1)}M`
  : n >= 1_000   ? `${(n / 1_000).toFixed(1)}K`
  : String(n)

const avgTokens = computed(() => {
  if (!props.summary || props.summary.total_conversations === 0) return '—'
  return fmt(Math.round(props.summary.total_tokens / props.summary.total_conversations))
})

const cards = computed(() => [
  {
    label: 'Total tokens',
    value: fmt(props.summary?.total_tokens ?? 0),
    sub:   `${fmt(props.summary?.total_input ?? 0)} in · ${fmt(props.summary?.total_output ?? 0)} out`,
  },
  {
    label: 'Conversations',
    value: fmt(props.summary?.total_conversations ?? 0),
    sub:   null,
  },
  {
    label: 'Top model',
    value: props.summary?.top_model ?? '—',
    sub:   null,
  },
  {
    label: 'Avg / conv',
    value: avgTokens.value,
    sub:   null,
  },
])
</script>
