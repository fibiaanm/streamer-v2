<template>
  <div class="h-screen w-screen overflow-hidden bg-canvas dark:bg-canvas-dark transition-colors duration-300">

    <!-- Decorative blobs -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
      <div
        class="absolute -top-40 -left-40 w-[600px] h-[600px] rounded-full
               bg-brand-200/40 dark:bg-brand-600/15 blur-3xl"
        :style="blobStyle(blob1, 9)"
      />
      <div
        class="absolute top-1/3 -right-32 w-[500px] h-[500px] rounded-full
               bg-live-200/30 dark:bg-live-600/10 blur-3xl"
        :style="blobStyle(blob2, 11)"
      />
      <div
        class="absolute bottom-0 left-1/3 w-[400px] h-[400px] rounded-full
               bg-brand-300/20 dark:bg-brand-800/20 blur-3xl"
        :style="blobStyle(blob3, 8)"
      />
    </div>

    <!-- Content -->
    <slot />

  </div>
</template>

<script setup lang="ts">
import { ref, onUnmounted } from 'vue'

interface BlobState { x: number; y: number; scale: number }

const randomState = (): BlobState => ({
  x:     (Math.random() - 0.5) * 100,
  y:     (Math.random() - 0.5) * 80,
  scale: 0.85 + Math.random() * 0.30,
})

const createBlob = (intervalMs: number, delay = 0) => {
  const state = ref<BlobState>({ x: 0, y: 0, scale: 1 })
  let timer: ReturnType<typeof setTimeout>
  let interval: ReturnType<typeof setInterval>

  timer = setTimeout(() => {
    state.value = randomState()
    interval = setInterval(() => { state.value = randomState() }, intervalMs)
  }, delay)

  const stop = () => { clearTimeout(timer); clearInterval(interval) }

  return { state, stop }
}

const blobStyle = (blob: BlobState, durationS: number) => ({
  transform:  `translate(${blob.x}px, ${blob.y}px) scale(${blob.scale})`,
  transition: `transform ${durationS}s ease-in-out`,
})

const { state: blob1, stop: stopBlob1 } = createBlob(9000,     0)
const { state: blob2, stop: stopBlob2 } = createBlob(11000, 3000)
const { state: blob3, stop: stopBlob3 } = createBlob(8000,  6000)

onUnmounted(() => { stopBlob1(); stopBlob2(); stopBlob3() })
</script>
