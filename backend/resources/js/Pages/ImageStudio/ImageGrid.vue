<template>
  <div class="w-72 shrink-0 flex flex-col border-r border-white/8 h-full">
    <div class="flex items-center justify-between px-4 py-3 border-b border-white/6 shrink-0">
      <span class="text-[11px] font-semibold uppercase tracking-widest text-white/35">Imágenes</span>
      <span class="text-xs font-mono text-white/25">{{ store.items.value.length }}</span>
    </div>

    <div class="flex-1 overflow-y-auto p-3">
      <input ref="fileInputEl" type="file" accept="image/*" multiple class="hidden" @change="onFilePick" />

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
import { useImageStore } from '@/composables/imageStudio/useImageStore'
import { readImageFiles } from '@/composables/imageStudio/useImageUpload'
import AppImageBox from '@/components/AppImageBox.vue'
import AppIcon     from '@/components/AppIcon.vue'
import ImageCard   from './ImageCard.vue'

const store       = useImageStore()
const fileInputEl = ref<HTMLInputElement>()

async function onFilePick(e: Event) {
  const files = Array.from((e.target as HTMLInputElement).files ?? [])
  if (!files.length) return
  const raw = await readImageFiles(files)
  if (raw.length) store.add(raw)
  ;(e.target as HTMLInputElement).value = ''
}
</script>
