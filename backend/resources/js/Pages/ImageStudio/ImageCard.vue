<template>
  <div
    class="group relative rounded-xl overflow-hidden cursor-pointer select-none transition-all duration-200"
    :class="isActive
      ? 'ring-2 ring-brand-400 ring-offset-1 ring-offset-[#080d1c]'
      : 'ring-1 ring-white/8 hover:ring-white/20 hover:scale-[1.015]'"
    @click="emit('select')"
  >
    <!-- Thumbnail: brand grid + gradient on top -->
    <div class="aspect-video w-full relative overflow-hidden" :style="GRID_BG">
      <img
        v-if="item.source.dataUrl"
        :src="item.source.dataUrl"
        :alt="item.name"
        class="absolute inset-0 w-full h-full object-cover"
      />
      <div
        v-else
        class="absolute inset-0"
        :style="{ background: `linear-gradient(135deg, ${colors[0]} 0%, ${colors[1]} 100%)`, opacity: '0.82' }"
      />

      <!-- Status badge -->
      <span
        class="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded-md text-[10px] font-semibold backdrop-blur-sm leading-none"
        :class="statusCfg.cls"
      >
        {{ statusCfg.label }}
      </span>
    </div>

    <!-- Name + dims -->
    <div class="px-2 pt-1.5 pb-2 transition-colors" :class="isActive ? 'bg-brand-500/10' : 'bg-black/25'">
      <p class="text-xs font-medium text-white/80 truncate leading-tight" :title="item.name">
        {{ item.name }}
      </p>
      <p class="text-[10px] text-white/25 mt-0.5 font-mono leading-none">
        {{ item.source.naturalWidth }}&thinsp;×&thinsp;{{ item.source.naturalHeight }}
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { ImageItem, ImageItemStatus } from '@/types/imageStudio'

const props = defineProps<{
  item: ImageItem
  isActive: boolean
}>()

const emit = defineEmits<{
  select: []
}>()

// ─── Brand grid background ────────────────────────────────────────────────────

const GRID_BG = {
  backgroundColor: '#080d1c',
  backgroundImage: [
    'linear-gradient(rgba(14,165,233,.07) 1px, transparent 1px)',
    'linear-gradient(90deg, rgba(14,165,233,.07) 1px, transparent 1px)',
  ].join(', '),
  backgroundSize: '20px 20px',
}

// ─── Gradient placeholder ─────────────────────────────────────────────────────

const GRADIENTS: [string, string][] = [
  ['#0ea5e9', '#0369a1'], ['#06b6d4', '#0e7490'], ['#8b5cf6', '#6d28d9'],
  ['#10b981', '#059669'], ['#f59e0b', '#d97706'], ['#f43f5e', '#be123c'],
  ['#6366f1', '#4338ca'], ['#ec4899', '#be185d'],
]

const colors = computed((): [string, string] => {
  const sum = [...props.item.id].reduce((a, c) => a + c.charCodeAt(0), 0)
  return GRADIENTS[sum % GRADIENTS.length]
})

// ─── Status ───────────────────────────────────────────────────────────────────

const STATUS: Record<ImageItemStatus, { label: string; cls: string }> = {
  idle:      { label: 'Listo',      cls: 'bg-white/10 text-white/50' },
  editing:   { label: 'Editando',   cls: 'bg-brand-500/25 text-brand-300' },
  exporting: { label: 'Exportando', cls: 'bg-live-500/25 text-live-300' },
  done:      { label: 'Exportado',  cls: 'bg-emerald-500/25 text-emerald-300' },
  error:     { label: 'Error',      cls: 'bg-rose-500/25 text-rose-300' },
}

const statusCfg = computed(() => STATUS[props.item.status])
</script>
