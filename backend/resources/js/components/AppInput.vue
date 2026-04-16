<template>
  <div class="w-full">
    <!-- Box -->
    <div :class="wrapperClasses">
      <div v-if="$slots.leading" class="pl-3 shrink-0 text-slate-400 dark:text-white/30">
        <slot name="leading" />
      </div>

      <div class="relative flex-1 min-w-0">
        <input
          :id="inputId"
          ref="inputRef"
          v-bind="$attrs"
          :value="modelValue"
          :type="type"
          :disabled="disabled"
          :readonly="readonly"
          :placeholder="label ? ' ' : placeholder"
          :class="inputClasses"
          @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
          @focus="onFocus"
          @blur="onBlur"
        />
        <label v-if="label" :for="inputId" :class="labelClasses">
          {{ label }}
        </label>
      </div>

      <div v-if="$slots.trailing" class="pr-3 shrink-0 text-slate-400 dark:text-white/30">
        <slot name="trailing" />
      </div>
    </div>

    <p v-if="hint || error" :class="messageClasses">{{ error ?? hint }}</p>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'

type Variant = 'default' | 'error' | 'success'
type Size    = 'sm' | 'md' | 'lg'

defineOptions({ inheritAttrs: false })

const props = withDefaults(defineProps<{
  modelValue?:  string | number
  label?:       string
  placeholder?: string
  hint?:        string
  error?:       string
  variant?:     Variant
  size?:        Size
  type?:        string
  disabled?:    boolean
  readonly?:    boolean
}>(), {
  modelValue:  '',
  variant:     'default',
  size:        'md',
  type:        'text',
  disabled:    false,
  readonly:    false,
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
  focus: [event: FocusEvent]
  blur:  [event: FocusEvent]
}>()

const isFocused = ref(false)

function onFocus(event: FocusEvent) {
  isFocused.value = true
  emit('focus', event)
}

function onBlur(event: FocusEvent) {
  isFocused.value = false
  emit('blur', event)
}

const inputRef = ref<HTMLInputElement>()
const inputId  = `app-input-${Math.random().toString(36).slice(2, 9)}`

defineExpose({ input: inputRef })

// ─── Size maps ────────────────────────────────────────────────────────────────

const fontSizes: Record<Size, string> = {
  sm: 'text-xs',
  md: 'text-sm',
  lg: 'text-base',
}

const inputPadding = computed(() => {
  const h = !!props.label
  if (props.size === 'sm') return h ? 'px-3 pt-6 pb-1.5' : 'px-3 py-2'
  if (props.size === 'lg') return h ? 'px-4 pt-8 pb-2.5' : 'px-4 py-3'
  return h ? 'px-4 pt-7 pb-2' : 'px-4 py-2.5'
})

const labelPositions: Record<Size, { floated: string; center: string }> = {
  sm: { floated: 'top-2',   center: 'top-1/2' },
  md: { floated: 'top-2.5', center: 'top-1/2' },
  lg: { floated: 'top-3',   center: 'top-1/2' },
}

// ─── Variant maps ─────────────────────────────────────────────────────────────

const variantWrapper: Record<Variant, string> = {
  default:
    'border-slate-200 dark:border-white/10 ' +
    'focus-within:border-brand-500/60 dark:focus-within:border-brand-400/40 ' +
    'focus-within:ring-2 focus-within:ring-brand-500/20 dark:focus-within:ring-brand-400/15',
  error:
    'border-rose-400 dark:border-rose-500/50 ' +
    'focus-within:ring-2 focus-within:ring-rose-400/20 dark:focus-within:ring-rose-500/15',
  success:
    'border-emerald-400 dark:border-emerald-500/50 ' +
    'focus-within:ring-2 focus-within:ring-emerald-400/20 dark:focus-within:ring-emerald-500/15',
}

const labelFocusColor: Record<Variant, string> = {
  default: 'text-brand-500 dark:text-brand-400',
  error:   'text-rose-500 dark:text-rose-400',
  success: 'text-emerald-500 dark:text-emerald-400',
}

// ─── Computed classes ─────────────────────────────────────────────────────────

const wrapperClasses = computed(() => [
  'flex items-center rounded-lg border transition-all duration-150',
  'bg-white dark:bg-white/5',
  props.disabled ? 'opacity-50 cursor-not-allowed' : '',
  variantWrapper[props.variant],
])

const inputClasses = computed(() => [
  'w-full bg-transparent outline-none',
  'text-slate-900 dark:text-white',
  'placeholder:text-slate-400 dark:placeholder:text-white/25',
  props.disabled ? 'cursor-not-allowed' : '',
  fontSizes[props.size],
  inputPadding.value,
])

const labelClasses = computed(() => {
  const { floated, center } = labelPositions[props.size]
  const isFloated = isFocused.value || !!props.modelValue

  return [
    'absolute left-4 pointer-events-none transition-all duration-150 leading-none',
    isFloated
      ? `${floated} translate-y-0 text-xs font-medium`
      : `${center} -translate-y-1/2 text-sm font-normal text-slate-400 dark:text-white/30`,
    isFloated
      ? (isFocused.value ? labelFocusColor[props.variant] : 'text-slate-500 dark:text-white/40')
      : '',
  ]
})

const messageClasses = computed(() => [
  'mt-1.5 text-xs px-1',
  props.error
    ? 'text-rose-500 dark:text-rose-400'
    : 'text-slate-400 dark:text-white/30',
])
</script>
