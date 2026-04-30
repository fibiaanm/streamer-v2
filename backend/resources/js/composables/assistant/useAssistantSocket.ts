import { ref, onUnmounted } from 'vue'
import { useSocket } from '@/composables/core/useSocket'
import type { AssistantMessage } from './useMessages'

export const useAssistantSocket = (onMessageReceived: (message: AssistantMessage) => void) => {
  const isTyping = ref(false)
  const { emit, on, off } = useSocket()

  const handleMessageProcessing = () => {
    isTyping.value = true
  }

  const handleMessageReceived = (data: { sessionId: string; message: AssistantMessage }) => {
    isTyping.value = false
    onMessageReceived(data.message)
  }

  on('MessageProcessing', handleMessageProcessing)
  on('MessageReceived', handleMessageReceived)

  onUnmounted(() => {
    off('MessageProcessing', handleMessageProcessing)
    off('MessageReceived', handleMessageReceived)
  })

  const joinSession = (sessionId: string) => emit('join_session', { sessionId })
  const leaveSession = (sessionId: string) => emit('leave_session', { sessionId })

  return { isTyping, joinSession, leaveSession }
}
