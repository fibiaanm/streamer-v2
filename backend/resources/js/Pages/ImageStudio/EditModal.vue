<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div
        v-if="isOpen && item"
        class="fixed inset-0 z-[200] flex items-center justify-center p-4"
      >
        <div class="absolute inset-0 bg-black/75 backdrop-blur-sm" @click="emit('close')" />

        <div class="relative glass rounded-2xl w-full max-w-6xl flex flex-col overflow-hidden" style="height: 88vh">

          <!-- Header -->
          <div class="flex items-center justify-between px-5 py-3.5 border-b border-white/8 shrink-0">
            <div class="flex items-center gap-3">
              <button
                class="flex items-center gap-1.5 text-xs text-white/35 hover:text-white/70 transition-colors cursor-pointer"
                @click="emit('close')"
              >
                <svg class="w-3.5 h-3.5" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.8">
                  <path d="M9 2L4 7l5 5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                Volver
              </button>
              <div class="w-px h-4 bg-white/10" />
              <span class="text-sm font-semibold text-white/85">{{ item.name }}</span>
            </div>
            <AppButton variant="primary" size="sm" @click="emit('close')">
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
                @click="activeTool = tool.id"
              >
                <AppIcon :name="tool.icon" size="sm" />
              </button>

              <div class="w-6 border-t border-white/8 my-1.5" />

              <button
                title="Rotar izquierda"
                class="w-9 h-9 rounded-xl flex items-center justify-center
                       text-white/25 hover:text-white/60 hover:bg-white/5 transition-colors cursor-pointer"
              >
                <AppIcon name="ui/rotate-ccw" size="sm" />
              </button>
              <button
                title="Rotar derecha"
                class="w-9 h-9 rounded-xl flex items-center justify-center
                       text-white/25 hover:text-white/60 hover:bg-white/5 transition-colors cursor-pointer"
              >
                <AppIcon name="ui/rotate-cw" size="sm" />
              </button>
            </div>

            <!-- Canvas area -->
            <div class="flex-1 flex items-center justify-center overflow-hidden bg-black/30">
              <div
                class="rounded-xl overflow-hidden shadow-2xl border border-white/5 relative"
                :style="{ width: '640px', height: '360px', ...GRID_BG }"
              >
                <!-- Gradient preview -->
                <div
                  class="absolute inset-0"
                  :style="{ background: `linear-gradient(135deg, ${colors[0]} 0%, ${colors[1]} 100%)`, opacity: '0.75' }"
                />

                <!-- Crop overlay -->
                <div
                  v-if="activeTool === 'crop'"
                  class="absolute inset-8 border-2 border-white/70 rounded"
                  style="box-shadow: 0 0 0 9999px rgba(0,0,0,0.55)"
                >
                  <div class="absolute inset-x-0 top-1/3 h-px bg-white/20" />
                  <div class="absolute inset-x-0 top-2/3 h-px bg-white/20" />
                  <div class="absolute inset-y-0 left-1/3 w-px bg-white/20" />
                  <div class="absolute inset-y-0 left-2/3 w-px bg-white/20" />
                  <div class="absolute top-0 left-0 w-4 h-4 border-t-2 border-l-2 border-white -mt-px -ml-px" />
                  <div class="absolute top-0 right-0 w-4 h-4 border-t-2 border-r-2 border-white -mt-px -mr-px" />
                  <div class="absolute bottom-0 left-0 w-4 h-4 border-b-2 border-l-2 border-white -mb-px -ml-px" />
                  <div class="absolute bottom-0 right-0 w-4 h-4 border-b-2 border-r-2 border-white -mb-px -mr-px" />
                </div>

                <!-- Dims label -->
                <div class="absolute bottom-2 left-1/2 -translate-x-1/2">
                  <span class="px-2 py-0.5 rounded bg-black/50 backdrop-blur-sm text-[10px] font-mono text-white/50">
                    {{ item.source.naturalWidth }} × {{ item.source.naturalHeight }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Filter panel -->
            <div class="w-60 shrink-0 border-l border-white/8 flex flex-col overflow-hidden">
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
import { ref, reactive, computed } from 'vue'
import type { ImageItem } from '@/types/imageStudio'
import AppButton from '@/components/AppButton.vue'
import AppIcon   from '@/components/AppIcon.vue'

type ToolId = 'select' | 'crop'

const props = defineProps<{ isOpen: boolean; item: ImageItem | null }>()
const emit  = defineEmits<{ close: [] }>()

const activeTool = ref<ToolId>('select')

// ─── Brand grid ───────────────────────────────────────────────────────────────

const GRID_BG = {
  backgroundColor: '#080d1c',
  backgroundImage: [
    'linear-gradient(rgba(14,165,233,.07) 1px, transparent 1px)',
    'linear-gradient(90deg, rgba(14,165,233,.07) 1px, transparent 1px)',
  ].join(', '),
  backgroundSize: '24px 24px',
}

// ─── Gradient ─────────────────────────────────────────────────────────────────

const GRADIENTS: [string, string][] = [
  ['#0ea5e9', '#0369a1'], ['#06b6d4', '#0e7490'], ['#8b5cf6', '#6d28d9'],
  ['#10b981', '#059669'], ['#f59e0b', '#d97706'], ['#f43f5e', '#be123c'],
  ['#6366f1', '#4338ca'], ['#ec4899', '#be185d'],
]

const colors = computed((): [string, string] => {
  if (!props.item) return GRADIENTS[0]
  const sum = [...props.item.id].reduce((a, c) => a + c.charCodeAt(0), 0)
  return GRADIENTS[sum % GRADIENTS.length]
})

// ─── Tools ────────────────────────────────────────────────────────────────────

const TOOLS: { id: ToolId; label: string; icon: string }[] = [
  { id: 'select', label: 'Seleccionar', icon: 'ui/cursor' },
  { id: 'crop',   label: 'Recortar',    icon: 'ui/crop' },
]

// ─── Filters ──────────────────────────────────────────────────────────────────

const FILTERS = [
  { id: 'brightness',  label: 'Brillo' },
  { id: 'contrast',    label: 'Contraste' },
  { id: 'saturation',  label: 'Saturación' },
  { id: 'shadows',     label: 'Sombras' },
  { id: 'sharpness',   label: 'Nitidez' },
  { id: 'temperature', label: 'Temperatura' },
]

const filterValues = reactive<Record<string, number>>(
  Object.fromEntries(FILTERS.map(f => [f.id, 0]))
)

function resetFilters() {
  FILTERS.forEach(f => { filterValues[f.id] = 0 })
}
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
