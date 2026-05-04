<template>
  <AppLayout>
    <template #sidebar>
      <AssistantSidebar @select-session="goToChat" @new-session="goToChat" />
    </template>

    <template #header-left>
      <button
        @click="router.push('/app/assistant')"
        class="w-8 h-8 flex items-center justify-center rounded-xl text-white/40
               hover:text-white/80 hover:bg-white/8 transition-colors shrink-0"
      >
        <AppIcon name="ui/arrow-left" size="sm" />
      </button>
      <span class="text-sm font-semibold text-white/70">Recordatorios</span>
    </template>
    <template #header-right>
      <UserMenu back-url="/app" />
    </template>

    <div class="h-full overflow-y-auto pretty-scroll pt-[84px] pb-8">

      <!-- Period dropdown -->
      <div class="relative px-6 pt-4 pb-2" ref="dropdownRef">
        <button
          @click="dropdownOpen = !dropdownOpen"
          class="flex items-center gap-2 px-3 py-1.5 rounded-xl glass text-sm text-white/70
                 hover:text-white/90 transition-colors cursor-pointer"
        >
          {{ activePeriod.label }}
          <AppIcon
            name="ui/chevron-down"
            size="xs"
            :class="dropdownOpen ? 'rotate-180' : 'rotate-0'"
            class="transition-transform duration-150 text-white/40"
          />
        </button>

        <Transition name="dd">
          <div
            v-if="dropdownOpen"
            class="absolute top-full left-6 mt-1 w-52 glass rounded-2xl py-2 z-50 shadow-glass"
          >
            <template v-for="group in periodGroups" :key="group.label">
              <p class="text-[10px] font-semibold uppercase tracking-widest text-white/25 px-3 pt-2 pb-1">
                {{ group.label }}
              </p>
              <button
                v-for="p in group.options"
                :key="p.value"
                @click="setPeriod(p)"
                class="w-full text-left px-3 py-1.5 text-sm transition-colors cursor-pointer flex items-center justify-between"
                :class="period === p.value
                  ? 'text-brand-300'
                  : 'text-white/55 hover:text-white/85 hover:bg-white/6'"
              >
                {{ p.label }}
                <AppIcon v-if="period === p.value" name="ui/check" size="xs" class="text-brand-400" />
              </button>
            </template>
          </div>
        </Transition>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex flex-col gap-3 px-6 pt-4">
        <div v-for="i in 4" :key="i" class="h-20 rounded-2xl bg-white/5 animate-pulse" />
      </div>

      <template v-else>
        <!-- Upcoming -->
        <section class="px-6 pt-4">
          <p class="text-[10px] font-semibold uppercase tracking-widest text-white/25 mb-3">
            Próximos
          </p>
          <div v-if="upcoming.length === 0" class="text-sm text-white/25 py-4">
            Sin eventos en este período
          </div>
          <div v-else class="flex flex-col gap-2">
            <EventCard
              v-for="event in upcoming"
              :key="event.id"
              :event="event"
              @update="handleUpdate"
              @cancel="handleCancel"
              @snooze="handleSnooze"
            />
          </div>
        </section>

        <!-- Past — collapsible -->
        <section class="px-6 pt-6">
          <button
            class="flex items-center gap-2 text-[10px] font-semibold uppercase tracking-widest
                   text-white/25 hover:text-white/45 transition-colors mb-3 cursor-pointer"
            @click="pastExpanded = !pastExpanded"
          >
            <AppIcon
              name="ui/chevron-down"
              size="xs"
              :class="pastExpanded ? 'rotate-0' : '-rotate-90'"
              class="transition-transform duration-150"
            />
            Pasados
          </button>
          <div v-if="pastExpanded">
            <div v-if="past.length === 0" class="text-sm text-white/25 py-4">
              Sin eventos pasados en este período
            </div>
            <div v-else class="flex flex-col gap-2">
              <EventCard
                v-for="event in past"
                :key="event.id"
                :event="event"
                @update="handleUpdate"
                @cancel="handleCancel"
                @snooze="handleSnooze"
              />
            </div>
          </div>
        </section>
      </template>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import AppLayout        from '@/components/AppLayout.vue'
import AppIcon          from '@/components/AppIcon.vue'
import UserMenu         from '@/components/UserMenu.vue'
import AssistantSidebar from '@/components/assistant/AssistantSidebar.vue'
import EventCard        from '@/components/assistant/EventCard.vue'
import { useEvents }    from '@/composables/assistant/useEvents'
import type { AssistantEvent } from '@/types'

const router = useRouter()
const { events, loading, loadEvents, cancelEvent, snoozeEvent, updateEvent, replaceEvent, removeEvent } = useEvents()

const pastExpanded = ref(false)
const dropdownOpen = ref(false)
const dropdownRef  = ref<HTMLElement | null>(null)
const period       = ref('3m')

