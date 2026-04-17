<template>
  <div class="glass rounded-xl p-3.5 space-y-3 relative">
    <!-- Label editable + remove -->
    <div class="flex items-center gap-2">
      <input
        v-model="local.label"
        class="flex-1 min-w-0 text-xs font-semibold text-white/80 bg-transparent outline-none
               border-b border-transparent hover:border-white/15 focus:border-brand-400/60
               transition-colors pb-0.5 truncate"
        placeholder="Nombre de la config…"
        @blur="pushUpdate"
      />
      <button
        v-if="canRemove"
        class="shrink-0 w-5 h-5 rounded-md flex items-center justify-center
               text-white/20 hover:text-rose-400 hover:bg-rose-500/10 transition-colors cursor-pointer"
        title="Eliminar"
        @click="emit('remove')"
      >
        <AppIcon name="ui/x" size="xs" />
      </button>
    </div>

    <!-- Format + Resize in one row -->
    <div class="flex items-center gap-2">
      <!-- Format -->
      <div class="relative flex-none w-[72px]">
        <select
          v-model="local.format"
          class="w-full bg-white/5 border border-white/10 rounded-lg text-xs text-white/70
                 pl-2.5 pr-5 py-1 outline-none focus:border-brand-400/50 cursor-pointer appearance-none"
          @change="pushUpdate"
        >
          <option value="webp">WebP</option>
          <option value="jpeg">JPEG</option>
          <option value="png">PNG</option>
        </select>
        <svg class="absolute right-1.5 top-1/2 -translate-y-1/2 w-2.5 h-2.5 text-white/25 pointer-events-none" viewBox="0 0 10 10" fill="none" stroke="currentColor" stroke-width="1.8">
          <path d="M2 3.5l3 3 3-3" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
      </div>

      <!-- Resize mode -->
      <div class="relative flex-1">
        <select
          v-model="local.resize.mode"
          class="w-full bg-white/5 border border-white/10 rounded-lg text-xs text-white/70
                 pl-2.5 pr-5 py-1 outline-none focus:border-brand-400/50 cursor-pointer appearance-none"
          @change="pushUpdate"
        >
          <option value="original">Original</option>
          <option value="width">Ancho</option>
          <option value="height">Alto</option>
        </select>
        <svg class="absolute right-1.5 top-1/2 -translate-y-1/2 w-2.5 h-2.5 text-white/25 pointer-events-none" viewBox="0 0 10 10" fill="none" stroke="currentColor" stroke-width="1.8">
          <path d="M2 3.5l3 3 3-3" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
      </div>

      <!-- Px value -->
      <input
        v-if="local.resize.mode !== 'original'"
        v-model.number="local.resize.value"
        type="number" min="1" max="10000"
        class="w-20 flex-none bg-white/5 border border-white/10 rounded-lg text-xs text-white/70
               px-2.5 py-1 outline-none focus:border-brand-400/50 text-right
               [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
        :placeholder="local.resize.mode === 'width' ? 'ancho' : 'alto'"
        @blur="pushUpdate"
      />
    </div>

    <!-- Quality -->
    <div class="space-y-1">
      <div class="flex items-center justify-between">
        <span class="text-[10px] text-white/30">Calidad</span>
        <span class="text-[10px] font-mono text-brand-400">{{ local.quality }}%</span>
      </div>
      <input
        v-model.number="local.quality"
        type="range" min="1" max="100"
        class="w-full h-1 rounded-full cursor-pointer appearance-none bg-white/10"
        :style="{ accentColor: '#0ea5e9' }"
        @change="pushUpdate"
      />
    </div>

    <!-- Output preview -->
    <p class="text-[10px] font-mono text-white/20 truncate border-t border-white/6 pt-2.5">
      → {{ outputName }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { reactive, computed } from 'vue'
import type { ExportConfig } from '@/types/imageStudio'
import AppIcon from '@/components/AppIcon.vue'

const props = defineProps<{
  config: ExportConfig
  itemName: string
  canRemove: boolean
}>()

const emit = defineEmits<{
  update: [config: ExportConfig]
  remove: []
}>()

const local = reactive<ExportConfig>({
  ...props.config,
  resize: { ...props.config.resize },
})

function pushUpdate() {
  emit('update', { ...local, resize: { ...local.resize } })
}

const outputName = computed(() => {
  const { format, resize } = local
  const base = local.label?.trim() || props.itemName
  if (resize.mode === 'original') return `${base}@original.${format}`
  const suffix = resize.mode === 'width'
    ? `${resize.value ?? '?'}w`
    : `${resize.value ?? '?'}h`
  return `${base}@${suffix}.${format}`
})
</script>
