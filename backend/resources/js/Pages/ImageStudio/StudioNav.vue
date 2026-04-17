<template>
  <AppHeader>
    <template #left>
      <div class="flex items-center gap-3">
        <div class="w-6 h-6 rounded-md bg-brand-500 shadow-lg shadow-brand-500/40 shrink-0" />
        <span class="text-sm font-semibold text-white tracking-tight">Image Studio</span>
      </div>
    </template>

    <template #center>
      <StudioTabs v-model="view" :tabs="TABS" />
    </template>

    <template #right>
      <span
        v-if="itemCount > 0"
        class="px-2.5 py-1 rounded-full text-xs font-medium bg-white/8 text-white/45 tabular-nums"
      >
        {{ itemCount }} {{ itemCount === 1 ? 'imagen' : 'imágenes' }}
      </span>
      <span v-else class="text-xs text-white/20">Sin imágenes</span>
    </template>
  </AppHeader>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { StudioView } from '@/types/imageStudio'
import AppHeader from '@/components/AppHeader.vue'
import StudioTabs, { type TabOption } from './StudioTabs.vue'

const props = defineProps<{ activeView: StudioView; itemCount: number }>()
const emit  = defineEmits<{ change: [view: StudioView] }>()

const view = computed<StudioView>({
  get: () => props.activeView,
  set: (v) => emit('change', v),
})

const TABS: TabOption<StudioView>[] = [
  { id: 'gallery', label: 'Galería',  icon: 'ui/gallery' },
  { id: 'export',  label: 'Exportar', icon: 'ui/download' },
]
</script>
