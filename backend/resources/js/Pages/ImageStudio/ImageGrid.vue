<template>
  <div class="w-72 shrink-0 flex flex-col border-r border-white/8 h-full">
    <div class="flex items-center justify-between px-4 py-3 border-b border-white/6 shrink-0">
      <span class="text-[11px] font-semibold uppercase tracking-widest text-white/35">Imágenes</span>
      <span class="text-xs font-mono text-white/25">{{ items.length }}</span>
    </div>

    <div class="flex-1 overflow-y-auto p-3">
      <div v-if="items.length" class="grid grid-cols-2 gap-2">
        <ImageCard
          v-for="item in items"
          :key="item.id"
          :item="item"
          :is-active="item.id === activeItemId"
          @select="emit('select', item.id)"
        />
      </div>

      <div v-else class="h-full min-h-[200px] flex flex-col items-center justify-center text-center gap-3 py-10">
        <div class="w-10 h-10 rounded-xl bg-white/5 border border-white/8 flex items-center justify-center">
          <svg class="w-5 h-5 text-white/20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <rect x="3" y="3" width="18" height="18" rx="3" />
            <path d="M3 15l5-5 4 4 3-3 6 6" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </div>
        <div>
          <p class="text-xs font-medium text-white/25">Sin imágenes</p>
          <p class="text-[10px] text-white/15 mt-0.5">Arrastra aquí para comenzar</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { ImageItem } from '@/types/imageStudio'
import ImageCard from './ImageCard.vue'

defineProps<{
  items: ImageItem[]
  activeItemId: string | null
}>()

const emit = defineEmits<{
  select: [id: string]
}>()
</script>
