<template>
  <!-- System message -->
  <div v-if="message.role === 'system'" class="flex justify-center px-4 py-1">
    <div class="glass rounded-xl px-4 py-2.5 max-w-md text-center">
      <p class="text-sm text-white/60">{{ message.content }}</p>
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
  <div v-else-if="message.role === 'user'" class="group flex justify-end px-4 py-1">
    <div class="relative max-w-[75%] rounded-2xl rounded-br-md px-4 pt-2.5 pb-5 bg-brand-500/20 border border-brand-400/20">
      <p class="text-sm text-white/90 whitespace-pre-wrap break-words">{{ message.content }}</p>
      <span class="absolute bottom-1.5 right-3 text-[10px] text-white/30 opacity-0 group-hover:opacity-100 transition-opacity duration-150">{{ time }}</span>
    </div>
  </div>

  <!-- Assistant message -->
  <div v-else class="group flex justify-start px-4 py-1">
    <div class="relative max-w-[75%] rounded-2xl rounded-bl-md px-4 pt-2.5 pb-5 glass">
      <div class="md-content text-sm text-white/85" v-html="renderMarkdown(message.content)" />

      <!-- Options (send_options virtual tool) -->
      <div v-if="message.metadata?.options?.length" class="mt-3">
        <template v-if="message.metadata.selected">
          <div class="flex flex-wrap gap-2">
            <span
              v-for="opt in message.metadata.options"
              :key="opt.value"
              :class="[
                'px-3 py-1 rounded-lg text-xs font-medium',
                opt.value === message.metadata.selected
                  ? 'glass-brand text-brand-300'
                  : 'text-white/25',
              ]"
            >
              {{ opt.label }}
            </span>
          </div>
        </template>

        <template v-else>
          <div class="flex flex-wrap gap-2 items-start">
            <template v-for="opt in message.metadata.options" :key="opt.value">
              <button
                v-if="opt.type === 'button'"
                @click="emit('select-option', message.id, opt.value)"
                class="px-3 py-1 rounded-lg text-xs font-medium glass-brand text-brand-300 hover:text-brand-200 transition-colors"
              >
                {{ opt.label }}
              </button>
              <DatetimePicker
                v-else-if="opt.type === 'datetime'"
                :default-value="opt.default"
                @confirm="emit('select-option', message.id, $event)"
              />
            </template>
          </div>
        </template>
      </div>

      <span class="absolute bottom-1.5 right-3 text-[10px] text-white/25 opacity-0 group-hover:opacity-100 transition-opacity duration-150">{{ time }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { AssistantMessage } from '@/composables/assistant/useMessages'
import DatetimePicker from '@/components/assistant/DatetimePicker.vue'
import { renderMarkdown } from '@/lib/markdown'

const props = defineProps<{ message: AssistantMessage }>()
const emit  = defineEmits<{ 'select-option': [messageId: string, value: string] }>()

const time = computed(() => {
  const d = new Date(props.message.created_at)
  return d.toLocaleTimeString('es', { hour: '2-digit', minute: '2-digit' })
})
</script>
