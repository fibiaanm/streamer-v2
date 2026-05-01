<template>
  <div class="rounded-2xl border border-white/8 bg-white/3 p-5 mb-5">
    <div class="flex items-center justify-between mb-4">
      <p class="text-sm font-semibold text-white/80">Tokens over time</p>
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
      <Line v-if="chartData" :data="chartData" :options="chartOptions" />
      <p v-else class="text-sm text-white/30 text-center pt-20">No data for this period</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Line } from 'vue-chartjs'
import {
  Chart as ChartJS, CategoryScale, LinearScale, PointElement,
  LineElement, Tooltip, Legend, Filler,
} from 'chart.js'
import type { UsageTimelinePoint, TimelineGroupBy } from '@/types/admin'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Tooltip, Legend, Filler)

const props = defineProps<{ points: UsageTimelinePoint[] }>()
defineEmits<{ 'group-by-change': [v: TimelineGroupBy] }>()

const groupBy = ref<TimelineGroupBy>('day')

const chartData = computed(() => {
  if (!props.points.length) return null
  return {
    labels: props.points.map(p => p.date),
    datasets: [
      {
        label: 'Input',
        data: props.points.map(p => p.input_tokens),
        borderColor: '#38bdf8',
        backgroundColor: 'rgba(56,189,248,0.08)',
        fill: true,
        tension: 0.3,
        pointRadius: 3,
      },
      {
        label: 'Output',
        data: props.points.map(p => p.output_tokens),
        borderColor: '#a78bfa',
        backgroundColor: 'rgba(167,139,250,0.08)',
        fill: true,
        tension: 0.3,
        pointRadius: 3,
      },
    ],
  }
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: { mode: 'index' as const, intersect: false },
  plugins: {
    legend: { position: 'top' as const, labels: { color: 'rgba(255,255,255,0.5)', boxWidth: 10, font: { size: 11 } } },
    tooltip: { callbacks: { label: (ctx: { dataset: { label: string }; parsed: { y: number } }) => ` ${ctx.dataset.label}: ${ctx.parsed.y.toLocaleString()}` } },
  },
  scales: {
    x: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: 'rgba(255,255,255,0.35)', font: { size: 11 } } },
    y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: 'rgba(255,255,255,0.35)', font: { size: 11 } } },
  },
}
</script>
