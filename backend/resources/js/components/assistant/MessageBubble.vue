<template>
  <!-- System message -->
  <div v-if="message.role === 'system'" class="flex justify-center px-4 py-1">
    <div class="glass rounded-xl px-4 py-2.5 max-w-md text-center">
      <p class="text-sm text-white/60">{{ message.content }}</p>
      <!-- Action buttons -->
      <div v-if="message.actions?.length" class="flex flex-wrap justify-center gap-2 mt-2">
        <button
          v-for="action in message.actions"
          :key="action.value"
          class="px-3 py-1 rounded-lg text-xs font-medium glass-brand text-brand-300 hover:text-brand-200 transition-colors"
        >
          {{ action.label }}
        </button>
      </div>
    </div>
  </div>

  <!-- User message -->
  <div v-else-if="message.role === 'user'" class="flex justify-end px-4 py-1">
    <div class="max-w-[75%] rounded-2xl rounded-br-md px-4 py-2.5 bg-brand-500/20 border border-brand-400/20">
      <p class="text-sm text-white/90 whitespace-pre-wrap break-words">{{ message.content }}</p>
      <p class="text-[10px] text-white/30 text-right mt-1">{{ time }}</p>
    </div>
  </div>

  <!-- Assistant message -->
  <div v-else class="flex justify-start px-4 py-1">
    <div class="max-w-[75%] rounded-2xl rounded-bl-md px-4 py-2.5 glass">
      <p class="text-sm text-white/85 whitespace-pre-wrap break-words">{{ message.content }}</p>
      <p class="text-[10px] text-white/25 mt-1">{{ time }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { AssistantMessage } from '@/composables/assistant/useMessages'

const props = defineProps<{ message: AssistantMessage }>()

const time = computed(() => {
  const d = new Date(props.message.created_at)
  return d.toLocaleTimeString('es', { hour: '2-digit', minute: '2-digit' })
})
</script>
