<template>
  <picture v-if="user.avatar_url.jpeg" :class="['block shrink-0 rounded-full overflow-hidden', containerClass]">
    <source :srcset="user.avatar_url.webp" type="image/webp" />
    <img :src="user.avatar_url.jpeg" :alt="user.name" class="w-full h-full object-cover" />
  </picture>
  <div
    v-else
    :class="['rounded-full flex items-center justify-center font-semibold shrink-0', containerClass, textClass, colorClass]"
  >
    {{ initials }}
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { MemberUser } from '@/composables/api/useMembersApi'

type Size = 'xs' | 'sm' | 'md' | 'lg' | 'xl'

const props = withDefaults(defineProps<{ user: MemberUser; size?: Size }>(), {
  size: 'sm',
})

const COLORS = [
  'bg-sky-500/20 text-sky-300',
  'bg-purple-500/20 text-purple-300',
  'bg-emerald-500/20 text-emerald-300',
  'bg-amber-500/20 text-amber-300',
  'bg-rose-500/20 text-rose-300',
]

const sizes: Record<Size, { container: string; text: string }> = {
  xs: { container: 'w-6 h-6',   text: 'text-[9px]'  },
  sm: { container: 'w-8 h-8',   text: 'text-[11px]' },
  md: { container: 'w-10 h-10', text: 'text-sm'     },
  lg: { container: 'w-12 h-12', text: 'text-base'   },
  xl: { container: 'w-16 h-16', text: 'text-lg'     },
}

const containerClass = computed(() => sizes[props.size].container)
const textClass      = computed(() => sizes[props.size].text)

const initials = computed(() =>
  props.user.name.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase()
)

const colorClass = computed(() => {
  const hash = [...props.user.name].reduce((acc, c) => acc + c.charCodeAt(0), 0)
  return COLORS[hash % COLORS.length]
})
</script>
