<template>
  <button
    v-bind="$attrs"
    :type="type"
    :disabled="disabled || loading"
    :class="classes"
  >
    <!-- Loading spinner (always on left, replaces left icon and live dot) -->
    <svg
      v-if="loading"
      class="shrink-0 animate-spin"
      :class="spinnerSizes[size]"
      viewBox="0 0 16 16"
      fill="none"
    >
      <circle cx="8" cy="8" r="6" stroke="currentColor" stroke-width="2" stroke-opacity="0.25" />
      <path d="M14 8a6 6 0 0 0-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
    </svg>

    <!-- Left decorations (only when not loading) -->
    <template v-else>
      <span v-if="variant === 'live'" class="w-2 h-2 rounded-full bg-live-500 animate-pulse shrink-0" />
      <AppIcon v-else-if="icon && iconPosition === 'left'" :name="icon" :size="iconSizes[size]" />
    </template>

    <span :class="loading ? 'opacity-60' : ''">
      <slot />
    </span>

    <!-- Right icon (hidden during loading) -->
    <AppIcon
      v-if="!loading && icon && iconPosition === 'right'"
      :name="icon"
      :size="iconSizes[size]"
    />
  </button>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import AppIcon from './AppIcon.vue'

type Variant      = 'primary' | 'secondary' | 'ghost' | 'danger' | 'live'
type Size         = 'xs' | 'sm' | 'md' | 'lg' | 'xl'
type IconPosition = 'left' | 'right'

const props = withDefaults(defineProps<{
  variant?:       Variant
  size?:          Size
  type?:          'button' | 'submit' | 'reset'
  disabled?:      boolean
  loading?:       boolean
  icon?:          string
  iconPosition?:  IconPosition
}>(), {
  variant:      'primary',
  size:         'md',
  type:         'button',
  disabled:     false,
  loading:      false,
  iconPosition: 'left',
})

const base = [
  'inline-flex items-center justify-center gap-2',
  'font-medium rounded-lg transition-all cursor-pointer',
  'disabled:opacity-50 disabled:cursor-not-allowed',
].join(' ')

const sizes: Record<Size, string> = {
  xs: 'px-2 py-0.5 text-[10px]',
  sm: 'px-3 py-1.5 text-xs',
  md: 'px-4 py-2 text-sm',
  lg: 'px-5 py-2.5 text-base',
  xl: 'px-6 py-3 text-lg',
}

const variants: Record<Variant, string> = {
  primary:
    'bg-brand-600 hover:bg-brand-500 text-white shadow-lg shadow-brand-600/30',
  secondary:
    'bg-slate-100 dark:bg-white/8 hover:bg-slate-200 dark:hover:bg-white/15 ' +
    'border border-slate-200 dark:border-white/10 text-slate-800 dark:text-white',
  ghost:
    'text-brand-600 dark:text-brand-400 ' +
    'hover:bg-brand-50 dark:hover:bg-brand-500/10',
  danger:
    'bg-rose-50 dark:bg-rose-500/10 hover:bg-rose-100 dark:hover:bg-rose-500/20 ' +
    'border border-rose-200 dark:border-rose-500/20 text-rose-600 dark:text-rose-400',
  live:
    'bg-live-50 dark:bg-live-500/10 hover:bg-live-100 dark:hover:bg-live-500/20 ' +
    'border border-live-200 dark:border-live-400/20 text-live-700 dark:text-live-400',
}

const spinnerSizes: Record<Size, string> = {
  xs: 'w-2.5 h-2.5',
  sm: 'w-3 h-3',
  md: 'w-3.5 h-3.5',
  lg: 'w-4 h-4',
  xl: 'w-5 h-5',
}

// AppIcon sizes mapped to button sizes
const iconSizes: Record<Size, 'xs' | 'sm' | 'md'> = {
  xs: 'xs',
  sm: 'xs',
  md: 'sm',
  lg: 'sm',
  xl: 'md',
}

const classes = computed(() => [base, sizes[props.size], variants[props.variant]])
</script>
