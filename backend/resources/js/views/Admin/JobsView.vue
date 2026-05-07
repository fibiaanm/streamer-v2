<template>
  <div>
    <form class="flex flex-wrap gap-2 mb-5" @submit.prevent="search">
      <input
        v-model="idFilter"
        class="w-40 px-3 py-2 rounded-xl border border-white/10 bg-white/5 text-white/80 placeholder-white/25 text-sm focus:outline-none focus:border-white/20"
        type="text"
        inputmode="numeric"
        placeholder="Job ID…"
      />
      <button
        type="submit"
        class="px-4 py-2 rounded-xl border border-white/10 bg-white/5 text-white/60 text-sm hover:text-white/90 hover:bg-white/8 transition-colors cursor-pointer"
      >Search</button>
      <button
        v-if="idFilter"
        type="button"
        class="px-4 py-2 rounded-xl border border-white/10 bg-white/5 text-white/40 text-sm hover:text-white/70 transition-colors cursor-pointer"
        @click="clearFilter"
      >Clear</button>
    </form>

    <div class="rounded-2xl border border-white/8 bg-white/3 p-5">
      <table class="w-full text-sm border-collapse">
        <thead>
          <tr>
            <th class="text-left text-xs font-semibold uppercase tracking-widest text-white/30 pb-3 border-b border-white/8 px-2">ID</th>
            <th class="text-left text-xs font-semibold uppercase tracking-widest text-white/30 pb-3 border-b border-white/8 px-2">Job</th>
            <th class="text-left text-xs font-semibold uppercase tracking-widest text-white/30 pb-3 border-b border-white/8 px-2">Queue</th>
            <th class="text-right text-xs font-semibold uppercase tracking-widest text-white/30 pb-3 border-b border-white/8 px-2">Attempts</th>
            <th class="text-left text-xs font-semibold uppercase tracking-widest text-white/30 pb-3 border-b border-white/8 px-2">Available at</th>
            <th class="text-left text-xs font-semibold uppercase tracking-widest text-white/30 pb-3 border-b border-white/8 px-2">Created</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="job in jobs" :key="job.id"
            class="border-b border-white/4 last:border-0 transition-colors"
            :class="[
              activeId === job.id ? 'bg-white/5' : 'hover:bg-white/3',
              job.queue === 'assistant' ? 'cursor-pointer' : 'cursor-default',
            ]"
            @click="handleRowClick(job)"
          >
            <td class="py-3 px-2 tabular-nums text-white/50 text-xs">{{ job.id }}</td>
            <td class="py-3 px-2 text-white/80 font-mono text-xs">{{ job.display_name }}</td>
            <td class="py-3 px-2 text-xs">
              <span
                class="px-1.5 py-0.5 rounded-md text-[10px] font-medium"
                :class="job.queue === 'assistant' ? 'bg-brand-500/15 text-brand-400' : 'bg-white/8 text-white/40'"
              >{{ job.queue }}</span>
            </td>
            <td class="py-3 px-2 text-right tabular-nums text-white/60">{{ job.attempts }}</td>
            <td class="py-3 px-2 text-xs text-white/40">{{ formatTs(job.available_at) }}</td>
            <td class="py-3 px-2 text-xs text-white/30">{{ formatTs(job.created_at) }}</td>
          </tr>
          <tr v-if="!jobs.length && !loading">
            <td colspan="6" class="py-10 text-center text-sm text-white/25">No pending jobs</td>
          </tr>
          <tr v-if="loading">
            <td colspan="6" class="py-10 text-center text-sm text-white/25">Loading…</td>
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

    <JobDetail />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAdminJobs } from '@/composables/admin/useAdminJobs'
import { useDate } from '@/composables/core/useDate'
import JobDetail from '@/components/Admin/jobs/JobDetail.vue'
import type { AdminJob } from '@/types/admin'

const { jobs, pagination, loading, fetch } = useAdminJobs()
const { formatTimestamp } = useDate()

const route  = useRoute()
const router = useRouter()

const idFilter = ref('')
const page     = ref(1)
const activeId = ref<number | null>(Number(route.query.job) || null)

const buildParams = () => ({
  ...(idFilter.value.trim() ? { id: Number(idFilter.value.trim()) } : {}),
  page: page.value,
})

const syncToUrl = (jobId: number | null = activeId.value) => {
  const q: Record<string, string> = {}
  if (idFilter.value.trim()) q.id   = idFilter.value.trim()
  if (page.value > 1)        q.page = String(page.value)
  if (jobId)                 q.job  = String(jobId)
  router.replace({ query: q })
}

const load = () => fetch(buildParams())

const search = () => {
  page.value     = 1
  activeId.value = null
  syncToUrl(null)
  load()
}

const clearFilter = () => {
  idFilter.value = ''
  page.value     = 1
  activeId.value = null
  syncToUrl(null)
  load()
}

const handleRowClick = (job: AdminJob) => {
  if (job.queue !== 'assistant') return
  const next = activeId.value === job.id ? null : job.id
  activeId.value = next
  syncToUrl(next)
}

const goTo = (p: number) => {
  page.value = p
  syncToUrl()
  load()
}

const formatTs = (ts: number) => formatTimestamp(ts)

onMounted(() => {
  idFilter.value = String(route.query.id   ?? '')
  page.value     = Number(route.query.page ?? 1)
  load()
})
</script>
