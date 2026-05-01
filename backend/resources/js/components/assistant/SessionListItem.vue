<template>
  <button
    class="w-full text-left px-3 py-2.5 rounded-xl flex items-start gap-2.5 transition-colors group cursor-pointer"
    :class="isSelected
      ? 'bg-brand-500/15 text-white'
      : 'text-white/60 hover:bg-white/6 hover:text-white/80'"
    @click="emit('select', session.id)"
  >
    <div class="flex-1 min-w-0">
      <div class="text-sm font-medium truncate leading-tight">
        {{ session.title }}
      </div>
      <div class="text-xs mt-0.5 truncate"
           :class="isSelected ? 'text-white/50' : 'text-white/35 group-hover:text-white/45'">
        {{ relativeDate }}
      </div>
    </div>
    <AppBadge v-if="session.is_active" variant="live" size="sm" class="shrink-0 mt-0.5">
      Activa
    </AppBadge>
  </button>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import AppBadge from '@/components/AppBadge.vue'
import type { AssistantSession } from '@/composables/assistant/useSessions'

const props = defineProps<{
  session:    AssistantSession
  isSelected: boolean
}>()

const emit = defineEmits<{ select: [id: string] }>()

const relativeDate = computed(() => {
  if (!props.session.started_at) return ''
  const date = new Date(props.session.started_at)
  const now  = new Date()
  const diff = Math.floor((now.getTime() - date.getTime()) / 1_000)

  if (diff < 60)           return 'Ahora mismo'
  if (diff < 3_600)        return `Hace ${Math.floor(diff / 60)} min`
  if (diff < 86_400)       return `Hace ${Math.floor(diff / 3_600)} h`
  if (diff < 86_400 * 7)   return `Hace ${Math.floor(diff / 86_400)} días`
  return date.toLocaleDateString('es', { day: 'numeric', month: 'short' })
})
</script>
