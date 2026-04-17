<template>
  <AppImageBox
    :src="item.source.dataUrl || undefined"
    :colors="colors"
    :name="item.name"
    :dims="`${item.source.naturalWidth} × ${item.source.naturalHeight}`"
    :active="isActive"
    @click="store.setActive(item.id)"
  >
    <template #action>
      <span
        v-if="item.status !== 'idle'"
        class="px-1.5 py-0.5 rounded-md text-[10px] font-semibold backdrop-blur-sm leading-none"
        :class="statusCfg.cls"
      >
        {{ statusCfg.label }}
      </span>
      <button
        v-else
        class="w-5 h-5 rounded-full flex items-center justify-center
               bg-black/20 backdrop-blur-sm
               text-white/0 group-hover:text-white/50
               hover:!text-white hover:bg-rose-500/60
               transition-all duration-150 cursor-pointer"
        title="Eliminar"
        @click.stop="store.remove(item.id)"
      >
        <AppIcon name="ui/x" size="xs" />
      </button>
    </template>
  </AppImageBox>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { ImageItem, ImageItemStatus } from '@/types/imageStudio'
import { useImageStore } from '@/composables/imageStudio/useImageStore'
import AppImageBox from '@/components/AppImageBox.vue'
import AppIcon     from '@/components/AppIcon.vue'

const props = defineProps<{ item: ImageItem }>()

const store    = useImageStore()
const isActive = computed(() => store.activeItemId.value === props.item.id)

const GRADIENTS: [string, string][] = [
  ['#0ea5e9', '#0369a1'], ['#06b6d4', '#0e7490'], ['#8b5cf6', '#6d28d9'],
  ['#10b981', '#059669'], ['#f59e0b', '#d97706'], ['#f43f5e', '#be123c'],
  ['#6366f1', '#4338ca'], ['#ec4899', '#be185d'],
]

const colors = computed((): [string, string] => {
  const sum = [...props.item.id].reduce((a, c) => a + c.charCodeAt(0), 0)
  return GRADIENTS[sum % GRADIENTS.length]
})

const STATUS: Record<ImageItemStatus, { label: string; cls: string }> = {
  idle:      { label: '',           cls: '' },
  editing:   { label: 'Editando',   cls: 'bg-brand-500/25 text-brand-300' },
  exporting: { label: 'Exportando', cls: 'bg-live-500/25 text-live-300' },
  done:      { label: 'Exportado',  cls: 'bg-emerald-500/25 text-emerald-300' },
  error:     { label: 'Error',      cls: 'bg-rose-500/25 text-rose-300' },
}

const statusCfg = computed(() => STATUS[props.item.status])
</script>
