<template>
  <!-- Empty state -->
  <div
    v-if="!item"
    class="h-full flex flex-col items-center justify-center text-center gap-3 p-8"
  >
    <div class="w-11 h-11 rounded-xl bg-white/5 border border-white/8 flex items-center justify-center">
      <AppIcon name="ui/download" size="md" class="text-white/20" />
    </div>
    <p class="text-xs text-white/25 max-w-[16rem]">
      Selecciona una imagen para configurar sus opciones de exportación
    </p>
  </div>

  <!-- Config panel -->
  <div v-else class="h-full flex flex-col overflow-hidden">
    <!-- Header -->
    <div class="px-5 py-3.5 border-b border-white/6 shrink-0">
      <p class="text-sm font-semibold text-white/85 truncate">{{ item.name }}</p>
      <p class="text-xs text-white/30 mt-0.5">
        {{ item.exportConfigs.length }}
        {{ item.exportConfigs.length === 1 ? 'configuración' : 'configuraciones' }}
      </p>
    </div>

    <div class="flex-1 overflow-y-auto px-5 py-4 space-y-3">
      <!-- Add config button: compact, full-width, anchored at top -->
      <button
        class="w-full flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl
               border border-dashed border-white/10
               hover:border-brand-400/40 hover:bg-brand-500/5
               transition-all duration-200 cursor-pointer group"
        @click="emit('addConfig')"
      >
        <div
          class="w-6 h-6 rounded-lg border border-dashed border-white/15
                 group-hover:border-brand-400/40 flex items-center justify-center
                 shrink-0 transition-colors"
        >
          <svg class="w-3 h-3 text-white/25 group-hover:text-brand-400 transition-colors" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M6 2v8M2 6h8" stroke-linecap="round" />
          </svg>
        </div>
        <span class="text-xs text-white/30 group-hover:text-brand-400 transition-colors">
          Nueva configuración
        </span>
      </button>

      <!-- Config cards with enter/move animation -->
      <TransitionGroup name="cfg" tag="div" class="grid grid-cols-1 xl:grid-cols-2 gap-3">
        <ExportConfigCard
          v-for="config in item.exportConfigs"
          :key="config.id"
          :config="config"
          :item-name="item.name"
          :can-remove="item.exportConfigs.length > 1"
          @update="(c) => emit('updateConfig', config.id, c)"
          @remove="emit('removeConfig', config.id)"
        />
      </TransitionGroup>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { ImageItem, ExportConfig } from '@/types/imageStudio'
import ExportConfigCard from './ExportConfigCard.vue'
import AppIcon          from '@/components/AppIcon.vue'

defineProps<{ item: ImageItem | null }>()

const emit = defineEmits<{
  addConfig: []
  removeConfig: [configId: string]
  updateConfig: [configId: string, config: ExportConfig]
}>()
</script>

<style scoped>
.cfg-enter-active {
  transition: opacity 0.22s ease, transform 0.22s ease;
}
.cfg-enter-from {
  opacity: 0;
  transform: translateY(-6px) scale(0.98);
}
.cfg-leave-active {
  transition: opacity 0.18s ease;
}
.cfg-leave-to {
  opacity: 0;
}
.cfg-move {
  transition: transform 0.28s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
