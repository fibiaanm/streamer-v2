<template>
  <div ref="listEl" class="absolute inset-0 overflow-y-auto pretty-scroll pt-4 flex flex-col gap-0.5" :style="{ paddingBottom: bottomOffset + 'px' }" @scroll="onScroll">
    <!-- Loading skeleton -->
    <template v-if="loading">
      <div v-for="i in 4" :key="i" class="px-4 py-1 flex" :class="i % 2 === 0 ? 'justify-end' : 'justify-start'">
        <div class="h-10 rounded-2xl bg-white/6 animate-pulse" :class="i % 2 === 0 ? 'w-48' : 'w-64'" />
      </div>
    </template>

    <template v-else>
      <div v-if="messages.length === 0" class="flex-1 flex items-center justify-center">
        <p class="text-sm text-white/25">Escríbeme algo para empezar</p>
      </div>

      <MessageBubble
        v-for="msg in messages"
        :key="msg.id"
        :message="msg"
      />

      <TypingIndicator v-if="isTyping" />
    </template>

    <div ref="anchorEl" />
  </div>
</template>

<script setup lang="ts">
import { ref, watch, nextTick } from 'vue'
import MessageBubble   from './MessageBubble.vue'
import TypingIndicator from './TypingIndicator.vue'
import type { AssistantMessage } from '@/composables/assistant/useMessages'

const props = defineProps<{
  messages:      AssistantMessage[]
  loading:       boolean
  isTyping:      boolean
  bottomOffset?: number
}>()

const listEl   = ref<HTMLElement | null>(null)
const anchorEl = ref<HTMLElement | null>(null)

let scrollTimer: ReturnType<typeof setTimeout> | null = null
function onScroll() {
  listEl.value?.classList.add('scrolling')
  if (scrollTimer) clearTimeout(scrollTimer)
  scrollTimer = setTimeout(() => listEl.value?.classList.remove('scrolling'), 800)
}

function scrollToBottom(smooth = true) {
  nextTick(() => {
    if (!listEl.value) return
    listEl.value.scrollTo({ top: listEl.value.scrollHeight, behavior: smooth ? 'smooth' : 'instant' })
  })
}

watch(() => props.messages.length, (_, prev) => scrollToBottom(prev > 0))
watch(() => props.isTyping, (val) => { if (val) scrollToBottom() })

defineExpose({ scrollToBottom })
</script>
