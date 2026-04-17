<template>
  <div class="flex-1 overflow-y-auto px-5 py-4 space-y-3">
    <button
      class="w-full flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl
             border border-dashed border-white/10
             hover:border-brand-400/40 hover:bg-brand-500/5
             transition-all duration-200 cursor-pointer group"
      @click="store.addExportConfig(store.activeItemId.value!)"
    >
      <div
        class="w-6 h-6 rounded-lg border border-dashed border-white/15
               group-hover:border-brand-400/40 flex items-center justify-center
               shrink-0 transition-colors"
      >
        <AppIcon name="ui/plus" size="xs" class="text-white/25 group-hover:text-brand-400 transition-colors" />
      </div>
      <span class="text-xs text-white/30 group-hover:text-brand-400 transition-colors">
        Nueva configuración
      </span>
    </button>

    <TransitionGroup name="cfg" tag="div" class="grid grid-cols-1 xl:grid-cols-2 gap-3">
      <ExportConfigCard
        v-for="config in store.activeItem.value!.exportConfigs"
        :key="config.id"
        :config="config"
        :item-id="store.activeItemId.value!"
        :can-remove="store.activeItem.value!.exportConfigs.length > 1"
      />
    </TransitionGroup>
  </div>
</template>

<script setup lang="ts">
import { useImageStore } from '@/composables/imageStudio/useImageStore'
import ExportConfigCard from './ExportConfigCard.vue'
import AppIcon          from '@/components/AppIcon.vue'

const store = useImageStore()
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
