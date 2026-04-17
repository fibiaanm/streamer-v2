<template>
  <div
    class="aspect-square rounded-xl overflow-hidden relative cursor-pointer select-none
           transition-all duration-200 group"
    :class="containerClass"
  >
    <div class="absolute inset-0" :style="GRID_BG" />

    <img
      v-if="src"
      :src="src"
      class="absolute inset-0 w-full h-full object-contain"
    />
    <div
      v-else-if="colors"
      class="absolute inset-0"
      :style="{ background: `linear-gradient(135deg, ${colors[0]} 0%, ${colors[1]} 100%)`, opacity: '0.82' }"
    />

    <!-- Bottom info overlay -->
    <div
      v-if="name"
      class="absolute bottom-0 inset-x-0 px-2.5 py-2 backdrop-blur-sm bg-black/0"
    >
      <p class="text-xs font-medium text-white/85 truncate leading-tight">{{ name }}</p>
      <p v-if="dims" class="text-[10px] text-white/35 font-mono leading-none mt-0.5">{{ dims }}</p>
    </div>

    <!-- Top-right: status badge or action button -->
    <div v-if="$slots.action" class="absolute top-1.5 right-1.5">
      <slot name="action" />
    </div>

    <!-- Default slot: for "+" or custom overlays -->
    <slot />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  src?:    string
  colors?: [string, string]
  name?:   string
  dims?:   string
  active?: boolean
  dashed?: boolean
}>()

const GRID_BG = {
  backgroundColor: '#080d1c',
  backgroundImage: [
    'linear-gradient(rgba(14,165,233,.07) 1px, transparent 1px)',
    'linear-gradient(90deg, rgba(14,165,233,.07) 1px, transparent 1px)',
  ].join(', '),
  backgroundSize: '20px 20px',
}

const containerClass = computed(() => {
  if (props.active) return 'ring-2 ring-brand-400 ring-offset-1 ring-offset-[#080d1c]'
  if (props.dashed) return 'border border-dashed border-white/12 hover:border-brand-400/40 hover:bg-brand-500/5'
  return 'ring-1 ring-white/8 hover:ring-white/20 hover:scale-[1.015]'
})
</script>
