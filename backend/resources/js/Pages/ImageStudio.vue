<template>
  <div class="dark">
    <PageBackground>
      <div
        class="h-screen flex flex-col overflow-hidden"
        @dragenter="onDragEnter"
        @dragleave="onDragLeave"
        @dragover.prevent
        @drop.prevent="onDrop"
      >
        <DropOverlay v-if="isDragging" />

        <StudioNav />

        <!-- Main area -->
        <div class="flex flex-1 overflow-hidden">
          <ImageGrid />

          <!-- Right panel -->
          <div class="flex-1 flex flex-col overflow-hidden">
            <template v-if="store.activeItem.value">
              <ImageHero @open-editor="editorOpen = true" />
              <ExportConfigPanel />
            </template>

            <div
              v-else
              class="h-full flex flex-col items-center justify-center gap-4 p-10 text-center"
            >
              <div class="w-16 h-16 rounded-2xl bg-white/4 border border-white/6 flex items-center justify-center">
                <AppIcon name="ui/image" size="lg" class="text-white/15" />
              </div>
              <div class="space-y-1.5 max-w-[220px]">
                <p class="text-sm font-medium text-white/25">Ninguna imagen seleccionada</p>
                <p class="text-xs text-white/15 leading-relaxed">
                  Arrastra imágenes o selecciónalas desde el panel izquierdo
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Full-width export footer -->
        <div class="shrink-0 border-t border-white/8 px-5 py-3 flex items-center gap-3">
          <div class="flex items-center gap-2 min-w-0">
            <span class="text-[11px] text-white/30 shrink-0">ZIP</span>
            <div class="flex items-center gap-1 bg-white/5 border border-white/10 rounded-lg px-2.5 py-1.5">
              <input
                v-model="zipName"
                class="text-xs text-white/70 bg-transparent outline-none w-32"
                placeholder="mis-imagenes"
              />
              <span class="text-xs text-white/25 shrink-0">.zip</span>
            </div>
          </div>

          <p class="text-xs text-white/25 flex-1 min-w-0 truncate">
            <span class="text-white/50 font-medium">{{ store.items.value.length }}</span>
            {{ store.items.value.length === 1 ? 'imagen' : 'imágenes' }}
            <span class="mx-1.5 text-white/15">·</span>
            <span class="text-white/50 font-medium">{{ store.totalOutputs.value }}</span>
            outputs
          </p>

          <AppButton
            variant="primary"
            size="sm"
            :disabled="store.totalOutputs.value === 0 || isExporting"
            icon="ui/download"
            @click="handleDownloadZip"
          >
            <template v-if="isExporting">
              {{ progress.done }}/{{ progress.total }}
            </template>
            <template v-else>Descargar ZIP</template>
          </AppButton>
        </div>

        <EditModal :is-open="editorOpen" @close="editorOpen = false" />
      </div>
    </PageBackground>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useImageStore } from '@/composables/imageStudio/useImageStore'
import { readImageFiles } from '@/composables/imageStudio/useImageUpload'
import { useImageExport } from '@/composables/imageStudio/useImageExport'
import PageBackground    from '@/components/PageBackground.vue'
import AppButton         from '@/components/AppButton.vue'
import AppIcon           from '@/components/AppIcon.vue'
import StudioNav         from './ImageStudio/StudioNav.vue'
import DropOverlay       from './ImageStudio/DropOverlay.vue'
import ImageGrid         from './ImageStudio/ImageGrid.vue'
import ImageHero         from './ImageStudio/ImageHero.vue'
import ExportConfigPanel from './ImageStudio/ExportConfigPanel.vue'
import EditModal         from './ImageStudio/EditModal.vue'

const store              = useImageStore()
const { exportAll, isExporting, progress } = useImageExport()
const editorOpen = ref(false)
const zipName    = ref('mis-imagenes')
const isDragging = ref(false)

let dragDepth = 0

function onDragEnter(e: DragEvent) {
  if (!e.dataTransfer?.types.includes('Files')) return
  dragDepth++
  isDragging.value = true
}

function onDragLeave() {
  dragDepth--
  if (dragDepth <= 0) { dragDepth = 0; isDragging.value = false }
}

async function onDrop(e: DragEvent) {
  dragDepth = 0
  isDragging.value = false
  const files = Array.from(e.dataTransfer?.files ?? [])
  if (!files.length) return
  const raw = await readImageFiles(files)
  if (raw.length) store.add(raw)
}

async function handleDownloadZip() {
  await exportAll(zipName.value)
}
</script>
