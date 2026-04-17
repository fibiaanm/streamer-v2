<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="opacity-0 translate-y-3"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 translate-y-3"
    >
      <div
        v-if="toasts.length > 0 || leavingIds.length > 0"
        class="fixed bottom-6 right-6 z-[9500] w-80"
        @mouseenter="isHovered = true"
        @mouseleave="isHovered = false"
      >

        <!-- Scroll wrapper -->
        <div
          ref="scrollRef"
          class="[&::-webkit-scrollbar]:hidden"
          style="scrollbar-width: none"
          :class="isExpanded ? 'overflow-y-auto max-h-[50vh]' : 'overflow-visible'"
        >
          <!-- Card container — height animates between stack and list -->
          <div
            class="relative"
            :style="{
              height: `${containerHeight}px`,
              transition: `height 250ms ${EASE}`,
            }"
            :class="{ 'cursor-pointer': !isExpanded }"
            @click="!isExpanded && expand()"
          >

            <!-- Count badge -->
            <Transition
              enter-active-class="transition duration-200 ease-out"
              enter-from-class="opacity-0 scale-75"
              enter-to-class="opacity-100 scale-100"
              leave-active-class="transition duration-150 ease-in"
              leave-from-class="opacity-100 scale-100"
              leave-to-class="opacity-0 scale-75"
            >
              <div
                v-if="!isExpanded && visibleToasts.length > 1"
                class="absolute -top-0.5 -left-2 z-20 pointer-events-none
                       flex items-center justify-center
                       min-w-[18px] h-[18px] px-1 rounded-full
                       text-[10px] font-bold text-white/70
                       bg-white/10 border border-white/15"
              >
                {{ visibleToasts.length }}
              </div>
            </Transition>

            <!-- Same nodes always — position and transform animate between states -->
            <div
              v-for="(toast, i) in toasts"
              :key="toast.id"
              :ref="(el) => setCardEl(toast.id, el as HTMLElement | null)"
              class="absolute left-0 right-0"
              :style="cardStyle(toast.id, i)"
            >
              <AppToast
                :toast="toast"
                :pause-timer="isExpanded || isHovered"
                :compact="isCompact"
                @remove="handleRemove"
              />
            </div>

          </div>
        </div>

        <!-- Footer: siempre en el DOM para reservar espacio (evita drift al colapsar) -->
        <div
          class="flex items-center justify-between gap-2 mt-2 transition-opacity duration-200"
          :class="isExpanded ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'"
        >
          <AppToastButton @click="collapse">Colapsar</AppToastButton>
          <AppToastButton danger @click="clearAll">Limpiar todo</AppToastButton>
        </div>

      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick, onUpdated } from 'vue'
import type { CSSProperties } from 'vue'
import { useToasts } from '@/composables/core/useToasts'
import AppToast from '@/components/AppToast.vue'
import AppToastButton from '@/components/AppToastButton.vue'

const { toasts, remove, clear } = useToasts()

// ── State ──────────────────────────────────────────────────────────────────────
const isExpanded  = ref(false)
const isCompact   = ref(true)
const isHovered   = ref(false)
const scrollRef   = ref<HTMLElement | null>(null)

// ── Leave animation tracking ───────────────────────────────────────────────────
// Cards in leavingIds remain in the DOM but animate to translateX(right)
const leavingIds    = ref<string[]>([])
const leavingStyles = ref<Record<string, CSSProperties>>({})

// ── Card height measurement ────────────────────────────────────────────────────
const cardHeights = ref<Record<string, number>>({})
const cardEls     = new Map<string, HTMLElement>()

const setCardEl = (id: string, el: HTMLElement | null) => {
  if (el) {
    cardEls.set(id, el)
    cardHeights.value[id] = el.offsetHeight
  } else {
    cardEls.delete(id)
    delete cardHeights.value[id]
  }
}

onUpdated(() => {
  cardEls.forEach((el, id) => {
    const h = el.offsetHeight
    if (cardHeights.value[id] !== h) cardHeights.value[id] = h
  })
})

const getH = (id: string) => cardHeights.value[id] ?? 76

// ── Constants ──────────────────────────────────────────────────────────────────
const GAP       = 8
const PEEK      = 12
const EASE      = 'cubic-bezier(0.4, 0, 0.2, 1)'
const T_MOVE    = `all 250ms ${EASE}`
const T_LEAVE   = `all 280ms ${EASE}`
const LEAVE_MS  = 280

// ── Visible toasts (excludes those currently animating out) ───────────────────
const visibleToasts = computed(() =>
  toasts.value.filter(t => !leavingIds.value.includes(t.id))
)

// Altura bloqueada durante clearAll: evita que el contenedor encoja mientras
// las cards todavía están animando hacia la derecha
const lockedHeight = ref<number | null>(null)

