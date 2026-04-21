<template>
  <nav class="flex items-center gap-2 text-xs text-white/40 min-w-0 flex-1 overflow-hidden">
    <span
      v-for="(crumb, i) in crumbs"
      :key="crumb.id"
      class="flex items-center gap-2 min-w-0"
    >
      <span
        :class="[
          'truncate transition-colors',
          i < crumbs.length - 1
            ? 'hover:text-white/60 cursor-pointer shrink-0'
            : 'text-white/80 font-medium',
        ]"
        @click="i < crumbs.length - 1 && $emit('navigate', crumb.id)"
      >{{ crumb.name }}</span>
      <span v-if="i < crumbs.length - 1" class="text-white/20 shrink-0">/</span>
    </span>
  </nav>
</template>

<script setup lang="ts">
defineProps<{
  crumbs: { id: string; name: string }[]
}>()

defineEmits<{ navigate: [id: string] }>()
</script>
