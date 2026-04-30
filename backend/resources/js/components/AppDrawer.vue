<template>
  <Teleport to="body">
    <!-- Backdrop -->
    <Transition name="fade">
      <div
        v-if="open"
        class="fixed inset-0 z-[199] bg-black/30"
        @click="emit('close')"
      />
    </Transition>

    <!-- Panel -->
    <Transition name="slide">
      <div
        v-if="open"
        class="fixed left-3 top-3 bottom-3 w-72 z-[200] bg-white/6 backdrop-blur-xl rounded-2xl flex flex-col overflow-hidden"
      >
        <!-- Header -->
        <div class="flex items-center justify-start px-4 py-3 shrink-0 border-b border-white/8">
          <button
            class="w-8 h-8 flex items-center justify-center rounded-xl text-white/50
                   hover:text-white/80 hover:bg-white/8 transition-colors cursor-pointer"
            @click="emit('close')"
          >
            <AppIcon name="ui/x" size="sm" />
          </button>
        </div>

        <!-- Contenido -->
        <div class="flex-1 overflow-y-auto">
          <slot />
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import AppIcon from '@/components/AppIcon.vue'

defineProps<{ open: boolean }>()
const emit = defineEmits<{ close: [] }>()
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-enter-active,
.slide-leave-active {
  transition: transform 0.25s ease;
}
.slide-enter-from,
.slide-leave-to {
  transform: translateX(-100%);
}
</style>