// ── Period helpers ────────────────────────────────────────────────────────────

function isoDate(d: Date) {
    return d.toISOString().split('T')[0]
}

function startOfWeek(d: Date): Date {
    const day  = d.getDay()
    const diff = day === 0 ? -6 : 1 - day
    const s    = new Date(d)
    s.setDate(d.getDate() + diff)
    s.setHours(0, 0, 0, 0)
    return s
}

function endOfWeek(d: Date): Date {
    const e = new Date(startOfWeek(d))
    e.setDate(e.getDate() + 6)
    return e
}

function rangeThisWeek()  {
    const t = new Date()
    return { from: isoDate(startOfWeek(t)), to: isoDate(endOfWeek(t)) }
}

function rangeNextWeek()  {
    const t   = new Date()
    const mon = new Date(startOfWeek(t))
    mon.setDate(mon.getDate() + 7)
    const sun = new Date(mon)
    sun.setDate(sun.getDate() + 6)
    return { from: isoDate(mon), to: isoDate(sun) }
}

function rangeThisMonth() {
    const t = new Date()
    return {
        from: isoDate(new Date(t.getFullYear(), t.getMonth(), 1)),
        to:   isoDate(new Date(t.getFullYear(), t.getMonth() + 1, 0)),
    }
}

function rangeNext(days: number) {
    const from = new Date()
    from.setDate(from.getDate() - 30)
    const to = new Date()
    to.setDate(to.getDate() + days)
    return { from: isoDate(from), to: isoDate(to) }
}

function rangeThisYear() {
    const t = new Date()
    return {
        from: isoDate(new Date(t.getFullYear(), 0, 1)),
        to:   isoDate(new Date(t.getFullYear(), 11, 31)),
    }
}

function rangeNextYear() {
    const t = new Date()
    return {
        from: isoDate(new Date(t.getFullYear() + 1, 0, 1)),
        to:   isoDate(new Date(t.getFullYear() + 1, 11, 31)),
    }
}

// ── Period catalog ────────────────────────────────────────────────────────────

interface PeriodOption {
    label: string
    value: string
    range: () => { from: string; to: string }
}

const periodGroups: { label: string; options: PeriodOption[] }[] = [
    {
        label: 'Semanas',
        options: [
            { label: 'Esta semana',     value: 'this-week',  range: rangeThisWeek },
            { label: 'Próxima semana',  value: 'next-week',  range: rangeNextWeek },
        ],
    },
    {
        label: 'Meses',
        options: [
            { label: 'Este mes',         value: 'this-month', range: rangeThisMonth },
            { label: 'Próximos 3 meses', value: '3m',         range: () => rangeNext(90) },
            { label: 'Próximos 6 meses', value: '6m',         range: () => rangeNext(180) },
        ],
    },
    {
        label: 'Año',
        options: [
            { label: 'Este año',   value: 'this-year', range: rangeThisYear },
            { label: 'Próximo año', value: 'next-year', range: rangeNextYear },
        ],
    },
]

const allPeriods = periodGroups.flatMap((g) => g.options)

const activePeriod = computed(
    () => allPeriods.find((p) => p.value === period.value) ?? allPeriods[2],
)

// ── Dropdown close on outside click ──────────────────────────────────────────

function onClickOutside(e: MouseEvent) {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target as Node)) {
        dropdownOpen.value = false
    }
}

onMounted(() => document.addEventListener('mousedown', onClickOutside))
onUnmounted(() => document.removeEventListener('mousedown', onClickOutside))

// ── Data ─────────────────────────────────────────────────────────────────────

const now = new Date().toISOString()

const upcoming = computed(() =>
    events.value
        .filter((e) => e.event_at >= now && e.status !== 'cancelled')
        .sort((a, b) => a.event_at.localeCompare(b.event_at)),
)

const past = computed(() =>
    events.value
        .filter((e) => e.event_at < now)
        .sort((a, b) => b.event_at.localeCompare(a.event_at)),
)

function setPeriod(p: PeriodOption) {
    period.value    = p.value
    dropdownOpen.value = false
}

watch(period, () => loadEvents(activePeriod.value.range()))

onMounted(() => loadEvents(activePeriod.value.range()))

async function handleUpdate(id: string, payload: Partial<AssistantEvent>) {
    const updated = await updateEvent(id, payload)
    replaceEvent(updated)
}

async function handleCancel(id: string, series: boolean) {
    await cancelEvent(id, series)
    if (series) {
        loadEvents(activePeriod.value.range())
    } else {
        removeEvent(id)
    }
}

async function handleSnooze(id: string, until: string) {
    const updated = await snoozeEvent(id, until)
    replaceEvent(updated)
}

function goToChat(sessionId: string) {
    router.push({ path: '/app/assistant', query: { session: sessionId } })
}
</script>

<style scoped>
.dd-enter-active,
.dd-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}
.dd-enter-from,
.dd-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
