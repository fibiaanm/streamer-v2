<template>
  <div class="relative group">
    <button
      class="w-full flex flex-col gap-2 p-3.5 rounded-xl border border-white/6 bg-white/2 hover:bg-white/5 hover:border-white/10 transition-all text-left cursor-pointer"
      @click="$emit('select')"
    >
      <div class="w-7 h-7 rounded-lg bg-white/6 flex items-center justify-center text-[10px] font-bold text-white/35 uppercase">
        {{ workspace.name[0] }}
      </div>
      <span class="text-xs font-medium text-white/70 truncate pr-4">{{ workspace.name }}</span>
    </button>
    <button
      class="absolute top-2 right-2 w-5 h-5 flex items-center justify-center rounded-md opacity-0 group-hover:opacity-100 text-white/30 hover:text-white/70 hover:bg-white/8 transition-all cursor-pointer"
      @click.stop="$emit('settings')"
    >
      <AppIcon name="ui/more-horizontal" size="xs" />
    </button>
  </div>
</template>

<script setup lang="ts">
import AppIcon from '@/components/AppIcon.vue'

defineProps<{
  workspace: { id: string; name: string }
}>()

defineEmits<{ select: []; settings: [] }>()
</script>
