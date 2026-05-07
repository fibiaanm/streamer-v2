<template>
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="rounded-2xl border border-white/8 bg-white/3 p-5">
      <p class="text-xs font-semibold uppercase tracking-widest text-white/35 mb-3">Total failed</p>
      <p class="text-2xl font-bold text-white/90 leading-none">{{ summary?.total ?? '—' }}</p>
    </div>

    <div class="rounded-2xl border border-white/8 bg-white/3 p-5">
      <p class="text-xs font-semibold uppercase tracking-widest text-white/35 mb-3">Last failure</p>
      <p class="text-sm font-bold text-white/90 leading-snug">{{ lastFailed }}</p>
    </div>

    <div class="rounded-2xl border border-white/8 bg-white/3 p-5">
      <p class="text-xs font-semibold uppercase tracking-widest text-white/35 mb-3">Most failing job</p>
      <p class="text-xs font-mono font-bold text-white/80 leading-snug break-all">{{ summary?.most_failing_job ?? '—' }}</p>
    </div>

    <div class="rounded-2xl border border-white/8 bg-white/3 p-5">
      <p class="text-xs font-semibold uppercase tracking-widest text-white/35 mb-3">By queue</p>
      <div v-if="summary?.by_queue.length" class="flex flex-col gap-1">
        <div
          v-for="q in summary.by_queue"
          :key="q.queue"
          class="flex items-center justify-between text-xs"
        >
          <span class="text-white/50 truncate">{{ q.queue }}</span>
          <span class="text-white/80 font-semibold ml-2 shrink-0">{{ q.count }}</span>
        </div>
      </div>
      <p v-else class="text-sm text-white/30">—</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { FailedJobsSummary } from '@/types/admin'

const props = defineProps<{ summary: FailedJobsSummary | null }>()

const lastFailed = computed(() => {
  if (!props.summary?.last_failed_at) return '—'
  const d = props.summary.last_failed_at
  const utc = d.includes('T') || d.endsWith('Z') ? d : d.replace(' ', 'T') + 'Z'
  const seconds = Math.floor((Date.now() - new Date(utc).getTime()) / 1000)
  if (seconds < 60)  return 'just now'
  const minutes = Math.floor(seconds / 60)
  if (minutes < 60)  return `${minutes}m ago`
  const hours = Math.floor(minutes / 60)
  if (hours < 24)    return `${hours}h ago`
  return `${Math.floor(hours / 24)}d ago`
})
</script>
