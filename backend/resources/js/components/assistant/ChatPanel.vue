<template>
  <div class="relative h-full">
    <MessageList
      ref="messageListRef"
      :messages="messages"
      :loading="messagesLoading"
      :is-typing="isTyping"
      :bottom-offset="inputHeight"
      @select-option="selectOption"
    />

    <ChatInput
      ref="chatInputRef"
      :sending="sending"
      class="absolute bottom-4 left-4 right-4"
      @send="handleSend"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, watch, nextTick, onUnmounted } from 'vue'
import MessageList from './MessageList.vue'
import ChatInput   from './ChatInput.vue'
import { useMessages } from '@/composables/assistant/useMessages'
import { useAssistantSocket } from '@/composables/assistant/useAssistantSocket'
import { useSendMessage } from '@/composables/assistant/useSendMessage'

const props = defineProps<{
  sessionId?: string
}>()

const emit = defineEmits<{ sessionCreated: [id: string]; sessionNotFound: [] }>()

const messageListRef = ref<InstanceType<typeof MessageList> | null>(null)
const chatInputRef   = ref<InstanceType<typeof ChatInput> | null>(null)
const inputHeight    = ref(96)

let resizeObserver: ResizeObserver | null = null

const focusInput = () => nextTick(() => chatInputRef.value?.focus())

watch(chatInputRef, (el) => {
  resizeObserver?.disconnect()
  const dom = (el as any)?.$el as HTMLElement | null
  if (!dom) return
  resizeObserver = new ResizeObserver(() => {
    inputHeight.value = dom.offsetHeight + 32 // + bottom-4 (16) top gap (16)
  })
  resizeObserver.observe(dom)
})

onUnmounted(() => resizeObserver?.disconnect())

const { messages, loading: messagesLoading, loadMessages, clearMessages, appendMessage, selectOption } = useMessages()
const { sending, sendMessage } = useSendMessage()

const { isTyping, joinSession, leaveSession } = useAssistantSocket(appendMessage)

watch(() => props.sessionId, async (id, prevId) => {
  if (prevId) leaveSession(prevId)
  if (id) {
    clearMessages()
    try {
      await loadMessages(id)
    } catch (e: any) {
      if (e?.response?.status === 404) {
        emit('sessionNotFound')
        return
      }
      throw e
    }
    joinSession(id)
    setTimeout(() => messageListRef.value?.scrollToBottom(false), 50)
  } else {
    clearMessages()
  }
  focusInput()
}, { immediate: true })

async function handleSend(content: string) {
  const result = await sendMessage(content, props.sessionId)
  if (!result) return

  if ('sessionNotFound' in result) {
    emit('sessionNotFound')
    return
  }

  appendMessage(result.userMessage)
  focusInput()

  if (result.sessionId && !props.sessionId) {
    emit('sessionCreated', result.sessionId)
  }
}
</script>
