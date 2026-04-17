<template>
  <div
    ref="containerEl"
    class="relative flex items-center gap-0.5 p-1 rounded-xl bg-white/5 border border-white/8 overflow-hidden"
  >
    <!-- Sliding highlight -->
    <div
      class="absolute rounded-lg bg-brand-500 shadow-sm shadow-brand-500/30 pointer-events-none"
      :style="{
        left:   highlight.left,
        top:    highlight.top,
        width:  highlight.width,
        height: highlight.height,
        transition: ready
          ? 'left 0.25s cubic-bezier(0.4,0,0.2,1), width 0.25s cubic-bezier(0.4,0,0.2,1)'
          : 'none',
      }"
    />

    <!-- Tab buttons -->
    <button
      v-for="tab in tabs"
      :key="tab.id"
      :ref="(el) => setRef(el as HTMLElement | null, tab.id)"
      class="relative z-10 flex items-center gap-1.5 px-4 py-1.5 rounded-lg text-xs font-medium
             cursor-pointer transition-colors duration-200 whitespace-nowrap select-none"
      :class="modelValue === tab.id ? 'text-white' : 'text-white/40 hover:text-white/70'"
      @click="emit('update:modelValue', tab.id)"
    >
      <AppIcon v-if="tab.icon" :name="tab.icon" size="xs" class="shrink-0" />
      {{ tab.label }}
    </button>
  </div>
</template>

<script setup lang="ts" generic="T extends string">
import { ref, reactive, watch, onMounted, nextTick } from 'vue'
import AppIcon from '@/components/AppIcon.vue'

// ─── Public interface ─────────────────────────────────────────────────────────

export interface TabOption<Id extends string = string> {
  id: Id
  label: string
  icon?: string  // nombre de AppIcon, ej. "ui/gallery"
}

// ─── Props / emits ────────────────────────────────────────────────────────────

const props = defineProps<{
  tabs: TabOption<T>[]
  modelValue: T
}>()

const emit = defineEmits<{
  'update:modelValue': [id: T]
}>()

// ─── Sliding highlight ────────────────────────────────────────────────────────

const containerEl = ref<HTMLElement>()
const tabEls      = new Map<string, HTMLElement>()
const ready       = ref(false)

const highlight = reactive({ left: '4px', top: '4px', width: '0px', height: '0px' })

function setRef(el: HTMLElement | null, id: string) {
  if (el) tabEls.set(id, el)
  else tabEls.delete(id)
}

function sync() {
  const el = tabEls.get(props.modelValue)
  if (!el) return
  highlight.left   = `${el.offsetLeft}px`
  highlight.top    = `${el.offsetTop}px`
  highlight.width  = `${el.offsetWidth}px`
  highlight.height = `${el.offsetHeight}px`
}

onMounted(() => nextTick(() => { sync(); ready.value = true }))
watch(() => props.modelValue, () => nextTick(sync))
</script>
