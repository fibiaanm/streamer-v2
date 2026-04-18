<template>
  <div class="relative inline-block" ref="containerRef">
    <div @click="toggle" class="cursor-pointer">
      <slot name="trigger" />
    </div>

    <Teleport to="body">
      <Transition
        enter-active-class="transition duration-150 ease-out"
        enter-from-class="opacity-0 scale-95 translate-y-1"
        enter-to-class="opacity-100 scale-100 translate-y-0"
        leave-active-class="transition duration-100 ease-in"
        leave-from-class="opacity-100 scale-100 translate-y-0"
        leave-to-class="opacity-0 scale-95 translate-y-1"
      >
        <div
          v-if="open"
          ref="panelRef"
          :style="dropdownStyle"
          class="fixed z-[9999] min-w-[200px] rounded-2xl bg-white/6 backdrop-blur-xs"
        >
          <div class="overflow-hidden rounded-2xl py-1.5">
            <slot />
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = withDefaults(defineProps<{
  align?: 'left' | 'right'
}>(), {
  align: 'right',
})

const open = ref(false)
const containerRef = ref<HTMLElement | null>(null)
const panelRef     = ref<HTMLElement | null>(null)
const anchorRect   = ref<DOMRect | null>(null)

const dropdownStyle = computed(() => {
  if (!anchorRect.value) return {}
  const rect = anchorRect.value
  const top = rect.bottom + 8
  if (props.align === 'right') {
    return { top: `${top}px`, right: `${window.innerWidth - rect.right}px` }
  }
  return { top: `${top}px`, left: `${rect.left}px` }
})

const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const toggle = () => {
  if (!open.value && containerRef.value) {
    anchorRect.value = containerRef.value.getBoundingClientRect()
  }
  open.value = !open.value
  emit('update:open', open.value)
}

const close = () => {
  open.value = false
  emit('update:open', false)
}

const onClickOutside = (e: MouseEvent) => {
  const target = e.target as Node
  const inContainer = containerRef.value?.contains(target) ?? false
  const inPanel     = panelRef.value?.contains(target) ?? false
  if (!inContainer && !inPanel) close()
}

onMounted(() => document.addEventListener('mousedown', onClickOutside))
onUnmounted(() => document.removeEventListener('mousedown', onClickOutside))

defineExpose({ close })
</script>
