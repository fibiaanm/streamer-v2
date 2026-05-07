<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-150"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="open"
        class="fixed inset-0 z-[450] flex items-start justify-end bg-black/60 backdrop-blur-sm"
        @click.self="$emit('close')"
      >
        <div
          class="h-full w-full bg-[#0e0e12] border-l border-white/8 flex flex-col overflow-hidden"
          :class="maxWidthClass"
        >
          <!-- Header -->
          <div class="flex items-start justify-between px-6 py-5 border-b border-white/8 shrink-0">
            <div class="min-w-0 flex-1">
              <slot name="header">
                <div class="text-sm text-white/40">Loading…</div>
              </slot>
            </div>
            <button
              class="ml-4 text-white/40 hover:text-white/80 transition-colors shrink-0 cursor-pointer"
              @click="$emit('close')"
            >✕</button>
          </div>

          <!-- Body -->
          <div class="flex-1 overflow-y-auto pretty-scroll px-5 py-4">
            <slot />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(defineProps<{
  open: boolean
  maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl' | '3xl'
}>(), {
  maxWidth: 'xl',
})

defineEmits<{ close: [] }>()

const maxWidthClass = computed(() => ({
  sm:  'max-w-sm',
  md:  'max-w-md',
  lg:  'max-w-lg',
  xl:  'max-w-xl',
  '2xl': 'max-w-2xl',
  '3xl': 'max-w-3xl',
}[props.maxWidth]))
</script>
