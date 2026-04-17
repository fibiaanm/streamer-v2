<template>
  <button
    class="flex-1 py-2 text-xs font-medium rounded-xl cursor-pointer transition-colors
           bg-white/6 backdrop-blur-2xl backdrop-saturate-200 border border-white/10 shadow-glass
           text-white/80"
    :class="danger ? 'hover:text-rose-400' : 'hover:text-white'"
    @click="emit('click')"
  >
    <slot />
  </button>
</template>

<script setup lang="ts">
defineProps<{ danger?: boolean }>()
const emit = defineEmits<{ click: [] }>()
</script>
