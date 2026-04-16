<template>
  <div class="mb-8">
    <div class="flex items-baseline gap-3 mb-3">
      <p class="text-xs font-semibold uppercase tracking-widest text-slate-400 dark:text-white/30">
        {{ name }}
      </p>
      <p class="text-xs text-slate-400 dark:text-white/20">{{ subtitle }}</p>
    </div>
    <div class="grid grid-cols-11 gap-1.5">
      <div
        v-for="s in shades"
        :key="s.shade"
        class="group flex flex-col items-center gap-1.5 cursor-default"
        :title="s.hex"
      >
        <div
          class="w-full aspect-square rounded-lg ring-1 ring-black/5 dark:ring-white/5
                 transition-transform group-hover:scale-110 group-hover:shadow-lg"
          :style="{ background: s.hex }"
        />
        <span class="text-[10px] font-mono text-slate-400 dark:text-white/30">
          {{ s.shade }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface ColorShade { shade: number; hex: string }

defineProps<{
  name:     string
  subtitle: string
  shades:   ColorShade[]
}>()
</script>
