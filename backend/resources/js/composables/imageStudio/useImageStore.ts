import { ref, computed } from 'vue'
import type {
  ImageItem, ExportConfig, FilterState, RawImageData, ImageItemStatus, CropState,
} from '@/types/imageStudio'

// ─── Default filter state ─────────────────────────────────────────────────────

function defaultFilters(): FilterState {
  return { brightness: 0, contrast: 0, saturation: 0, shadows: 0, sharpness: 0, temperature: 0 }
}

// ─── Module-level singleton state ─────────────────────────────────────────────

const items        = ref<ImageItem[]>([])
const activeItemId = ref<string | null>(null)

// ─── Composable ───────────────────────────────────────────────────────────────

export function useImageStore() {
  const activeItem = computed(() =>
    items.value.find(i => i.id === activeItemId.value) ?? null
  )

  const totalOutputs = computed(() =>
    items.value.reduce((sum, i) => sum + i.exportConfigs.length, 0)
  )

  // ── Queue mutations ──────────────────────────────────────────────────────────

  function add(raw: RawImageData[]): void {
    for (const r of raw) {
      const id = `img-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`
      const name = r.file.name.replace(/\.[^.]+$/, '')
      items.value.push({
        id,
        name,
        status:   'idle',
        rotation: 0,
        filters:  defaultFilters(),
        source: {
          file: r.file,
          dataUrl: r.dataUrl,
          naturalWidth: r.naturalWidth,
          naturalHeight: r.naturalHeight,
          sizeBytes: r.sizeBytes,
        },
        exportConfigs: [
          { id: `${id}-c1`, label: name, format: 'webp', quality: 80, resize: { mode: 'original' } },
        ],
      })
    }
    if (!activeItemId.value && items.value.length > 0) {
      activeItemId.value = items.value[0].id
    }
  }

  function remove(id: string): void {
    items.value = items.value.filter(i => i.id !== id)
    if (activeItemId.value === id) {
      activeItemId.value = items.value[0]?.id ?? null
    }
  }

  function setActive(id: string): void {
    activeItemId.value = id
  }

  function rename(id: string, name: string): void {
    const item = items.value.find(i => i.id === id)
    if (item) item.name = name
  }

  function setStatus(id: string, status: ImageItemStatus): void {
    const item = items.value.find(i => i.id === id)
    if (item) item.status = status
  }

  function setCrop(id: string, crop: CropState | undefined): void {
    const item = items.value.find(i => i.id === id)
    if (item) item.crop = crop
  }

  function setRotation(id: string, rotation: number): void {
    const item = items.value.find(i => i.id === id)
    if (item) item.rotation = ((rotation % 360) + 360) % 360
  }

  function setFilters(id: string, filters: Partial<FilterState>): void {
    const item = items.value.find(i => i.id === id)
    if (item) item.filters = { ...item.filters, ...filters }
  }

  // ── Export config mutations ──────────────────────────────────────────────────

  function addExportConfig(itemId: string): void {
    const item = items.value.find(i => i.id === itemId)
    if (!item) return
    item.exportConfigs.unshift({
      id: `${itemId}-c${Date.now()}`,
      label: item.name,
      format: 'webp',
      quality: 80,
      resize: { mode: 'original' },
    })
  }

  function removeExportConfig(itemId: string, configId: string): void {
    const item = items.value.find(i => i.id === itemId)
    if (item) item.exportConfigs = item.exportConfigs.filter(c => c.id !== configId)
  }

  function updateExportConfig(itemId: string, configId: string, patch: Partial<ExportConfig>): void {
    const item = items.value.find(i => i.id === itemId)
    if (!item) return
    const idx = item.exportConfigs.findIndex(c => c.id === configId)
    if (idx !== -1) item.exportConfigs[idx] = { ...item.exportConfigs[idx], ...patch }
  }

  return {
    items,
    activeItemId,
    activeItem,
    totalOutputs,
    add,
    remove,
    setActive,
    rename,
    setStatus,
    setCrop,
    setRotation,
    setFilters,
    addExportConfig,
    removeExportConfig,
    updateExportConfig,
  }
}
