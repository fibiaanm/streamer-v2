<template>
  <button
    :class="[
      'w-full flex items-center gap-2.5 px-3 py-2 text-left transition-colors cursor-pointer',
      isActive
        ? 'bg-white/8 text-white/90'
        : 'text-white/50 hover:text-white/80 hover:bg-white/4',
    ]"
    @click="$emit('select')"
  >
    <div class="w-5 h-5 shrink-0 rounded-md bg-white/8 flex items-center justify-center text-[9px] font-bold text-white/40 uppercase">
      {{ workspace.name[0] }}
    </div>
    <span class="text-xs font-medium truncate">{{ workspace.name }}</span>
  </button>
</template>

<script setup lang="ts">
defineProps<{
  workspace: { id: string; name: string }
  isActive:  boolean
}>()

defineEmits<{ select: [] }>()
</script>
