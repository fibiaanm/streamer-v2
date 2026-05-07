<template>
  <div
    class="group relative glass rounded-2xl px-4 py-3 flex flex-col gap-2 cursor-pointer transition-all"
    :class="selected
      ? 'ring-1 ring-brand-400/30 bg-brand-400/5'
      : 'hover:bg-white/4'"
    @click="emit('select', list.id)"
  >
    <!-- Header -->
    <div class="flex items-start justify-between gap-2">
      <div class="flex items-center gap-2 min-w-0">
        <p class="text-sm font-medium text-white/90 truncate">{{ list.name }}</p>
        <span
          v-if="list.type"
          class="shrink-0 text-[10px] font-medium uppercase tracking-wide text-white/35
                 bg-white/8 rounded-md px-1.5 py-0.5"
        >
          {{ list.type }}
        </span>
        <span
          v-if="list.is_shared_with_me"
          class="shrink-0 text-[10px] font-medium text-brand-300/70 bg-brand-400/8 rounded-md px-1.5 py-0.5"
        >
          compartida
        </span>
      </div>

      <!-- Counts -->
      <div class="flex items-center gap-2 shrink-0 text-xs text-white/35">
        <span v-if="list.items_count.pending > 0" class="text-white/55">
          {{ list.items_count.pending }}
        </span>
        <span v-if="list.items_count.done > 0">
          {{ list.items_count.done }} ✓
        </span>
        <span v-if="isEmpty" class="italic">vacía</span>
      </div>
    </div>

    <!-- Preview: first pending items -->
    <div v-if="previewItems.length > 0" class="flex flex-col gap-0.5">
      <p
        v-for="item in previewItems"
        :key="item"
        class="text-xs text-white/40 truncate pl-3 before:content-['·'] before:absolute before:left-0 before:text-white/20 relative"
      >
        {{ item }}
      </p>
      <p
        v-if="list.items_count.pending > 3"
        class="text-[10px] text-white/25 pl-3"
      >
        +{{ list.items_count.pending - 3 }} más
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { AssistantList } from '@/types'

const props = defineProps<{
  list:     AssistantList
  selected: boolean
}>()

const emit = defineEmits<{ select: [id: string] }>()

const isEmpty = computed(
  () => props.list.items_count.pending === 0 && props.list.items_count.done === 0,
)

const previewItems = computed(() => {
  if (!props.list.items) return []
  return props.list.items
    .filter((i) => i.status === 'pending')
    .slice(0, 3)
    .map((i) => i.content)
})
</script>
