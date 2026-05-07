<template>
  <AdminDetailPanel :open="!!jobId" max-width="xl" @close="close">
    <template #header>
      <template v-if="detail">
        <p class="font-medium text-white/90 font-mono text-sm">{{ detail.display_name }}</p>
        <p class="text-xs text-white/40 mt-0.5">Job #{{ detail.id }} · queue: {{ detail.queue }}</p>
        <p class="text-xs text-white/25 mt-0.5">
          {{ detail.attempts }} attempts · available {{ formatTs(detail.available_at) }}
        </p>
      </template>
    </template>

    <div v-if="loadingDetail" class="py-10 text-center text-sm text-white/25">Loading…</div>

    <template v-else-if="detail">
      <div v-if="detail.reminder" class="space-y-4">
        <p class="text-[10px] font-bold uppercase tracking-widest text-white/30">Reminder</p>

        <div class="rounded-xl border border-white/8 bg-white/3 p-4 space-y-3">
          <div class="flex items-start justify-between gap-4">
            <p class="text-sm text-white/80 leading-relaxed">{{ detail.reminder.message }}</p>
            <span
              class="shrink-0 text-[10px] font-semibold uppercase tracking-wider px-2 py-0.5 rounded-full"
              :class="statusClass(detail.reminder.status)"
            >{{ detail.reminder.status }}</span>
          </div>

          <div class="grid grid-cols-2 gap-x-6 gap-y-2 text-xs">
            <div>
              <p class="text-white/30 mb-0.5">Fire at</p>
              <p class="text-white/70 tabular-nums">{{ formatDt(detail.reminder.fire_at) }}</p>
            </div>
            <div v-if="detail.reminder.fired_at">
              <p class="text-white/30 mb-0.5">Fired at</p>
              <p class="text-white/70 tabular-nums">{{ formatDt(detail.reminder.fired_at) }}</p>
            </div>
          </div>
        </div>

        <div v-if="detail.reminder.event">
          <p class="text-[10px] font-bold uppercase tracking-widest text-white/30 mb-3">Event</p>
          <div class="rounded-xl border border-white/8 bg-white/3 p-4 space-y-3">
            <p class="text-sm text-white/80">{{ detail.reminder.event.content }}</p>
            <div class="grid grid-cols-2 gap-x-6 gap-y-2 text-xs">
              <div>
                <p class="text-white/30 mb-0.5">Event at</p>
                <p class="text-white/70 tabular-nums">{{ formatDt(detail.reminder.event.event_at) }}</p>
              </div>
              <div>
                <p class="text-white/30 mb-0.5">Type</p>
                <p class="text-white/70">{{ detail.reminder.event.type }}</p>
              </div>
              <div v-if="detail.reminder.event.user" class="col-span-2">
                <p class="text-white/30 mb-0.5">User</p>
                <p class="text-white/70">
                  {{ detail.reminder.event.user.name }}
                  <span class="text-white/30">· {{ detail.reminder.event.user.email }}</span>
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="py-10 text-center text-sm text-white/25">No reminder linked to this job</div>
    </template>
  </AdminDetailPanel>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAdminJobs } from '@/composables/admin/useAdminJobs'
import { useDate } from '@/composables/core/useDate'
import AdminDetailPanel from '@/components/Admin/AdminDetailPanel.vue'

const { detail, loadingDetail, fetchDetail } = useAdminJobs()
const { formatTimestamp, formatEventAt } = useDate()

const route  = useRoute()
const router = useRouter()
const jobId  = ref<number | null>(Number(route.query.job) || null)

const load = (id: number | null) => { if (id) fetchDetail(id) }

watch(() => route.query.job, (val) => {
  jobId.value = Number(val) || null
  load(jobId.value)
})

onMounted(() => load(jobId.value))

const close = () => {
  const q = { ...route.query }
  delete q.job
  router.replace({ query: q })
}

const formatTs = (ts: number) => formatTimestamp(ts)
const formatDt = (dt: string) => formatEventAt(dt.includes('T') ? dt : dt.replace(' ', 'T') + 'Z')

const statusClass = (s: string) => ({
  pending: 'bg-amber-500/15 text-amber-400',
  fired:   'bg-emerald-500/15 text-emerald-400',
  failed:  'bg-red-500/15 text-red-400',
}[s] ?? 'bg-white/10 text-white/40')
</script>
