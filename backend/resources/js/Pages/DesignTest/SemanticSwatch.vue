<template>
  <div class="flex items-center gap-3 px-4 py-3 rounded-xl
              bg-white/70 dark:bg-white/5
              border border-slate-200 dark:border-white/8">
    <div
      class="w-9 h-9 rounded-lg ring-1 ring-black/10 dark:ring-white/10 shrink-0"
      :style="{ background: hex }"
    />
    <div>
      <p class="text-sm font-semibold text-slate-800 dark:text-white/80">{{ label }}</p>
      <p class="text-xs font-mono text-slate-400 dark:text-white/30">{{ hex }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{ label: string; hex: string }>()
</script>
