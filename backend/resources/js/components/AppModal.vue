<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div
        v-if="isOpen"
        class="fixed inset-0 z-[200] flex items-center justify-center p-4"
      >
        <div class="absolute inset-0 bg-black/20 backdrop-blur-sm" @click="emit('close')" />

        <div
          class="modal-card relative rounded-2xl flex flex-col overflow-hidden bg-[#0d1426]/95 backdrop-blur-2xl border border-white/12 shadow-glass w-full"
          :class="maxWidthClass"
        >
          <!-- Header -->
          <div class="flex items-center justify-between px-5 py-4 border-b border-white/8 shrink-0">
            <slot name="header" />
            <button
              class="w-7 h-7 flex items-center justify-center rounded-lg text-white/30 hover:text-white/70 hover:bg-white/8 transition-colors cursor-pointer ml-3"
              @click="emit('close')"
            >
              <AppIcon name="ui/x" size="sm" />
            </button>
          </div>

          <!-- Body -->
          <div class="px-5 py-5 overflow-y-auto">
            <slot />
          </div>

          <!-- Footer -->
          <div v-if="$slots.footer" class="flex items-center justify-end gap-3 px-5 py-4 border-t border-white/8 shrink-0">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import AppIcon from '@/components/AppIcon.vue'

type MaxWidth = 'sm' | 'md' | 'lg' | 'xl'

const props = withDefaults(defineProps<{
  isOpen:   boolean
  maxWidth?: MaxWidth
}>(), {
  maxWidth: 'md',
})

const emit = defineEmits<{ close: [] }>()

const maxWidthClass: Record<MaxWidth, string> = {
  sm: 'max-w-sm',
  md: 'max-w-md',
  lg: 'max-w-lg',
  xl: 'max-w-xl',
}[props.maxWidth]
</script>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.2s ease;
}
.modal-fade-enter-active .modal-card,
.modal-fade-leave-active .modal-card {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}
.modal-fade-enter-from .modal-card,
.modal-fade-leave-to .modal-card {
  opacity: 0;
  transform: scale(0.96) translateY(8px);
}
</style>
