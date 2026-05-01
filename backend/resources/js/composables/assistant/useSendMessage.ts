import { ref } from 'vue'
import { useApi } from '@/lib/api'
import type { AssistantMessage } from './useMessages'

interface SendResult {
  userMessage:      AssistantMessage
  sessionId:        string
  sessionNotFound?: false
}

interface SessionNotFoundResult {
  sessionNotFound: true
}

type SendMessageResult = SendResult | SessionNotFoundResult | null

export const useSendMessage = () => {
  const sending = ref(false)

  const sendMessage = async (content: string, sessionId?: string): Promise<SendMessageResult> => {
    if (sending.value) return null
    sending.value = true
    try {
      const api = useApi()
      const res = await api.post('/assistant/messages', { content, ...(sessionId ? { session_id: sessionId } : {}) })
      const { message_id, session_id } = res.data.data as { message_id: string; session_id: string }

      const userMessage: AssistantMessage = {
        id:         message_id,
        role:       'user',
        content,
        channel:    'web',
        actions:    [],
        metadata:   {},
        created_at: new Date().toISOString(),
      }

      return { userMessage, sessionId: session_id }
    } catch (e: any) {
      if (e?.response?.status === 404) return { sessionNotFound: true }
      return null
    } finally {
      sending.value = false
    }
  }

  return { sending, sendMessage }
}
