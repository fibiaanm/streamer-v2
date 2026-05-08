<template>
  <div>
    <form class="flex flex-wrap gap-2 mb-5" @submit.prevent="search">
      <input
        v-model="query"
        class="w-56 px-3 py-2 rounded-xl border border-white/10 bg-white/5 text-white/80 placeholder-white/25 text-sm focus:outline-none focus:border-white/20"
        type="text"
        placeholder="User ID or email…"
      />
      <input
        v-model="from"
        class="px-3 py-2 rounded-xl border border-white/10 bg-white/5 text-white/80 text-sm focus:outline-none focus:border-white/20"
        type="date"
      />
      <input
        v-model="to"
        class="px-3 py-2 rounded-xl border border-white/10 bg-white/5 text-white/80 text-sm focus:outline-none focus:border-white/20"
        type="date"
      />
      <select
        v-model="status"
        class="px-3 py-2 rounded-xl border border-white/10 bg-white/5 text-white/60 text-sm focus:outline-none focus:border-white/20"
      >
        <option value="">All statuses</option>
        <option value="active">Active</option>
        <option value="cancelled">Cancelled</option>
        <option value="completed">Completed</option>
      </select>
      <button
        type="submit"
        class="px-4 py-2 rounded-xl border border-white/10 bg-white/5 text-white/60 text-sm hover:text-white/90 hover:bg-white/8 transition-colors cursor-pointer"
      >Search</button>
    </form>

    <div v-if="searched" class="rounded-2xl border border-white/8 bg-white/3 p-5">
      <table class="w-full text-sm border-collapse">
        <thead>
          <tr>
            <th class="text-left text-xs font-semibold uppercase tracking-widest text-white/30 pb-3 border-b border-white/8 px-2">Event</th>
            <th class="text-left text-xs font-semibold uppercase tracking-widest text-white/30 pb-3 border-b border-white/8 px-2">When</th>
            <th class="text-left text-xs font-semibold uppercase tracking-widest text-white/30 pb-3 border-b border-white/8 px-2">User</th>
            <th class="text-center text-xs font-semibold uppercase tracking-widest text-white/30 pb-3 border-b border-white/8 px-2">Status</th>
            <th class="text-center text-xs font-semibold uppercase tracking-widest text-white/30 pb-3 border-b border-white/8 px-2">Reminders</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="e in events"
            :key="e.id"
            class="border-b border-white/4 last:border-0 cursor-pointer transition-colors"
            :class="selectedId === e.id ? 'bg-white/5' : 'hover:bg-white/3'"
            @click="select(e.id)"
          >
            <td class="py-3 px-2">
              <div class="flex items-center gap-2">
                <span class="text-white/80 truncate max-w-[220px]">{{ e.content }}</span>
                <span class="shrink-0 text-[10px] px-1.5 py-0.5 rounded-md font-medium" :class="kindClass(e.kind)">
                  {{ e.kind }}
                </span>
              </div>
              <span class="text-[11px] text-white/35">{{ e.type }}</span>
            </td>
            <td class="py-3 px-2 text-xs text-white/40 whitespace-nowrap">{{ fmtDate(e.event_at) }}</td>
            <td class="py-3 px-2">
              <p class="text-xs text-white/70">{{ e.user_name }}</p>
              <p class="text-[11px] text-white/30">{{ e.user_email }}</p>
            </td>
            <td class="py-3 px-2 text-center">
              <span class="text-[11px] px-2 py-0.5 rounded-full font-medium" :class="statusClass(e.status)">
                {{ e.status }}
              </span>
            </td>
            <td class="py-3 px-2 text-center tabular-nums text-white/50 text-xs">{{ e.reminder_count }}</td>
          </tr>
          <tr v-if="!events.length && !loading">
            <td colspan="5" class="py-10 text-center text-sm text-white/25">No events found</td>
          </tr>
          <tr v-if="loading">
            <td colspan="5" class="py-10 text-center text-sm text-white/25">Loading…</td>
          </tr>
        </tbody>
      </table>

      <div v-if="pagination && (page > 1 || pagination.has_more)" class="flex items-center justify-center gap-3 pt-5">
        <button
          :disabled="page === 1"
          class="text-xs px-3 py-1.5 rounded-lg border border-white/10 bg-white/5 text-white/50 disabled:opacity-30 hover:text-white/80 transition-colors cursor-pointer"
          @click="goTo(page - 1)"
        >Prev</button>
        <span class="text-xs text-white/30">Page {{ page }}</span>
        <button
          :disabled="!pagination.has_more"
          class="text-xs px-3 py-1.5 rounded-lg border border-white/10 bg-white/5 text-white/50 disabled:opacity-30 hover:text-white/80 transition-colors cursor-pointer"
          @click="goTo(page + 1)"
        >Next</button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAdminEvents } from '@/composables/admin/useAdminEvents'
