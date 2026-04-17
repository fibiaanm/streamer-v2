<template>
  <div class="flex h-full overflow-hidden">
    <ImageGrid
      :items="items"
      :active-item-id="activeItemId"
      @select="emit('select', $event)"
    />

    <div class="flex-1 flex flex-col overflow-hidden">
      <ImageInfoPanel
        v-if="activeItem"
        :item="activeItem"
        @open-editor="emit('openEditor')"
        @rename="(name) => emit('rename', activeItem!.id, name)"
      />

      <div
        v-else
        class="h-full flex flex-col items-center justify-center text-center gap-4 p-10"
      >
        <div class="w-16 h-16 rounded-2xl bg-white/4 border border-white/6 flex items-center justify-center">
          <svg class="w-8 h-8 text-white/15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
            <rect x="3" y="3" width="18" height="18" rx="4" />
            <circle cx="8.5" cy="8.5" r="1.5" />
            <path d="M3 16l5-5 4 4 3-3 6 6" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </div>
        <div class="space-y-1.5 max-w-[220px]">
          <p class="text-sm font-medium text-white/25">Ninguna imagen seleccionada</p>
          <p class="text-xs text-white/15 leading-relaxed">
            Haz click en una imagen para ver sus detalles y editarla
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { ImageItem } from '@/types/imageStudio'
import ImageGrid from './ImageGrid.vue'
import ImageInfoPanel from './ImageInfoPanel.vue'

const props = defineProps<{
  items: ImageItem[]
  activeItemId: string | null
}>()

const emit = defineEmits<{
  select: [id: string]
  rename: [id: string, name: string]
  openEditor: []
}>()

const activeItem = computed(() =>
  props.items.find(i => i.id === props.activeItemId) ?? null
)
</script>
