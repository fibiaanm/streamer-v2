<template>
  <span
    :class="['inline-flex shrink-0 [&>svg]:size-full', sizes[size]]"
    v-html="svg"
    aria-hidden="true"
  />
</template>

<script setup lang="ts">
import { computed } from 'vue'

type Size = 'xs' | 'sm' | 'md' | 'lg' | 'xl'

const props = withDefaults(defineProps<{
  name: string
  size?: Size
}>(), {
  size: 'md',
})

const rawSvgs = import.meta.glob<string>('../icons/**/*.svg', {
  eager: true,
  query: '?raw',
  import: 'default',
})

const svgMap = Object.fromEntries(
  Object.entries(rawSvgs).map(([path, raw]) => [
    path.replace(/^.*\/icons\//, '').replace(/\.svg$/, ''),
    raw,
  ])
)

const sizes: Record<Size, string> = {
  xs: 'size-3',
  sm: 'size-4',
  md: 'size-5',
  lg: 'size-6',
  xl: 'size-8',
}

const svg = computed(() => svgMap[props.name] ?? '')
</script>
