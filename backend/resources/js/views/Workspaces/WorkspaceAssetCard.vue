<template>
  <button class="group flex flex-col gap-1.5 text-left cursor-pointer" @click="$emit('select')">
    <div :class="['aspect-square rounded-xl border border-white/6 flex items-center justify-center transition-all group-hover:border-white/12', asset.color]">
      <AppIcon :name="iconName" size="sm" class="text-white/30" />
    </div>
    <span class="text-[10px] text-white/40 truncate leading-tight">{{ asset.name }}</span>
    <span class="text-[9px] text-white/20">{{ asset.size }}</span>
  </button>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import AppIcon from '@/components/AppIcon.vue'

const props = defineProps<{
  asset: {
    id:    string
    name:  string
    type:  'image' | 'video' | 'doc'
    size:  string
    color: string
  }
}>()

defineEmits<{ select: [] }>()

const iconName = computed(() => ({
  image: 'ui/image',
  video: 'ui/video',
  doc:   'ui/file',
}[props.asset.type]))
</script>
