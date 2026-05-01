<template>
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="rounded-2xl border border-white/8 bg-white/3 p-5">
      <p class="text-xs font-semibold uppercase tracking-widest text-white/35 mb-3">Total tokens</p>
      <p class="text-2xl font-bold text-white/90 leading-none mb-1">{{ fmt(summary?.total_tokens ?? 0) }}</p>
      <p class="text-xs text-white/35">{{ fmt(summary?.total_input ?? 0) }} in · {{ fmt(summary?.total_output ?? 0) }} out</p>
      <p v-if="memoryShare !== null" class="text-xs text-white/25 mt-0.5">{{ fmt(summary?.memory_tokens ?? 0) }} memory ({{ memoryShare }}%)</p>
    </div>

    <div class="rounded-2xl border border-white/8 bg-white/3 p-5">
      <p class="text-xs font-semibold uppercase tracking-widest text-white/35 mb-3">Conversations</p>
      <p class="text-2xl font-bold text-white/90 leading-none mb-1">{{ fmt(summary?.total_conversations ?? 0) }}</p>
      <p class="text-xs text-white/35">{{ fmt(summary?.total_records ?? 0) }} requests</p>
    </div>

    <div class="rounded-2xl border border-white/8 bg-white/3 p-5">
      <p class="text-xs font-semibold uppercase tracking-widest text-white/35 mb-3">Top model</p>
      <p class="text-lg font-bold text-white/90 leading-snug mb-1">{{ summary?.top_model ?? '—' }}</p>
    </div>

    <div class="rounded-2xl border border-white/8 bg-white/3 p-5">
      <p class="text-xs font-semibold uppercase tracking-widest text-white/35 mb-3">Avg / conv</p>
      <p class="text-2xl font-bold text-white/90 leading-none mb-1">{{ avgTokens }}</p>
      <p class="text-xs text-white/35">tokens per conversation</p>
    </div>
  </div>

  <p class="text-xs text-white/30 mt-3 mb-7">
    <template v-if="summary?.last_run_at">
      Rollup updated {{ timeAgo(summary.last_run_at) }} — data may be up to 30 min behind
    </template>
    <template v-else>Rollup has not run yet</template>
  </p>
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
  if (!props.summary || !props.summary.avg_tokens_per_conv) return '—'
  return fmt(props.summary.avg_tokens_per_conv)
})

const memoryShare = computed(() => {
  const s = props.summary
  if (!s || s.total_tokens === 0 || s.memory_tokens === 0) return null
  return ((s.memory_tokens / s.total_tokens) * 100).toFixed(1)
})

const timeAgo = (d: string) => {
  // Ensure the string is parsed as UTC (backend sends without timezone suffix)
  const utc = d.includes('T') || d.endsWith('Z') ? d : d.replace(' ', 'T') + 'Z'
  const seconds = Math.floor((Date.now() - new Date(utc).getTime()) / 1000)
  if (seconds < 60)  return 'just now'
  const minutes = Math.floor(seconds / 60)
  if (minutes < 60)  return `${minutes}m ago`
  const hours = Math.floor(minutes / 60)
  if (hours < 24)    return `${hours}h ago`
  return `${Math.floor(hours / 24)}d ago`
}
</script>
