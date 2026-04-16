<template>
  <div class="flex items-center gap-4 mb-8">
    <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white whitespace-nowrap">
      {{ label }}
    </h2>
    <div class="flex-1 h-px bg-gradient-to-r from-slate-200 dark:from-white/10 to-transparent" />
  </div>
</template>

<script setup lang="ts">
defineProps<{ label: string }>()
</script>
