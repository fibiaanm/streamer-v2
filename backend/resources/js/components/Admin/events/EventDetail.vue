<template>
  <AdminDetailPanel :open="!!eventId" max-width="2xl" @close="close">
    <template #header>
      <template v-if="detail">
        <p class="font-medium text-white/90 truncate">{{ detail.content }}</p>
        <p class="text-xs text-white/40 mt-0.5">{{ detail.user.name }} · {{ detail.user.email }}</p>
        <p class="text-xs text-white/25 mt-0.5">
          ID {{ detail.id }}
          · <span :class="kindClass(detail.kind)">{{ detail.kind }}</span>
          · {{ fmtDate(detail.event_at) }}
        </p>
      </template>
    </template>

    <div v-if="loadingDetail" class="py-10 text-center text-sm text-white/25">Loading event…</div>

    <template v-else-if="detail">
      <!-- Main info -->
      <div class="rounded-xl border border-white/8 bg-white/3 p-4 space-y-2 mb-4">
        <Row label="Type"    :value="detail.type" />
        <Row label="Status">
          <span class="text-xs px-2 py-0.5 rounded-full font-medium" :class="statusClass(detail.status)">
            {{ detail.status }}
          </span>
        </Row>
        <Row label="Event at"  :value="fmtDate(detail.event_at)" />
        <Row v-if="detail.event_end"    label="Ends at"    :value="fmtDate(detail.event_end)" />
        <Row v-if="detail.occurrence_at" label="Occurrence" :value="fmtDate(detail.occurrence_at)" />
        <Row v-if="detail.series_ends_at" label="Series ends" :value="fmtDate(detail.series_ends_at)" />
        <Row label="Created"   :value="fmtDate(detail.created_at)" />
      </div>

      <!-- Recurrence -->
      <div v-if="detail.recurrence_rule || detail.master" class="rounded-xl border border-violet-500/15 bg-violet-500/5 p-4 space-y-2 mb-4">
        <p class="text-[10px] font-bold uppercase tracking-widest text-violet-400/70 mb-2">Recurrence</p>
        <Row v-if="detail.recurrence_rule" label="Rule" :value="detail.recurrence_rule" mono />
        <template v-if="detail.master">
          <Row label="Master ID"      :value="String(detail.master.id)" />
          <Row label="Master content" :value="detail.master.content" />
          <Row v-if="detail.master.recurrence_rule" label="Master rule" :value="detail.master.recurrence_rule" mono />
        </template>
      </div>

      <!-- Reminders -->
      <div>
        <p class="text-[10px] font-bold uppercase tracking-widest text-white/25 mb-2">
          Reminders ({{ detail.reminders.length }})
        </p>
        <div v-if="detail.reminders.length" class="space-y-1.5">
          <div
            v-for="r in detail.reminders"
            :key="r.id"
            class="flex items-center gap-3 rounded-xl border border-white/6 bg-white/2 px-3 py-2.5"
          >
            <span class="text-[10px] font-bold uppercase tracking-widest px-1.5 py-0.5 rounded-md shrink-0" :class="kindBadge(r.kind)">
              {{ r.kind }}
            </span>
            <div class="flex-1 min-w-0">
              <p class="text-xs text-white/70">{{ fmtDate(r.fire_at) }}</p>
              <p v-if="r.fired_at" class="text-[11px] text-white/30">fired {{ fmtDate(r.fired_at) }}</p>
            </div>
            <span class="text-[11px] px-2 py-0.5 rounded-full font-medium shrink-0" :class="reminderStatusClass(r.status)">
              {{ r.status }}
            </span>
          </div>
        </div>
        <p v-else class="text-sm text-white/25 py-4 text-center">No reminders</p>
      </div>
    </template>
  </AdminDetailPanel>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAdminEvents } from '@/composables/admin/useAdminEvents'
import AdminDetailPanel from '@/components/Admin/AdminDetailPanel.vue'
import { useDate } from '@/composables/core/useDate'

const { detail, loadingDetail, fetchDetail } = useAdminEvents()
const { formatDatetime } = useDate()

const route   = useRoute()
const router  = useRouter()
const eventId = ref<number | null>(Number(route.query.event) || null)

const load = (id: number | null) => { if (id) fetchDetail(id) }

watch(() => route.query.event, (val) => {
  eventId.value = Number(val) || null
  load(eventId.value)
})

onMounted(() => load(eventId.value))

const close = () => {
  const q = { ...route.query }
  delete q.event
  router.replace({ query: q })
}

const fmtDate = (d: string | null) => d ? formatDatetime(d.includes('T') ? d : d + 'Z') : '—'

const statusClass = (s: string) => ({
  active:    'bg-emerald-500/15 text-emerald-400',
  cancelled: 'bg-white/8 text-white/35',
  completed: 'bg-blue-500/15 text-blue-400',
}[s] ?? 'bg-white/8 text-white/35')

const kindClass = (k: string) => ({
  single:     'text-white/30',
  master:     'text-violet-400',
  occurrence: 'text-violet-300/60',
}[k] ?? '')

const kindBadge = (k: string) => ({
  digest: 'bg-blue-500/15 text-blue-400',
  ahead:  'bg-amber-500/15 text-amber-400',
  inline: 'bg-rose-500/15 text-rose-400',
}[k] ?? 'bg-white/8 text-white/35')

const reminderStatusClass = (s: string) => ({
  pending:   'bg-yellow-500/15 text-yellow-400',
  fired:     'bg-emerald-500/15 text-emerald-400',
  cancelled: 'bg-white/8 text-white/30',
  failed:    'bg-red-500/15 text-red-400',
}[s] ?? 'bg-white/8 text-white/30')
</script>

<!-- Utility sub-component inlined to avoid a separate file for a simple label+value row -->
<script lang="ts">
import { defineComponent, h } from 'vue'
const Row = defineComponent({
  props: { label: String, value: String, mono: Boolean },
  slots: ['default'],
  setup(props, { slots }) {
    return () => h('div', { class: 'flex items-start gap-3' }, [
      h('span', { class: 'text-xs text-white/30 w-24 shrink-0 pt-px' }, props.label),
      slots.default
        ? h('div', { class: 'flex-1 min-w-0' }, slots.default())
        : h('span', { class: ['text-xs text-white/70 flex-1 min-w-0 break-all', props.mono ? 'font-mono' : ''] }, props.value ?? '—'),
    ])
  },
})
export { Row }
</script>
