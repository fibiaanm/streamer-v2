<template>
  <div
    class="shrink-0 relative h-48 cursor-pointer group overflow-hidden border-b border-white/8"
    @click="emit('openEditor')"
  >
    <!-- Grid background -->
    <div class="absolute inset-0" :style="GRID_BG" />

    <!-- Image or gradient -->
    <img
      v-if="item.source.dataUrl"
      :src="item.source.dataUrl"
      class="absolute inset-0 w-full h-full object-contain"
    />
    <div
      v-else
      class="absolute inset-0"
      :style="{ background: `linear-gradient(135deg, ${colors[0]} 0%, ${colors[1]} 100%)`, opacity: '0.75' }"
    />

    <!-- Open editor hover overlay -->
    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/50 transition-colors duration-200 flex items-center justify-center gap-2">
      <AppIcon name="ui/open-editor" size="sm" class="text-white opacity-0 group-hover:opacity-100 transition-opacity duration-200" />
      <span class="text-sm font-medium text-white opacity-0 group-hover:opacity-100 transition-opacity duration-200 tracking-wide">
        Abrir editor
      </span>
    </div>

    <!-- Bottom mask: thumbnail + name + info -->
    <div
      class="absolute bottom-0 inset-x-0 px-4 py-2.5 backdrop-blur-md bg-black/50
             flex items-center gap-3"
      @click.stop
    >
      <!-- Thumbnail -->
      <div
        class="w-8 h-8 rounded-md overflow-hidden shrink-0 relative"
        :style="GRID_BG"
      >
        <img
          v-if="item.source.dataUrl"
          :src="item.source.dataUrl"
          class="absolute inset-0 w-full h-full object-cover"
        />
        <div
          v-else
          class="absolute inset-0"
          :style="{ background: `linear-gradient(135deg, ${colors[0]} 0%, ${colors[1]} 100%)`, opacity: '0.82' }"
        />
      </div>

      <!-- Name + metadata -->
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-1.5 group/name">
          <p
            v-if="!editingName"
            class="text-xs font-semibold text-white/90 truncate cursor-text leading-tight"
            :title="item.name"
            @click.stop="startNameEdit"
          >
            {{ item.name }}
          </p>
          <input
            v-else
            ref="nameInputEl"
            v-model="newName"
            class="text-xs font-semibold text-white bg-transparent outline-none w-full
                   border-b border-brand-400/70 pb-0.5 leading-tight"
            @blur="saveName"
            @keyup.enter="saveName"
            @keyup.escape="cancelName"
          />
          <button
            v-if="!editingName"
            class="opacity-0 group-hover/name:opacity-100 transition-opacity shrink-0
                   w-4 h-4 rounded flex items-center justify-center
                   text-white/30 hover:text-white/70 cursor-pointer"
            @click.stop="startNameEdit"
          >
            <AppIcon name="ui/pencil" size="xs" />
          </button>
        </div>
        <p class="text-[10px] text-white/40 font-mono mt-0.5">
          {{ item.source.naturalWidth }} × {{ item.source.naturalHeight }}
          <span class="mx-1 text-white/20">·</span>
          {{ formatBytes(item.source.sizeBytes) }}
          <span class="mx-1 text-white/20">·</span>
          {{ aspectRatio }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, nextTick } from 'vue'
import { useImageStore } from '@/composables/imageStudio/useImageStore'
import AppIcon from '@/components/AppIcon.vue'

const store = useImageStore()
const item  = store.activeItem

const emit = defineEmits<{ openEditor: [] }>()

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
  if (!item.value) return GRADIENTS[0]
  const sum = [...item.value.id].reduce((a, c) => a + c.charCodeAt(0), 0)
  return GRADIENTS[sum % GRADIENTS.length]
})

const editingName = ref(false)
const newName     = ref('')
const nameInputEl = ref<HTMLInputElement>()

function startNameEdit() {
  if (!item.value) return
  newName.value     = item.value.name
  editingName.value = true
  nextTick(() => nameInputEl.value?.select())
}

function saveName() {
  const name = newName.value.trim()
  if (name && item.value && name !== item.value.name) store.rename(item.value.id, name)
  editingName.value = false
}

function cancelName() { editingName.value = false }

const aspectRatio = computed(() => {
  if (!item.value) return ''
  const gcd = (a: number, b: number): number => b === 0 ? a : gcd(b, a % b)
  const { naturalWidth: w, naturalHeight: h } = item.value.source
  const d = gcd(w, h)
  return `${w / d}:${h / d}`
})

function formatBytes(bytes: number): string {
  if (bytes < 1024)      return `${bytes} B`
  if (bytes < 1_048_576) return `${(bytes / 1024).toFixed(0)} KB`
  return `${(bytes / 1_048_576).toFixed(1)} MB`
}
</script>
