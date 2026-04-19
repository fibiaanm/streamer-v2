<template>
  <button
    class="w-full flex items-center gap-3 px-4 py-2 text-sm transition-colors cursor-pointer text-left"
    :class="variantClasses"
    v-bind="$attrs"
    @click="closeDropdown"
  >
    <AppIcon
      v-if="icon"
      :name="icon"
      size="sm"
      class="shrink-0"
      :class="iconClass"
    />
    <span v-else-if="$slots.icon" class="shrink-0 flex items-center" :class="iconClass">
      <slot name="icon" />
    </span>
    <span class="truncate">
      <slot />
    </span>
  </button>
</template>

<script setup lang="ts">
import { computed, inject } from 'vue'
import AppIcon from '@/components/AppIcon.vue'

const props = withDefaults(defineProps<{
  icon?: string
  variant?: 'default' | 'danger'
}>(), {
  variant: 'default',
})

const closeDropdown = inject<() => void>('dropdown:close', () => {})

const variantClasses = computed(() =>
  props.variant === 'danger'
    ? 'text-red-400/80 hover:text-red-400 hover:bg-red-500/8'
    : 'text-white/60 hover:text-white/90 hover:bg-white/5'
)

const iconClass = computed(() =>
  props.variant === 'danger' ? 'text-red-400/50' : 'text-white/40'
)
</script>
