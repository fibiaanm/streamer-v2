<template>
  <div class="flex flex-col h-full border-r border-white/8 bg-white/2">

    <!-- Category shortcuts -->
    <div class="px-3 pt-4 pb-3 border-b border-white/6 shrink-0">
      <p class="text-[10px] font-semibold uppercase tracking-widest text-white/25 px-1 mb-2">
        Acceso rápido
      </p>
      <div class="flex flex-col gap-0.5">
        <button
          v-for="shortcut in shortcuts"
          :key="shortcut.label"
          class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-white/50 hover:text-white/80 hover:bg-white/6 transition-colors text-sm text-left cursor-pointer"
        >
          <AppIcon :name="shortcut.icon" size="sm" class="shrink-0" />
          {{ shortcut.label }}
        </button>
      </div>
    </div>

    <!-- Session list -->
    <div class="flex-1 overflow-y-auto px-2 py-3 flex flex-col gap-0.5">
      <p class="text-[10px] font-semibold uppercase tracking-widest text-white/25 px-1 mb-1">
        Conversaciones
      </p>

      <template v-if="sessions.length === 0 && !loading">
        <p class="text-xs text-white/25 px-3 py-2">Sin sesiones previas</p>
      </template>

      <SessionListItem
        v-for="session in sessions"
        :key="session.id"
        :session="session"
        :is-selected="session.id === activeSessionId"
        @select="emit('selectSession', $event)"
      />

      <!-- Load more -->
      <button
        v-if="hasMore"
        class="mt-1 text-xs text-white/30 hover:text-white/55 transition-colors px-3 py-1.5 text-left"
        :disabled="loading"
        @click="loadMore"
      >
        {{ loading ? 'Cargando...' : 'Cargar más' }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import AppIcon        from '@/components/AppIcon.vue'
import SessionListItem from './SessionListItem.vue'
import { useSessions } from '@/composables/assistant/useSessions'

defineProps<{ activeSessionId?: string }>()
const emit = defineEmits<{ selectSession: [id: string] }>()

const { sessions, loading, hasMore, loadSessions, loadMore } = useSessions()

onMounted(loadSessions)

const shortcuts = [
  { label: 'Memorias',      icon: 'ui/bookmark' },
  { label: 'Recordatorios', icon: 'ui/repeat' },
  { label: 'Listas',        icon: 'ui/check' },
  { label: 'Gastos',        icon: 'ui/info' },
  { label: 'Amigos',        icon: 'ui/users' },
]
</script>
