<template>
  <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors">
    <slot name="avatar" />

    <div class="flex-1 min-w-0">
      <slot name="text">
        <p class="text-sm text-white/80 truncate">{{ name }}</p>
        <p class="text-xs text-white/30 truncate">{{ sub }}</p>
      </slot>
    </div>

    <slot name="badge" />

    <slot name="actions">
      <div class="w-6 shrink-0" />
    </slot>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  name?: string
  sub?:  string
}>()
</script>
