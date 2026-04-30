import { ref } from 'vue'
import { useApi } from '@/lib/api'

export interface AssistantMessage {
  id:         string
  role:       'user' | 'assistant' | 'system'
  content:    string
  channel:    'web' | 'whatsapp'
  actions:    Array<{ label: string; value: string }>
  metadata:   Record<string, unknown>
  created_at: string
}

export const useMessages = () => {
  const messages = ref<AssistantMessage[]>([])
  const loading  = ref(false)

  const clearMessages = (): void => {
    messages.value = []
  }

  const loadMessages = async (sessionId: string): Promise<void> => {
    loading.value = true
    try {
      const api = useApi()
      const res = await api.get('/assistant/conversation/messages', {
        params: { session: sessionId },
      })
      messages.value = res.data.data as AssistantMessage[]
    } finally {
      loading.value = false
    }
  }

  const appendMessage = (msg: AssistantMessage): void => {
    messages.value = [...messages.value, msg]
  }

  return { messages, loading, loadMessages, clearMessages, appendMessage }
}
