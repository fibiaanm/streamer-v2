<template>
  <div
    class="relative overflow-hidden rounded-xl
           bg-white/6 backdrop-blur-2xl backdrop-saturate-200
           border border-white/10 shadow-glass"
    :style="{ borderLeft: `3px solid ${typeColor}` }"
  >
    <div class="p-3.5">
      <div class="flex items-start gap-3">

        <!-- Type icon -->
        <span class="mt-0.5 shrink-0 opacity-80" :style="{ color: typeColor }">
          <AppIcon :name="typeIcon" size="sm" />
        </span>

        <!-- Text -->
        <div class="flex-1 min-w-0">
          <p class="text-sm font-semibold text-white/90 leading-snug">{{ toast.title }}</p>
          <p
            v-if="toast.message"
            class="text-xs text-white/50 mt-0.5 leading-relaxed"
            :class="compact ? 'line-clamp-1' : 'line-clamp-3'"
          >
            {{ toast.message }}
          </p>

          <!-- Action buttons — only in full mode -->
          <div v-if="!compact && toast.actions?.length" class="flex gap-2 mt-2.5">
            <button
              v-for="action in toast.actions"
              :key="action.label"
              class="text-xs font-medium px-2.5 py-1 rounded-md cursor-pointer transition-opacity hover:opacity-80"
              :style="{ color: typeColor, background: typeColor + '1a' }"
              @click.stop="action.onClick()"
            >
              {{ action.label }}
            </button>
          </div>
        </div>

        <!-- Close — only in full mode -->
        <button
          v-if="!compact"
          class="shrink-0 text-white/25 hover:text-white/60 transition-colors cursor-pointer mt-0.5"
          @click.stop="emit('remove', toast.id)"
        >
          <AppIcon name="ui/x" size="xs" />
        </button>

      </div>
    </div>

    <!-- Progress bar — always visible when toast has a duration -->
    <div v-if="toast.duration" class="absolute bottom-0 inset-x-0 h-0.5 bg-white/5">
      <div
        class="h-full"
        :style="{ width: `${progress}%`, background: typeColor + 'cc', transition: 'none' }"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted } from 'vue'
import type { Toast } from '@/composables/core/useToasts'
import AppIcon from '@/components/AppIcon.vue'

const props = defineProps<{
  toast: Toast
  pauseTimer: boolean
  compact?: boolean
}>()

const emit = defineEmits<{
  remove: [id: string]
}>()

// ── Type maps ──────────────────────────────────────────────────────────────────
const TYPE_COLORS: Record<Toast['type'], string> = {
  success: '#10b981',
  error:   '#f43f5e',
  warning: '#f59e0b',
  info:    '#3b82f6',
}

const TYPE_ICONS: Record<Toast['type'], string> = {
  success: 'ui/check-circle',
  error:   'ui/x-circle',
  warning: 'ui/alert-triangle',
  info:    'ui/info',
}

const typeColor = TYPE_COLORS[props.toast.type]
const typeIcon  = TYPE_ICONS[props.toast.type]

// ── Timer ──────────────────────────────────────────────────────────────────────
const TICK_MS = 50
const progress = ref(100)
let remaining  = props.toast.duration ?? 0
let intervalId: ReturnType<typeof setInterval> | null = null

const startTick = () => {
  if (!props.toast.duration || props.pauseTimer || intervalId !== null) return
  intervalId = setInterval(() => {
    remaining -= TICK_MS
    progress.value = Math.max(0, (remaining / props.toast.duration!) * 100)
    if (remaining <= 0) {
      stopTick()
      props.toast.onTimeout?.()
      emit('remove', props.toast.id)
    }
  }, TICK_MS)
}

const stopTick = () => {
  if (intervalId !== null) {
    clearInterval(intervalId)
    intervalId = null
  }
}

watch(() => props.pauseTimer, paused => {
  if (paused) stopTick()
  else startTick()
})

onMounted(() => {
  if (props.toast.duration) startTick()
})

onUnmounted(stopTick)
</script>
