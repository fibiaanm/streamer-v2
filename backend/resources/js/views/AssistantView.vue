<template>
  <AppLayout>
    <template #sidebar>
      <AssistantSidebar
        :active-session-id="activeSessionId"
        @select-session="navigateToSession"
      />
    </template>

    <template #header-left>
      <span class="text-sm font-semibold text-white/70">Asistente</span>
    </template>
    <template #header-right>
      <UserMenu back-url="/app" />
    </template>

    <div class="h-full">
      <ChatPanel
        :session-id="sessionId"
        :is-active-session="isActiveSession"
        @session-created="handleSessionCreated"
      />
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppLayout        from '@/components/AppLayout.vue'
import UserMenu         from '@/components/UserMenu.vue'
import AssistantSidebar from '@/components/assistant/AssistantSidebar.vue'
import ChatPanel        from '@/components/assistant/ChatPanel.vue'
import { useConversation } from '@/composables/assistant/useConversation'

const route  = useRoute()
const router = useRouter()

const { activeSessionId, resolveConversation } = useConversation()

const sessionId = computed(() => route.query.session as string | undefined)

const isActiveSession = computed(() =>
  !!sessionId.value && sessionId.value === activeSessionId.value
)

function navigateToSession(id: string) {
  router.push({ query: { session: id } })
}

function handleSessionCreated(id: string) {
  router.replace({ query: { session: id } })
  activeSessionId.value = id
}

resolveConversation().then((id) => {
  if (id && !sessionId.value) {
    router.replace({ query: { session: id } })
  }
})

watch(sessionId, (val) => {
  if (!val) {
    resolveConversation().then((id) => {
      if (id) router.replace({ query: { session: id } })
    })
  }
})
</script>
