<template>
  <button
    class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm transition-colors w-full text-left cursor-pointer"
    :class="isActive
      ? 'bg-white/8 text-white/90'
      : 'text-white/45 hover:text-white/70 hover:bg-white/4'"
    @click="emit('select')"
  >
    <AppIcon :name="icon" size="sm" class="shrink-0" :class="isActive ? 'text-white/60' : 'text-white/30'" />
    <span class="truncate">{{ label }}</span>
  </button>
</template>

<script setup lang="ts">
import AppIcon from '@/components/AppIcon.vue'

defineProps<{
  icon: string
  label: string
  isActive: boolean
}>()

const emit = defineEmits<{ select: [] }>()
</script>
