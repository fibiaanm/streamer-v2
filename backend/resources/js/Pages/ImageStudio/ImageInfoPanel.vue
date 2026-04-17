<template>
  <div class="h-full flex flex-col overflow-hidden">
    <!-- Preview: brand grid + gradient/image on top -->
    <div class="p-5 shrink-0">
      <div class="rounded-2xl overflow-hidden relative w-full" style="aspect-ratio: 16/9" :style="GRID_BG">
        <img
          v-if="item.source.dataUrl"
          :src="item.source.dataUrl"
          :alt="item.name"
          class="absolute inset-0 w-full h-full object-contain"
        />
        <div
          v-else
          class="absolute inset-0"
          :style="{ background: `linear-gradient(135deg, ${colors[0]} 0%, ${colors[1]} 100%)`, opacity: '0.82' }"
        />
      </div>

      <!-- Editable name -->
      <div class="mt-3 flex items-center gap-2 group/name">
        <div class="flex-1 min-w-0">
          <p
            v-if="!editingName"
            class="text-base font-semibold text-white/90 truncate cursor-text"
            :title="item.name"
            @click="startNameEdit"
          >
            {{ item.name }}
          </p>
          <input
            v-else
            ref="nameInputEl"
            v-model="newName"
            class="text-base font-semibold text-white bg-transparent outline-none w-full
                   border-b border-brand-400/70 pb-0.5"
            @blur="saveName"
            @keyup.enter="saveName"
            @keyup.escape="cancelName"
          />
        </div>
        <button
          v-if="!editingName"
          class="opacity-0 group-hover/name:opacity-100 transition-opacity shrink-0
                 w-6 h-6 rounded-md flex items-center justify-center
                 text-white/30 hover:text-white/70 hover:bg-white/8 cursor-pointer"
          title="Renombrar"
          @click="startNameEdit"
        >
          <AppIcon name="ui/pencil" size="xs" />
        </button>
      </div>
      <p class="text-xs text-white/30 mt-0.5 font-mono">{{ formatBytes(item.source.sizeBytes) }}</p>
    </div>

    <div class="h-px bg-white/6 mx-5 shrink-0" />

    <!-- Metadata -->
    <div class="flex-1 overflow-y-auto px-5 py-4">
      <p class="text-[10px] font-semibold uppercase tracking-widest text-white/25 mb-3">Dimensiones</p>
      <div class="grid grid-cols-2 gap-2 mb-5">
        <div class="glass rounded-xl px-3 py-2.5">
          <p class="text-[10px] text-white/30 mb-0.5">Ancho</p>
          <p class="text-sm font-semibold text-white/80 font-mono">
            {{ item.source.naturalWidth }}<span class="text-xs text-white/30 font-sans"> px</span>
          </p>
        </div>
        <div class="glass rounded-xl px-3 py-2.5">
          <p class="text-[10px] text-white/30 mb-0.5">Alto</p>
          <p class="text-sm font-semibold text-white/80 font-mono">
            {{ item.source.naturalHeight }}<span class="text-xs text-white/30 font-sans"> px</span>
          </p>
        </div>
        <div class="glass rounded-xl px-3 py-2.5">
          <p class="text-[10px] text-white/30 mb-0.5">Peso</p>
          <p class="text-sm font-semibold text-white/80">{{ formatBytes(item.source.sizeBytes) }}</p>
        </div>
        <div class="glass rounded-xl px-3 py-2.5">
          <p class="text-[10px] text-white/30 mb-0.5">Ratio</p>
          <p class="text-sm font-semibold text-white/80 font-mono">{{ aspectRatio }}</p>
        </div>
      </div>

      <template v-if="item.crop">
        <p class="text-[10px] font-semibold uppercase tracking-widest text-white/25 mb-3">Recorte aplicado</p>
        <div class="glass rounded-xl px-3 py-2.5">
          <p class="text-xs text-white/60 font-mono">
            {{ item.crop.width }} × {{ item.crop.height }} px
            <span class="text-white/25"> en ({{ item.crop.x }}, {{ item.crop.y }})</span>
          </p>
        </div>
      </template>
    </div>

    <div class="h-px bg-white/6 mx-5 shrink-0" />

    <div class="p-5 shrink-0">
      <AppButton variant="primary" class="w-full" @click="emit('openEditor')">
        Abrir Editor
      </AppButton>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, nextTick } from 'vue'
import type { ImageItem } from '@/types/imageStudio'
import AppButton from '@/components/AppButton.vue'
import AppIcon   from '@/components/AppIcon.vue'

const props = defineProps<{ item: ImageItem }>()
const emit  = defineEmits<{
  openEditor: []
  rename: [name: string]
}>()

const GRID_BG = {
  backgroundColor: '#080d1c',
  backgroundImage: [
    'linear-gradient(rgba(14,165,233,.07) 1px, transparent 1px)',
    'linear-gradient(90deg, rgba(14,165,233,.07) 1px, transparent 1px)',
  ].join(', '),
  backgroundSize: '24px 24px',
}

const GRADIENTS: [string, string][] = [
  ['#0ea5e9', '#0369a1'], ['#06b6d4', '#0e7490'], ['#8b5cf6', '#6d28d9'],
  ['#10b981', '#059669'], ['#f59e0b', '#d97706'], ['#f43f5e', '#be123c'],
  ['#6366f1', '#4338ca'], ['#ec4899', '#be185d'],
]

const colors = computed((): [string, string] => {
  const sum = [...props.item.id].reduce((a, c) => a + c.charCodeAt(0), 0)
  return GRADIENTS[sum % GRADIENTS.length]
})

const editingName = ref(false)
const newName     = ref('')
const nameInputEl = ref<HTMLInputElement>()

function startNameEdit() {
  newName.value     = props.item.name
  editingName.value = true
  nextTick(() => nameInputEl.value?.select())
}

function saveName() {
  const name = newName.value.trim()
  if (name && name !== props.item.name) emit('rename', name)
  editingName.value = false
}

function cancelName() { editingName.value = false }

const aspectRatio = computed(() => {
  const gcd = (a: number, b: number): number => b === 0 ? a : gcd(b, a % b)
  const { naturalWidth: w, naturalHeight: h } = props.item.source
  const d = gcd(w, h)
  return `${w / d}:${h / d}`
})

function formatBytes(bytes: number): string {
  if (bytes < 1024)      return `${bytes} B`
  if (bytes < 1_048_576) return `${(bytes / 1024).toFixed(0)} KB`
  return `${(bytes / 1_048_576).toFixed(1)} MB`
}
</script>
