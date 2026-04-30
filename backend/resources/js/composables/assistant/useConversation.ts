import { ref } from 'vue'
import { createSharedComposableById } from '@/composables/core/createSharedComposableById'
import { useApi } from '@/lib/api'

export const useConversation = () => createSharedComposableById('assistant-conversation', () => {
  const activeSessionId = ref<string | null>(null)

  const resolveConversation = async (): Promise<string | null> => {
    try {
      const api = useApi()
      const res = await api.get('/assistant/conversation')
      activeSessionId.value = res.data.data.active_session_id ?? null
    } catch {
      activeSessionId.value = null
    }
    return activeSessionId.value
  }

  return { activeSessionId, resolveConversation }
})
