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

      <div class="flex gap-2">
        <select
          v-model="filters.provider"
          class="text-xs px-2.5 py-1.5 rounded-lg border border-white/10 bg-white/5 text-white/60 cursor-pointer"
          @change="reload"
        >
          <option value="">All providers</option>
          <option value="openai">OpenAI</option>
          <option value="anthropic">Anthropic</option>
          <option value="gemini">Gemini</option>
          <option value="grok">Grok</option>
        </select>

        <select
          v-model="filters.type"
          class="text-xs px-2.5 py-1.5 rounded-lg border border-white/10 bg-white/5 text-white/60 cursor-pointer"
          @change="reload"
        >
          <option value="">All types</option>
          <option value="text">Text</option>
          <option value="image">Image</option>
          <option value="embedding">Embedding</option>
          <option value="memory">Memory</option>
          <option value="audio">Audio</option>
        </select>
      </div>
    </div>

    <SummaryCards :summary="summary" />

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
      <TimelineChart
        :points="timeline"
        @group-by-change="(g) => { timelineGroupBy = g; fetchTimeline(filters, g) }"
      />
      <BreakdownChart
        :items="breakdown"
        @group-by-change="(g) => { breakdownGroupBy = g; fetchBreakdown(filters, g) }"
      />
    </div>

    <TopUsersTable :users="topUsers" />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAdminUsage } from '@/composables/admin/useAdminUsage'
import SummaryCards   from '@/components/Admin/usage/SummaryCards.vue'
import TimelineChart  from '@/components/Admin/usage/TimelineChart.vue'
import BreakdownChart from '@/components/Admin/usage/BreakdownChart.vue'
import TopUsersTable  from '@/components/Admin/usage/TopUsersTable.vue'
import type { UsageFilters, BreakdownGroupBy, TimelineGroupBy } from '@/types/admin'

const { summary, timeline, breakdown, topUsers, fetchSummary, fetchTimeline, fetchBreakdown, fetchTopUsers } = useAdminUsage()

const today   = () => new Date().toISOString().slice(0, 10)
const daysAgo = (n: number) => new Date(Date.now() - n * 86400_000).toISOString().slice(0, 10)

const presets = [
  { label: 'Today',    from: today(),     to: today() },
  { label: 'Last 7d',  from: daysAgo(6),  to: today() },
  { label: 'Last 30d', from: daysAgo(29), to: today() },
  { label: 'Last 90d', from: daysAgo(89), to: today() },
]

const activePreset     = ref('Last 7d')
const timelineGroupBy  = ref<TimelineGroupBy>('day')
const breakdownGroupBy = ref<BreakdownGroupBy>('model')

const filters = ref<UsageFilters>({
  from: daysAgo(6), to: today(), provider: '', model: '', type: '',
})

const applyPreset = (p: { label: string; from: string; to: string }) => {
  activePreset.value = p.label
  filters.value.from = p.from
  filters.value.to   = p.to
  reload()
}

const clean = () => ({
  ...filters.value,
  provider: filters.value.provider || undefined,
  model:    filters.value.model    || undefined,
  type:     filters.value.type     || undefined,
})

const reload = () => {
  const f = clean()
  fetchSummary(f)
  fetchTimeline(f, timelineGroupBy.value)
  fetchBreakdown(f, breakdownGroupBy.value)
  fetchTopUsers(f)
}

onMounted(reload)
</script>
