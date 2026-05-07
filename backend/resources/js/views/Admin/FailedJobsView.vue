<template>
  <div>
    <!-- Filters -->
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
      <div class="flex gap-1.5">
        <button
          v-for="p in presets"
          :key="p.label"
          class="text-xs px-3 py-1.5 rounded-lg border transition-colors cursor-pointer"
          :class="activePreset === p.label
            ? 'bg-brand-500/20 border-brand-500/30 text-brand-300 font-medium'
            : 'border-white/10 bg-white/5 text-white/40 hover:text-white/70'"
          @click="applyPreset(p)"
        >{{ p.label }}</button>
      </div>

      <select
        v-model="filters.queue"
        class="text-xs px-2.5 py-1.5 rounded-lg border border-white/10 bg-white/5 text-white/60 cursor-pointer"
        @change="reload"
      >
        <option value="">All queues</option>
        <option v-for="q in queues" :key="q" :value="q">{{ q }}</option>
      </select>
    </div>

    <FailedJobsSummaryCards :summary="summary" />

    <FailedJobsTimelineChart
      :points="timeline"
      @group-by-change="(g) => { timelineGroupBy = g; fetchTimeline(filters, g) }"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAdminFailedJobs } from '@/composables/admin/useAdminFailedJobs'
import FailedJobsSummaryCards  from '@/components/Admin/failed-jobs/FailedJobsSummaryCards.vue'
import FailedJobsTimelineChart from '@/components/Admin/failed-jobs/FailedJobsTimelineChart.vue'
import type { TimelineGroupBy } from '@/types/admin'
import type { FailedJobsFilters } from '@/composables/admin/useAdminFailedJobs'

const { summary, timeline, fetchSummary, fetchTimeline } = useAdminFailedJobs()

const today   = () => new Date().toISOString().slice(0, 10)
const daysAgo = (n: number) => new Date(Date.now() - n * 86400_000).toISOString().slice(0, 10)

const presets = [
  { label: 'Today',    from: today(),     to: today() },
  { label: 'Last 7d',  from: daysAgo(6),  to: today() },
  { label: 'Last 30d', from: daysAgo(29), to: today() },
  { label: 'Last 90d', from: daysAgo(89), to: today() },
]

const activePreset    = ref('Last 7d')
const timelineGroupBy = ref<TimelineGroupBy>('day')

const filters = ref<FailedJobsFilters>({
  from: daysAgo(6), to: today(), queue: '',
})

const queues = computed(() => summary.value?.queues ?? [])

const applyPreset = (p: { label: string; from: string; to: string }) => {
  activePreset.value = p.label
  filters.value.from = p.from
  filters.value.to   = p.to
  reload()
}

const clean = (): FailedJobsFilters => ({
  from:  filters.value.from,
  to:    filters.value.to,
  queue: filters.value.queue || undefined,
})

const reload = () => {
  const f = clean()
  fetchSummary(f)
  fetchTimeline(f, timelineGroupBy.value)
}

onMounted(reload)
</script>
