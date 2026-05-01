<template>
  <div class="flex items-center gap-2 mt-1">
    <input
      v-model="localValue"
      type="datetime-local"
      class="rounded-lg px-2 py-1 text-xs bg-white/10 border border-white/20 text-white/80 focus:outline-none focus:border-brand-400/60"
    />
    <button
      @click="confirm"
      :disabled="!localValue"
      class="px-3 py-1 rounded-lg text-xs font-medium glass-brand text-brand-300 hover:text-brand-200 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
    >
      Aceptar
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const props = defineProps<{ defaultValue?: string }>()
const emit  = defineEmits<{ confirm: [value: string] }>()

// Convert ISO 8601 to datetime-local format (YYYY-MM-DDTHH:mm)
const toLocalInput = (iso?: string): string => {
  if (!iso) return ''
  try {
    return new Date(iso).toISOString().slice(0, 16)
  } catch {
    return ''
  }
}

const localValue = ref(toLocalInput(props.defaultValue))

const confirm = (): void => {
  if (!localValue.value) return
  // Emit as ISO 8601
  emit('confirm', new Date(localValue.value).toISOString())
}
</script>
