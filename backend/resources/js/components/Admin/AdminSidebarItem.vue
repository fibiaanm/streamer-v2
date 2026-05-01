<template>
  <RouterLink
    :to="to"
    class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm transition-colors w-full"
    :class="isActive
      ? 'bg-white/8 text-white/90'
      : 'text-white/45 hover:text-white/70 hover:bg-white/4'"
  >
    <AppIcon
      :name="icon"
      size="sm"
      class="shrink-0"
      :class="isActive ? 'text-white/60' : 'text-white/30'"
    />
    <span class="truncate">{{ label }}</span>
  </RouterLink>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AppIcon from '@/components/AppIcon.vue'

const props = defineProps<{ to: string; icon: string; label: string }>()
const route    = useRoute()
const isActive = computed(() => route.path === props.to)
</script>