// ── Container height (based on visible cards only) ────────────────────────────
const containerHeight = computed(() => {
  if (lockedHeight.value !== null) return lockedHeight.value

  const visible = visibleToasts.value
  const n = visible.length
  if (n === 0) return 0

  if (!isExpanded.value) {
    const frontH = getH(visible[n - 1].id)
    return n > 1 ? frontH + PEEK : frontH
  }

  return visible.reduce((sum, t, i) => {
    return sum + getH(t.id) + (i < n - 1 ? GAP : 0)
  }, 0)
})

// ── Card position style ────────────────────────────────────────────────────────
const cardStyle = (id: string, i: number): CSSProperties => {
  // Leaving: slide to the right from the snapshotted position
  if (leavingIds.value.includes(id)) {
    return {
      ...leavingStyles.value[id],
      transform: 'translateX(calc(100% + 1.5rem))',
      opacity: '0',
      pointerEvents: 'none',
      transition: T_LEAVE,
    }
  }

  const visible = visibleToasts.value
  const n = visible.length
  const vi = visible.findIndex(t => t.id === id)

  if (vi === -1) return { opacity: '0', pointerEvents: 'none' }

  const fromBottom = n - 1 - vi  // 0 = newest visible

  if (!isExpanded.value) {
    if (fromBottom === 0) {
      return {
        bottom: '0', top: 'auto',
        zIndex: n, opacity: '1',
        transform: 'none',
        pointerEvents: 'auto',
        transition: T_MOVE,
      }
    }
    if (fromBottom === 1) {
      return {
        bottom: '0', top: 'auto',
        zIndex: n - 1, opacity: '0.45',
        transform: `translateY(-${PEEK}px) scaleX(0.93)`,
        transformOrigin: 'top center',
        pointerEvents: 'none',
        transition: T_MOVE,
      }
    }
    return {
      bottom: '0', top: 'auto',
      zIndex: 1, opacity: '0',
      transform: `translateY(-${PEEK}px) scaleX(0.86)`,
      transformOrigin: 'top center',
      pointerEvents: 'none',
      transition: T_MOVE,
    }
  }

  // Expanded: position from the top, oldest first
  let top = 0
  for (let j = 0; j < vi; j++) {
    top += getH(visible[j].id) + GAP
  }

  return {
    top: `${top}px`, bottom: 'auto',
    zIndex: vi + 1, opacity: '1',
    transform: 'none',
    pointerEvents: 'auto',
    transition: T_MOVE,
  }
}

// ── Remove with slide-out animation ───────────────────────────────────────────
const handleRemove = (id: string) => {
  if (leavingIds.value.includes(id)) return

  // Snapshot the card's current position so it slides from the right place
  const idx = toasts.value.findIndex(t => t.id === id)
  if (idx !== -1) {
    leavingStyles.value[id] = cardStyle(id, idx)
  }

  leavingIds.value = [...leavingIds.value, id]

  setTimeout(() => {
    remove(id)
    leavingIds.value = leavingIds.value.filter(x => x !== id)
    delete leavingStyles.value[id]
  }, LEAVE_MS)
}

// ── Expand / collapse ──────────────────────────────────────────────────────────
let compactTimer: ReturnType<typeof setTimeout> | null = null

const scrollToBottom = () => {
  if (scrollRef.value) scrollRef.value.scrollTop = scrollRef.value.scrollHeight
}

const expand = () => {
  isExpanded.value = true
  if (compactTimer) clearTimeout(compactTimer)
  compactTimer = setTimeout(() => { isCompact.value = false }, 120)
  nextTick(scrollToBottom)
}

const collapse = () => {
  if (compactTimer) clearTimeout(compactTimer)
  isCompact.value = true
  isExpanded.value = false
}

const clearAll = () => {
  const ids = visibleToasts.value.map(t => t.id)
  if (ids.length === 0) { collapse(); return }

  // Bloquear la altura actual ANTES de cualquier cambio,
  // así el contenedor no encoge mientras las cards animan hacia la derecha
  lockedHeight.value = containerHeight.value

  // Snapshot posiciones ANTES de tocar leavingIds
  const snapshots: Record<string, CSSProperties> = {}
  toasts.value.forEach((toast, i) => {
    if (ids.includes(toast.id)) {
      snapshots[toast.id] = cardStyle(toast.id, i)
    }
  })
  leavingStyles.value = { ...leavingStyles.value, ...snapshots }
  leavingIds.value    = [...leavingIds.value, ...ids]

  setTimeout(() => {
    lockedHeight.value = null
    clear()
    leavingIds.value = leavingIds.value.filter(id => !ids.includes(id))
    ids.forEach(id => delete leavingStyles.value[id])
    collapse()
  }, LEAVE_MS)
}

// Auto-scroll when a new toast arrives while expanded
watch(() => toasts.value.length, (len) => {
  if (len === 0 && leavingIds.value.length === 0) collapse()
  else if (isExpanded.value) nextTick(scrollToBottom)
})
</script>
