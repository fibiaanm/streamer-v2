<template>
  <div
    class="relative w-full flex flex-wrap gap-1.5 px-3 rounded-lg border transition-all cursor-text"
    :class="[
      isFocused
        ? 'border-brand-400/40 bg-white/5 ring-2 ring-brand-400/15'
        : 'border-white/10 bg-white/5',
      label ? 'min-h-[52px] pt-7 pb-2' : 'min-h-[44px] py-2',
    ]"
    @click="focusInput"
  >
    <!-- Floating label -->
    <label
      v-if="label"
      class="absolute left-3 pointer-events-none transition-all duration-150 leading-none"
      :class="isFloated
        ? [
            'top-2.5 translate-y-0 text-xs font-medium',
            isFocused ? 'text-brand-400' : 'text-white/40',
          ]
        : 'top-1/2 -translate-y-1/2 text-sm font-normal text-white/30'"
    >
      {{ label }}
    </label>

    <!-- Tags -->
    <span
      v-for="(tag, i) in tags"
      :key="i"
      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium self-center"
      :class="isValidEmail(tag)
        ? 'bg-brand-500/15 text-brand-300 border border-brand-500/25'
        : 'bg-rose-500/15 text-rose-300 border border-rose-500/25'"
    >
      {{ tag }}
      <button
        type="button"
        class="flex items-center justify-center w-3 h-3 rounded-sm hover:bg-white/20 transition-colors cursor-pointer"
        @click.stop="removeTag(i)"
      >
        <svg viewBox="0 0 8 8" fill="currentColor" class="w-2 h-2">
          <path d="M1 1l6 6M7 1L1 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
      </button>
    </span>

    <!-- Input -->
    <input
      ref="inputRef"
      v-model="inputValue"
      type="text"
      :placeholder="!label && tags.length === 0 ? placeholder : ''"
      class="flex-1 min-w-[140px] bg-transparent text-sm text-white/80 placeholder:text-white/25 outline-none py-0.5 self-center"
      @keydown="onKeydown"
      @blur="onBlur"
      @focus="isFocused = true"
      @paste="onPaste"
    />
  </div>

  <!-- Invalid hint -->
  <p v-if="hasInvalidTags" class="mt-1.5 text-xs text-rose-400/80">
    Los correos marcados en rojo no son válidos y no se enviarán.
  </p>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

const props = withDefaults(defineProps<{
  modelValue:   string[]
  label?:       string
  placeholder?: string
}>(), {
  placeholder: 'email@ejemplo.com, otro@ejemplo.com…',
})

const emit = defineEmits<{
  'update:modelValue': [value: string[]]
}>()

const inputRef   = ref<HTMLInputElement | null>(null)
const inputValue = ref('')
const isFocused  = ref(false)

const tags = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val),
})

const isFloated = computed(() =>
  isFocused.value || tags.value.length > 0 || inputValue.value !== '',
)

const isValidEmail = (email: string) =>
  /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim())

const hasInvalidTags = computed(() => tags.value.some(t => !isValidEmail(t)))

const focusInput = () => inputRef.value?.focus()

const commitInput = () => {
  const value = inputValue.value.replace(/,$/, '').trim()
  if (!value) return
  if (!tags.value.includes(value)) {
    emit('update:modelValue', [...tags.value, value])
  }
  inputValue.value = ''
}

const removeTag = (index: number) => {
  const next = [...tags.value]
  next.splice(index, 1)
  emit('update:modelValue', next)
}

const onKeydown = (e: KeyboardEvent) => {
  if (e.key === ',' || e.key === 'Enter') {
    e.preventDefault()
    commitInput()
    return
  }
  if (e.key === 'Backspace' && inputValue.value === '' && tags.value.length > 0) {
    removeTag(tags.value.length - 1)
  }
}

const onBlur = () => {
  isFocused.value = false
  commitInput()
}

const onPaste = (e: ClipboardEvent) => {
  e.preventDefault()
  const text = e.clipboardData?.getData('text') ?? ''
  const emails = text.split(/[\s,;]+/).map(s => s.trim()).filter(Boolean)
  const unique = emails.filter(em => !tags.value.includes(em))
  if (unique.length) {
    emit('update:modelValue', [...tags.value, ...unique])
  }
  inputValue.value = ''
}
</script>
