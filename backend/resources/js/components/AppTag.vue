<template>
  <span :class="classes">
    <slot />
    <button
      v-if="removable"
      type="button"
      :class="removeClasses"
      @click.stop="emit('remove')"
    >
      <svg class="w-3 h-3" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
        <path d="M1 1l10 10M11 1L1 11" />
      </svg>
    </button>
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue'

type Variant = 'brand' | 'live' | 'success' | 'warning' | 'danger' | 'neutral'
type Size    = 'sm' | 'md'

const props = withDefaults(defineProps<{
  variant?:  Variant
  size?:     Size
  removable?: boolean
}>(), {
  variant:   'neutral',
  size:      'md',
  removable: false,
})

const emit = defineEmits<{ remove: [] }>()

const base = 'inline-flex items-center gap-1.5 rounded-md font-medium border'

const sizes: Record<Size, string> = {
  sm: 'px-2 py-0.5 text-[10px]',
  md: 'px-2.5 py-1 text-xs',
}

const variants: Record<Variant, string> = {
  brand:
    'bg-brand-100 dark:bg-brand-500/15 text-brand-700 dark:text-brand-300 ' +
    'border-brand-200 dark:border-brand-500/20',
  live:
    'bg-live-100 dark:bg-live-500/15 text-live-700 dark:text-live-400 ' +
    'border-live-200 dark:border-live-500/20',
  success:
    'bg-emerald-100 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 ' +
    'border-emerald-200 dark:border-emerald-500/20',
  warning:
    'bg-amber-100 dark:bg-amber-500/15 text-amber-700 dark:text-amber-400 ' +
    'border-amber-200 dark:border-amber-500/20',
  danger:
    'bg-rose-100 dark:bg-rose-500/15 text-rose-700 dark:text-rose-400 ' +
    'border-rose-200 dark:border-rose-500/20',
  neutral:
    'bg-slate-100 dark:bg-white/8 text-slate-600 dark:text-white/50 ' +
    'border-slate-200 dark:border-white/10',
}

// El botón de remove hereda el color del texto del variant
const removeClasses =
  'ml-0.5 rounded opacity-60 hover:opacity-100 transition-opacity cursor-pointer'

const classes = computed(() => [base, sizes[props.size], variants[props.variant]])
</script>
