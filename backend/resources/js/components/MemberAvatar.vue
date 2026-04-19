<template>
  <img
    v-if="user.avatar_url"
    :src="user.avatar_url"
    :alt="user.name"
    class="w-8 h-8 rounded-full shrink-0 object-cover"
  />
  <div
    v-else
    :class="['w-8 h-8 rounded-full flex items-center justify-center text-xs font-semibold shrink-0', colorClass]"
  >
    {{ initialsText }}
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { MemberUser } from '@/composables/api/useMembersApi'

const props = defineProps<{ user: MemberUser }>()

const COLORS = [
  'bg-sky-500/20 text-sky-300',
  'bg-purple-500/20 text-purple-300',
  'bg-emerald-500/20 text-emerald-300',
  'bg-amber-500/20 text-amber-300',
  'bg-rose-500/20 text-rose-300',
]

const initialsText = computed(() =>
  props.user.name.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase()
)

const colorClass = computed(() => {
  const hash = [...props.user.name].reduce((acc, c) => acc + c.charCodeAt(0), 0)
  return COLORS[hash % COLORS.length]
})
</script>
