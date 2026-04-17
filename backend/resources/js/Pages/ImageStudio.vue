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

        <StudioNav
          :active-view="activeView"
          :item-count="items.length"
          @change="setView"
        />

        <div class="flex-1 overflow-hidden">
          <GalleryView
            v-if="activeView === 'gallery'"
            :items="items"
            :active-item-id="activeItemId"
            @select="activeItemId = $event"
            @rename="handleRename"
            @open-editor="editorOpen = true"
          />
          <ExportView
            v-else
            :items="items"
            :active-item-id="activeItemId"
            :zip-name="zipName"
            @select="activeItemId = $event"
            @rename="handleRename"
            @add-config="handleAddConfig"
            @remove-config="handleRemoveConfig"
            @update-config="handleUpdateConfig"
            @download-zip="handleDownloadZip"
            @update:zip-name="zipName = $event"
          />
        </div>

        <EditModal
          :is-open="editorOpen"
          :item="activeItem"
          @close="editorOpen = false"
        />
      </div>
    </PageBackground>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import type { ImageItem, ExportConfig, StudioView } from '@/types/imageStudio'
import PageBackground from '@/components/PageBackground.vue'
import StudioNav      from './ImageStudio/StudioNav.vue'
import DropOverlay    from './ImageStudio/DropOverlay.vue'
import GalleryView    from './ImageStudio/GalleryView.vue'
import ExportView     from './ImageStudio/ExportView.vue'
import EditModal      from './ImageStudio/EditModal.vue'

// ─── Router ───────────────────────────────────────────────────────────────────

const router = useRouter()
const route  = useRoute()

// ─── State ────────────────────────────────────────────────────────────────────

const activeView   = ref<StudioView>('gallery')
const activeItemId = ref<string | null>('1')
const editorOpen   = ref(false)
const isDragging   = ref(false)
const zipName      = ref('mis-imagenes')

let dragDepth = 0

// Restore view from query param on mount
onMounted(() => {
  const qv = route.query.view as string
  if (qv === 'export' || qv === 'gallery') activeView.value = qv
})

function setView(view: StudioView) {
  activeView.value = view
  router.replace({ query: { ...route.query, view } })
}

// ─── Dummy data — Phase 1 ─────────────────────────────────────────────────────

const items = ref<ImageItem[]>([
  {
    id: '1', name: 'hero-banner', status: 'idle',
    source: { file: null as unknown as File, dataUrl: '', naturalWidth: 1920, naturalHeight: 1080, sizeBytes: 2_450_000 },
    exportConfigs: [
      { id: 'c1-1', label: 'Web optimizado', format: 'webp', quality: 80, resize: { mode: 'original' } },
    ],
  },
  {
    id: '2', name: 'avatar-profile', status: 'done',
    source: { file: null as unknown as File, dataUrl: '', naturalWidth: 800, naturalHeight: 800, sizeBytes: 124_000 },
    exportConfigs: [
      { id: 'c2-1', label: 'Original WebP',  format: 'webp', quality: 90, resize: { mode: 'original' } },
      { id: 'c2-2', label: 'Miniatura 200px', format: 'jpeg', quality: 85, resize: { mode: 'width', value: 200 } },
    ],
  },
  {
    id: '3', name: 'product-shot', status: 'editing',
    source: { file: null as unknown as File, dataUrl: '', naturalWidth: 2400, naturalHeight: 1600, sizeBytes: 3_800_000 },
    exportConfigs: [
      { id: 'c3-1', label: 'Calidad completa', format: 'jpeg', quality: 90, resize: { mode: 'original' } },
    ],
  },
  {
    id: '4', name: 'thumbnail-video', status: 'idle',
    source: { file: null as unknown as File, dataUrl: '', naturalWidth: 1280, naturalHeight: 720, sizeBytes: 890_000 },
    exportConfigs: [
      { id: 'c4-1', label: 'Thumbnail 640w', format: 'webp', quality: 75, resize: { mode: 'width', value: 640 } },
    ],
  },
  {
    id: '5', name: 'background-dark', status: 'error',
    source: { file: null as unknown as File, dataUrl: '', naturalWidth: 3840, naturalHeight: 2160, sizeBytes: 9_200_000 },
    exportConfigs: [
      { id: 'c5-1', label: 'Comprimido', format: 'webp', quality: 70, resize: { mode: 'original' } },
    ],
  },
  {
    id: '6', name: 'banner-mobile', status: 'idle',
    source: { file: null as unknown as File, dataUrl: '', naturalWidth: 750, naturalHeight: 1334, sizeBytes: 560_000 },
    exportConfigs: [
      { id: 'c6-1', label: 'Alto 600px', format: 'jpeg', quality: 80, resize: { mode: 'height', value: 600 } },
    ],
  },
])

const activeItem = computed(() =>
  items.value.find(i => i.id === activeItemId.value) ?? null
)

// ─── Drag & drop ──────────────────────────────────────────────────────────────

function onDragEnter(e: DragEvent) {
  if (!e.dataTransfer?.types.includes('Files')) return
  dragDepth++
  isDragging.value = true
}

function onDragLeave() {
  dragDepth--
  if (dragDepth <= 0) { dragDepth = 0; isDragging.value = false }
}

function onDrop() {
  dragDepth = 0
  isDragging.value = false
  // Phase 2: useImageUpload
}

// ─── Mutations ────────────────────────────────────────────────────────────────

function handleRename(id: string, name: string) {
  const item = items.value.find(i => i.id === id)
  if (item) item.name = name
}

function handleAddConfig() {
  const item = activeItem.value
  if (!item) return
  item.exportConfigs.unshift({
    id: `c${item.id}-${Date.now()}`,
    label: 'Nueva config',
    format: 'webp',
    quality: 80,
    resize: { mode: 'original' },
  })
}

function handleRemoveConfig(configId: string) {
  const item = activeItem.value
  if (!item) return
  item.exportConfigs = item.exportConfigs.filter(c => c.id !== configId)
}

function handleUpdateConfig(configId: string, config: ExportConfig) {
  const item = activeItem.value
  if (!item) return
  const idx = item.exportConfigs.findIndex(c => c.id === configId)
  if (idx !== -1) item.exportConfigs[idx] = config
}

function handleDownloadZip() {
  // Phase 4: useImageExport + JSZip
}
</script>
