<template>
  <AppHeader>
    <template #left>
      <div class="flex items-center gap-3">
        <div class="w-6 h-6 rounded-md bg-brand-500 shadow-lg shadow-brand-500/40 shrink-0" />
        <span class="text-sm font-semibold text-white tracking-tight">Image Studio</span>
      </div>
    </template>

    <template #right>
      <span
        v-if="store.items.value.length > 0"
        class="px-2.5 py-1 rounded-full text-xs font-medium bg-white/8 text-white/45 tabular-nums"
      >
        {{ store.items.value.length }} {{ store.items.value.length === 1 ? 'imagen' : 'imágenes' }}
      </span>
      <span v-else class="text-xs text-white/20">Sin imágenes</span>
    </template>
  </AppHeader>
</template>

<script setup lang="ts">
import { useImageStore } from '@/composables/imageStudio/useImageStore'
import AppHeader from '@/components/AppHeader.vue'

const store = useImageStore()
</script>
