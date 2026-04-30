<template>
  <div class="glass-light dark:glass rounded-3xl flex flex-col p-3 gap-2 max-h-[40vh]">

    <!-- Textarea -->
    <textarea
      ref="textareaEl"
      v-model="text"
      class="flex-1 min-h-[5rem] bg-transparent text-sm text-white/90 placeholder-white/30
             resize-none outline-none leading-relaxed overflow-y-auto pretty-scroll"
      placeholder="Escribe un mensaje..."
      :disabled="sending"
      @keydown.enter.exact.prevent="submit"
      @input="autoResize"
    />

    <!-- Barra inferior -->
    <div class="flex items-center justify-between shrink-0">
      <div class="flex items-center gap-2">
        <button
          disabled
          class="w-8 h-8 flex items-center justify-center rounded-full text-white/25 cursor-not-allowed"
          title="Adjuntar"
        >
          <AppIcon name="ui/paperclip" size="sm" />
        </button>
      </div>

      <button
        :disabled="!canSend"
        class="w-8 h-8 flex items-center justify-center rounded-full transition-colors"
        :class="canSend
          ? 'bg-white text-black cursor-pointer hover:bg-white/90'
          : 'bg-white/6 text-white/20 cursor-not-allowed'"
        @click="submit"
      >
        <AppIcon name="ui/send" size="sm" />
      </button>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, nextTick } from 'vue'
import AppIcon from '@/components/AppIcon.vue'

const emit  = defineEmits<{ send: [content: string] }>()
const props = defineProps<{ sending: boolean }>()

const text       = ref('')
const textareaEl = ref<HTMLTextAreaElement | null>(null)

const canSend = computed(() => text.value.trim().length > 0 && !props.sending)

function autoResize() {
  const el = textareaEl.value
  if (!el) return
  el.style.height = 'auto'
  el.style.height = el.scrollHeight + 'px'
}

function submit() {
  const content = text.value.trim()
  if (!content || props.sending) return
  emit('send', content)
  text.value = ''
  nextTick(() => {
    if (textareaEl.value) {
      textareaEl.value.style.height = 'auto'
      textareaEl.value.focus()
    }
  })
}

defineExpose({ focus: () => textareaEl.value?.focus() })
</script>
