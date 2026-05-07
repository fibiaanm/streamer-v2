<template>
  <div class="rounded-2xl border border-white/8 bg-white/3 p-5">
    <div class="flex items-center justify-between mb-4">
      <p class="text-sm font-semibold text-white/80">Failures over time</p>
      <select
        v-model="groupBy"
        class="text-xs px-2.5 py-1.5 rounded-lg border border-white/10 bg-white/5 text-white/60 cursor-pointer"
        @change="$emit('group-by-change', groupBy)"
      >
        <option value="day">By day</option>
        <option value="week">By week</option>
      </select>
    </div>
    <div class="h-56">
      <Bar v-if="chartData" :data="chartData" :options="chartOptions" />
      <p v-else class="text-sm text-white/30 text-center pt-20">No failures in this period</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Bar } from 'vue-chartjs'
import {
  Chart as ChartJS, CategoryScale, LinearScale, BarElement, Tooltip, Legend,
} from 'chart.js'
import type { FailedJobsTimelinePoint, TimelineGroupBy } from '@/types/admin'

ChartJS.register(CategoryScale, LinearScale, BarElement, Tooltip, Legend)

const PALETTE = [
  { bg: 'rgba(248,113,113,0.7)', border: 'rgba(248,113,113,0.9)' },
  { bg: 'rgba(251,146,60,0.7)',  border: 'rgba(251,146,60,0.9)' },
  { bg: 'rgba(56,189,248,0.7)',  border: 'rgba(56,189,248,0.9)' },
  { bg: 'rgba(167,139,250,0.7)', border: 'rgba(167,139,250,0.9)' },
  { bg: 'rgba(74,222,128,0.7)',  border: 'rgba(74,222,128,0.9)' },
]

const props = defineProps<{ points: FailedJobsTimelinePoint[] }>()
defineEmits<{ 'group-by-change': [v: TimelineGroupBy] }>()

const groupBy = ref<TimelineGroupBy>('day')

const chartData = computed(() => {
  if (!props.points.length) return null

  // derive ordered list of dates and queues from the raw rows
  const dates  = [...new Set(props.points.map(p => p.date))].sort()
  const queues = [...new Set(props.points.map(p => p.queue))].sort()

  // index for O(1) lookup: "date|queue" -> count
  const idx = new Map(props.points.map(p => [`${p.date}|${p.queue}`, p.count]))

  return {
    labels: dates,
    datasets: queues.map((q, i) => {
      const color = PALETTE[i % PALETTE.length]
      return {
        label: q,
        data: dates.map(d => idx.get(`${d}|${q}`) ?? 0),
        backgroundColor: color.bg,
        borderColor: color.border,
        borderWidth: 1,
        borderRadius: 3,
        stack: 'failures',
      }
    }),
  }
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: { mode: 'index' as const, intersect: false },
  plugins: {
    legend: {
      position: 'top' as const,
      labels: { color: 'rgba(255,255,255,0.5)', boxWidth: 10, font: { size: 11 } },
    },
    tooltip: {
      callbacks: {
        label: (ctx: { dataset: { label: string }; parsed: { y: number } }) =>
          ` ${ctx.dataset.label}: ${ctx.parsed.y}`,
      },
    },
  },
  scales: {
    x: { stacked: true, grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: 'rgba(255,255,255,0.35)', font: { size: 11 } } },
    y: { stacked: true, grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: 'rgba(255,255,255,0.35)', font: { size: 11 }, stepSize: 1 }, beginAtZero: true },
  },
}
</script>