import { useDate } from '@/composables/core/useDate'

const { events, pagination, loading, fetch } = useAdminEvents()
const { formatDatetime } = useDate()

const route  = useRoute()
const router = useRouter()

const query    = ref('')
const from     = ref('')
const to       = ref('')
const status   = ref('')
const page     = ref(1)
const searched = ref(false)

const selectedId = ref<number | null>(Number(route.query.event) || null)

const buildParams = () => {
  const q    = query.value.trim()
  const isId = /^\d+$/.test(q)
  return {
    ...(q ? (isId ? { user_id: Number(q) } : { email: q }) : {}),
    from:   from.value   || undefined,
    to:     to.value     || undefined,
    status: status.value || undefined,
    page:   page.value,
  }
}

const syncToUrl = (evId: number | null = selectedId.value) => {
  const q: Record<string, string> = {}
  if (query.value.trim()) q.q      = query.value.trim()
  if (from.value)          q.from   = from.value
  if (to.value)            q.to     = to.value
  if (status.value)        q.status = status.value
  if (page.value > 1)      q.page   = String(page.value)
  if (evId)                q.event  = String(evId)
  router.replace({ query: q })
}

const search = () => {
  const params    = buildParams()
  const hasFilter = params.user_id || params.email || params.from || params.to || params.status
  if (!hasFilter) return

  searched.value   = true
  selectedId.value = null
  page.value       = 1
  syncToUrl(null)
  fetch({ ...params, page: 1 })
}

const select = (id: number) => {
  selectedId.value = selectedId.value === id ? null : id
  syncToUrl(selectedId.value)
}

const goTo = (p: number) => {
  page.value = p
  syncToUrl()
  fetch(buildParams())
}

const fmtDate = (d: string) => formatDatetime(d.includes('T') ? d : d + 'Z')

const statusClass = (s: string) => ({
  active:    'bg-emerald-500/15 text-emerald-400',
  cancelled: 'bg-white/8 text-white/35',
  completed: 'bg-blue-500/15 text-blue-400',
}[s] ?? 'bg-white/8 text-white/35')

const kindClass = (k: string) => ({
  single:     'bg-white/6 text-white/30',
  master:     'bg-violet-500/15 text-violet-400',
  occurrence: 'bg-violet-500/8 text-violet-300/60',
}[k] ?? '')

const toDateInput = (d: Date) => d.toISOString().slice(0, 10)

const defaultFrom = () => toDateInput(new Date())
const defaultTo   = () => {
  const d = new Date()
  d.setDate(d.getDate() + 7)
  return toDateInput(d)
}

onMounted(() => {
  query.value  = String(route.query.q      ?? '')
  from.value   = String(route.query.from   ?? defaultFrom())
  to.value     = String(route.query.to     ?? defaultTo())
  status.value = String(route.query.status ?? '')
  page.value   = Number(route.query.page   ?? 1)

  searched.value = true
  fetch(buildParams())
})
</script>
