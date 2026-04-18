<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div
        v-if="isOpen && item"
        class="fixed inset-0 z-[200] flex items-center justify-center p-4"
      >
        <div class="absolute inset-0 bg-black/75 backdrop-blur-sm" @click="handleClose" />

        <div class="relative glass rounded-2xl w-full max-w-6xl flex flex-col overflow-hidden" style="height: 88vh">

          <!-- Header -->
          <div class="flex items-center justify-between px-5 py-3.5 border-b border-white/8 shrink-0">
            <div class="flex items-center gap-3">
              <button
                class="flex items-center gap-1.5 text-xs text-white/35 hover:text-white/70 transition-colors cursor-pointer"
                @click="handleClose"
              >
                <AppIcon name="ui/chevron-left" size="sm" />
                Volver
              </button>
              <div class="w-px h-4 bg-white/10" />
              <span class="text-sm font-semibold text-white/85">{{ item.name }}</span>
            </div>
            <AppButton variant="primary" size="sm" @click="handleSave">
              Guardar y cerrar
            </AppButton>
          </div>

          <!-- Body -->
          <div class="flex flex-1 overflow-hidden">

            <!-- Tool sidebar -->
            <div class="w-14 shrink-0 border-r border-white/8 flex flex-col items-center py-4 gap-1.5">
              <button
                v-for="tool in TOOLS"
                :key="tool.id"
                :title="tool.label"
                class="w-9 h-9 rounded-xl flex items-center justify-center transition-colors cursor-pointer"
                :class="activeTool === tool.id
                  ? 'bg-brand-500/20 text-brand-400'
                  : 'text-white/25 hover:text-white/60 hover:bg-white/5'"
                @click="onToolClick(tool.id)"
              >
                <AppIcon :name="tool.icon" size="sm" />
              </button>

              <div class="w-6 border-t border-white/8 my-1.5" />

              <button
                title="Rotar izquierda"
                class="w-9 h-9 rounded-xl flex items-center justify-center
                       text-white/25 hover:text-white/60 hover:bg-white/5 transition-colors cursor-pointer"
                @click="rotateCCW"
              >
                <AppIcon name="ui/rotate-ccw" size="sm" />
              </button>
              <button
                title="Rotar derecha"
                class="w-9 h-9 rounded-xl flex items-center justify-center
                       text-white/25 hover:text-white/60 hover:bg-white/5 transition-colors cursor-pointer"
                @click="rotateCW"
              >
                <AppIcon name="ui/rotate-cw" size="sm" />
              </button>
            </div>

            <!-- Preview area -->
            <div class="flex-1 overflow-hidden bg-[#080d1c] flex items-center justify-center">
              <img
                v-if="item"
                :src="item.source.dataUrl"
                :style="previewStyle"
                class="object-contain select-none pointer-events-none"
                style="max-width: 85%; max-height: 85%"
                draggable="false"
              />
            </div>

            <!-- Right panel -->
            <div class="w-64 shrink-0 border-l border-white/8 flex flex-col overflow-hidden">

              <!-- Crop panel (only when crop tool active) -->
              <template v-if="activeTool === 'crop'">
                <div class="px-4 py-3 border-b border-white/6 shrink-0">
                  <p class="text-[11px] font-semibold uppercase tracking-widest text-white/30 mb-3">Recorte</p>
                  <div class="flex items-center gap-2">
                    <div class="flex-1 space-y-1">
                      <p class="text-[10px] text-white/25">W</p>
                      <input
                        type="number"
                        :value="cropW"
                        min="1"
                        class="w-full bg-white/5 border border-white/10 rounded-lg px-2 py-1.5
                               text-xs text-white/70 outline-none focus:border-brand-500/50
                               [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none"
                        @change="setCropWidth(+($event.target as HTMLInputElement).value)"
                      />
                    </div>

                    <button
                      :title="aspectLocked ? 'Liberar proporción' : 'Bloquear proporción'"
                      class="mt-4 w-7 h-7 rounded-lg flex items-center justify-center transition-colors cursor-pointer shrink-0"
                      :class="aspectLocked
                        ? 'bg-brand-500/20 text-brand-400'
                        : 'text-white/25 hover:text-white/60 hover:bg-white/5'"
                      @click="toggleAspectLock"
                    >
                      <AppIcon :name="aspectLocked ? 'ui/lock' : 'ui/lock-open'" size="xs" />
                    </button>

                    <div class="flex-1 space-y-1">
                      <p class="text-[10px] text-white/25">H</p>
                      <input
                        type="number"
                        :value="cropH"
                        min="1"
                        class="w-full bg-white/5 border border-white/10 rounded-lg px-2 py-1.5
                               text-xs text-white/70 outline-none focus:border-brand-500/50
                               [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none"
                        @change="setCropHeight(+($event.target as HTMLInputElement).value)"
                      />
                    </div>
                  </div>
                </div>
              </template>

              <!-- Filter panel -->
              <div class="px-4 py-3 border-b border-white/6 shrink-0">
                <p class="text-[11px] font-semibold uppercase tracking-widest text-white/30">Ajustes</p>
              </div>
              <div class="flex-1 overflow-y-auto px-4 py-4 space-y-5">
                <div v-for="f in FILTERS" :key="f.id" class="space-y-1.5">
                  <div class="flex items-center justify-between">
                    <span class="text-xs text-white/40">{{ f.label }}</span>
                    <span class="text-xs font-mono text-white/35 tabular-nums">
                      {{ filterValues[f.id] > 0 ? '+' : '' }}{{ filterValues[f.id] }}
                    </span>
                  </div>
                  <input
                    v-model.number="filterValues[f.id]"
                    type="range" min="-100" max="100"
                    class="w-full h-1 rounded-full cursor-pointer appearance-none bg-white/10"
                    :style="{ accentColor: '#0ea5e9' }"
                    @change="persistFilters"
                  />
                </div>
              </div>
              <div class="px-4 py-3 border-t border-white/6 shrink-0">
                <button
                  class="w-full text-xs text-white/25 hover:text-white/50 transition-colors cursor-pointer"
                  @click="resetFilters"
                >
                  Restablecer ajustes
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import type { FilterState } from '@/types/imageStudio'
import { useImageStore } from '@/composables/imageStudio/useImageStore'
import AppButton from '@/components/AppButton.vue'
import AppIcon   from '@/components/AppIcon.vue'

type EditorTool = 'select' | 'crop'

const props = defineProps<{ isOpen: boolean }>()
const emit  = defineEmits<{ close: [] }>()

const store = useImageStore()
const item  = store.activeItem

// ─── Local editor state ───────────────────────────────────────────────────────

const activeTool   = ref<EditorTool>('select')
const cropDirty    = ref(false)
const rotation     = ref(0)
const cropW        = ref(0)
const cropH        = ref(0)
const aspectLocked = ref(false)
let   _ar          = 1   // locked aspect ratio (w / h in natural px)

// ─── Tools ────────────────────────────────────────────────────────────────────

const TOOLS: { id: EditorTool; label: string; icon: string }[] = [
  { id: 'select', label: 'Seleccionar', icon: 'ui/cursor' },
  { id: 'crop',   label: 'Recortar',    icon: 'ui/crop' },
]

function onToolClick(id: EditorTool) {
  activeTool.value = id
  if (id === 'crop') cropDirty.value = true
}

// ─── Rotation ─────────────────────────────────────────────────────────────────

function rotateCW()  { rotation.value = (rotation.value + 90) % 360 }
function rotateCCW() { rotation.value = ((rotation.value - 90) + 360) % 360 }

// ─── Crop ─────────────────────────────────────────────────────────────────────

function setCropWidth(px: number) {
  if (px < 1) return
  cropW.value = px
  if (aspectLocked.value) cropH.value = Math.round(px / _ar)
}

function setCropHeight(px: number) {
  if (px < 1) return
  cropH.value = px
  if (aspectLocked.value) cropW.value = Math.round(px * _ar)
}

function toggleAspectLock() {
  aspectLocked.value = !aspectLocked.value
  if (aspectLocked.value && cropH.value > 0) _ar = cropW.value / cropH.value
}

// ─── Filters ──────────────────────────────────────────────────────────────────

const FILTERS: { id: keyof FilterState; label: string }[] = [
  { id: 'brightness',  label: 'Brillo' },
  { id: 'contrast',    label: 'Contraste' },
  { id: 'saturation',  label: 'Saturación' },
  { id: 'shadows',     label: 'Sombras' },
  { id: 'sharpness',   label: 'Nitidez' },
  { id: 'temperature', label: 'Temperatura' },
]

const ZERO: FilterState = { brightness: 0, contrast: 0, saturation: 0, shadows: 0, sharpness: 0, temperature: 0 }
const filterValues = reactive<FilterState>({ ...ZERO })

function persistFilters() {
  if (item.value) store.setFilters(item.value.id, { ...filterValues })
}

function resetFilters() {
  Object.assign(filterValues, ZERO)
  if (item.value) store.setFilters(item.value.id, { ...filterValues })
}

// ─── CSS preview ──────────────────────────────────────────────────────────────

const previewStyle = computed(() => {
  const parts: string[] = []
  if (filterValues.brightness !== 0) parts.push(`brightness(${100 + filterValues.brightness}%)`)
  if (filterValues.contrast   !== 0) parts.push(`contrast(${100 + filterValues.contrast}%)`)
  if (filterValues.saturation !== 0) parts.push(`saturate(${100 + filterValues.saturation}%)`)
  return {
    transform: rotation.value ? `rotate(${rotation.value}deg)` : undefined,
    filter:    parts.length ? parts.join(' ') : undefined,
  }
})

// ─── Save / close ─────────────────────────────────────────────────────────────

function handleSave() {
  if (item.value) {
    store.setFilters(item.value.id, { ...filterValues })
    store.setRotation(item.value.id, rotation.value)
    if (cropDirty.value) {
      store.setCrop(item.value.id, { x: 0, y: 0, width: cropW.value, height: cropH.value })
    }
  }
  emit('close')
}

function handleClose() {
  emit('close')
}

// ─── Lifecycle ────────────────────────────────────────────────────────────────

watch(
  () => props.isOpen,
  (open) => {
    if (open && item.value) {
      Object.assign(filterValues, item.value.filters)
      rotation.value     = item.value.rotation ?? 0
      cropW.value        = item.value.crop?.width  ?? item.value.source.naturalWidth
      cropH.value        = item.value.crop?.height ?? item.value.source.naturalHeight
      aspectLocked.value = false
      cropDirty.value    = false
      activeTool.value   = 'select'
    }
  },
)
</script>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.2s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}
</style>
