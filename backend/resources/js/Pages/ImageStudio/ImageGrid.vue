<template>
  <div class="w-72 shrink-0 flex flex-col border-r border-white/8 h-full">
    <div class="flex items-center justify-between px-4 py-3 border-b border-white/6 shrink-0">
      <span class="text-[11px] font-semibold uppercase tracking-widest text-white/35">Imágenes</span>
      <div class="flex items-center gap-2">
        <svg
          v-if="isProcessing"
          class="w-3 h-3 text-brand-400 animate-spin"
          viewBox="0 0 24 24"
          fill="none"
        >
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
        </svg>
        <span class="text-xs font-mono text-white/25">{{ store.items.value.length }}</span>
      </div>
    </div>

    <div class="flex-1 overflow-y-auto p-3">
      <input ref="fileInputEl" type="file" accept="image/*,.zip,.heic,.heif" multiple class="hidden" @change="onFilePick" />

      <div class="grid grid-cols-2 gap-2">
        <ImageCard
          v-for="item in store.items.value"
          :key="item.id"
          :item="item"
        />

        <!-- Add card -->
        <AppImageBox dashed @click="fileInputEl?.click()">
          <div class="absolute inset-0 flex items-center justify-center">
            <AppIcon name="ui/plus" size="sm" class="text-white/20 group-hover:text-brand-400 transition-colors" />
          </div>
        </AppImageBox>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useImageStore }    from '@/composables/imageStudio/useImageStore'
import { useFileProcessor } from '@/composables/imageStudio/useFileProcessor'
import AppImageBox from '@/components/AppImageBox.vue'
import AppIcon     from '@/components/AppIcon.vue'
import ImageCard   from './ImageCard.vue'

const store = useImageStore()
const { isProcessing, processFiles } = useFileProcessor()
const fileInputEl = ref<HTMLInputElement>()

async function onFilePick(e: Event) {
  const files = Array.from((e.target as HTMLInputElement).files ?? [])
  if (!files.length) return
  await processFiles(files, raw => store.add([raw]))
  ;(e.target as HTMLInputElement).value = ''
}
</script>
