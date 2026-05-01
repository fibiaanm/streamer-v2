import { ref } from 'vue'
import { useApi } from '@/lib/api'

export interface MessageOption {
  type:     'button' | 'datetime'
  label:    string
  value:    string
  default?: string
}

export interface MessageMetadata {
  request_id?: string
  options?:    MessageOption[]
  selected?:   string
  [key: string]: unknown
}

export interface AssistantMessage {
  id:         string
  role:       'user' | 'assistant' | 'system'
  content:    string
  channel:    'web' | 'whatsapp'
  actions:    Array<{ label: string; value: string }>
  metadata:   MessageMetadata
  created_at: string
}

export const useMessages = () => {
  const messages = ref<AssistantMessage[]>([])
  const loading  = ref(false)
  const api      = useApi()

  const clearMessages = (): void => {
    messages.value = []
  }

  const loadMessages = async (sessionId: string): Promise<void> => {
    loading.value = true
    try {
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

  const selectOption = async (messageId: string, value: string): Promise<void> => {
    // Optimistic update so UI reflects selection immediately
    const msg = messages.value.find(m => m.id === messageId)
    if (msg) {
      msg.metadata = { ...msg.metadata, selected: value }
    }
    await api.post(`/assistant/messages/${messageId}/select`, { value })
  }

  return { messages, loading, loadMessages, clearMessages, appendMessage, selectOption }
}
