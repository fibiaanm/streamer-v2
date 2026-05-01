<template>
  <div class="rounded-2xl border border-white/8 bg-white/3 p-5 mb-5">
    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
      <p class="text-sm font-semibold text-white/80">Breakdown</p>
      <div class="flex gap-1">
        <button
          v-for="opt in groupByOptions"
          :key="opt.value"
          class="text-xs px-2.5 py-1.5 rounded-lg border transition-colors cursor-pointer"
          :class="groupBy === opt.value
            ? 'bg-brand-500/20 border-brand-500/30 text-brand-300'
            : 'border-white/10 bg-white/5 text-white/40 hover:text-white/70'"
          @click="groupBy = opt.value; $emit('group-by-change', groupBy)"
        >{{ opt.label }}</button>
      </div>
    </div>
    <div class="h-52">
      <Bar v-if="chartData" :data="chartData" :options="chartOptions" />
      <p v-else class="text-sm text-white/30 text-center pt-16">No data for this period</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Bar } from 'vue-chartjs'
import {
  Chart as ChartJS, CategoryScale, LinearScale,
  BarElement, Tooltip, Legend,
} from 'chart.js'
import type { UsageBreakdownItem, BreakdownGroupBy } from '@/types/admin'

ChartJS.register(CategoryScale, LinearScale, BarElement, Tooltip, Legend)

const props = defineProps<{ items: UsageBreakdownItem[] }>()
defineEmits<{ 'group-by-change': [v: BreakdownGroupBy] }>()

const groupBy = ref<BreakdownGroupBy>('model')

const groupByOptions = [
  { value: 'model' as BreakdownGroupBy,    label: 'Model' },
  { value: 'provider' as BreakdownGroupBy, label: 'Provider' },
  { value: 'type' as BreakdownGroupBy,     label: 'Type' },
]

const chartData = computed(() => {
  if (!props.items.length) return null
  return {
    labels: props.items.map(i => i.key),
    datasets: [
      {
        label: 'Input',
        data: props.items.map(i => i.input_tokens),
        backgroundColor: 'rgba(56,189,248,0.7)',
        borderRadius: 4,
      },
      {
        label: 'Output',
        data: props.items.map(i => i.output_tokens),
        backgroundColor: 'rgba(167,139,250,0.7)',
        borderRadius: 4,
      },
    ],
  }
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { position: 'top' as const, labels: { color: 'rgba(255,255,255,0.5)', boxWidth: 10, font: { size: 11 } } },
  },
  scales: {
    x: { grid: { display: false }, ticks: { color: 'rgba(255,255,255,0.35)', font: { size: 11 } } },
    y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: 'rgba(255,255,255,0.35)', font: { size: 11 } } },
  },
}
</script>
