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
      <button
        type="submit"
        class="px-4 py-2 rounded-xl border border-white/10 bg-white/5 text-white/60 text-sm hover:text-white/90 hover:bg-white/8 transition-colors cursor-pointer"
      >Search</button>
    </form>

    <div v-if="searched" class="rounded-2xl border border-white/8 bg-white/3 p-5">
      <table class="w-full text-sm border-collapse">
        <thead>
          <tr>
            <th class="text-left text-xs font-semibold uppercase tracking-widest text-white/30 pb-3 border-b border-white/8 px-2">Title</th>
            <th class="text-right text-xs font-semibold uppercase tracking-widest text-white/30 pb-3 border-b border-white/8 px-2">Messages</th>
            <th class="text-right text-xs font-semibold uppercase tracking-widest text-white/30 pb-3 border-b border-white/8 px-2">Cost (tokens)</th>
            <th class="text-left text-xs font-semibold uppercase tracking-widest text-white/30 pb-3 border-b border-white/8 px-2">Started</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="c in conversations" :key="c.id"
            class="border-b border-white/4 last:border-0 cursor-pointer transition-colors"
            :class="selectedId === c.id ? 'bg-white/5' : 'hover:bg-white/3'"
            @click="selectConversation(c.id)"
          >
            <td class="py-3 px-2 text-white/80">{{ c.title ?? `Conversation #${c.id}` }}</td>
            <td class="py-3 px-2 text-right tabular-nums text-white/60">{{ c.message_count }}</td>
            <td class="py-3 px-2 text-right tabular-nums text-white/60">{{ fmt(c.cost.total) }}</td>
            <td class="py-3 px-2 text-xs text-white/30">{{ formatDate(c.created_at) }}</td>
          </tr>
          <tr v-if="!conversations.length && !loading">
            <td colspan="4" class="py-10 text-center text-sm text-white/25">No conversations found</td>
          </tr>
          <tr v-if="loading">
            <td colspan="4" class="py-10 text-center text-sm text-white/25">Loading…</td>
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
import { useAdminConversations } from '@/composables/admin/useAdminConversations'

const { conversations, pagination, loading, fetch } = useAdminConversations()

const route  = useRoute()
const router = useRouter()

const query    = ref('')
const from     = ref('')
const to       = ref('')
const page     = ref(1)
const searched = ref(false)

const selectedId = ref<number | null>(Number(route.query.conv) || null)

const buildParams = () => {
  const q   = query.value.trim()
  const isId = /^\d+$/.test(q)
  return {
    ...(q ? (isId ? { user_id: Number(q) } : { email: q }) : {}),
    from: from.value || undefined,
    to:   to.value   || undefined,
    page: page.value,
  }
}

const syncToUrl = (convId: number | null = selectedId.value) => {
  const q: Record<string, string> = {}
  if (query.value.trim()) q.q    = query.value.trim()
  if (from.value)          q.from = from.value
  if (to.value)            q.to   = to.value
  if (page.value > 1)      q.page = String(page.value)
  if (convId)              q.conv = String(convId)
  router.replace({ query: q })
}

const search = () => {
  const params    = buildParams()
  const hasFilter = params.user_id || params.email || params.from || params.to
  if (!hasFilter) return

  searched.value   = true
  selectedId.value = null
  page.value       = 1
  syncToUrl(null)
  fetch({ ...params, page: 1 })
}

const selectConversation = (id: number) => {
  selectedId.value = selectedId.value === id ? null : id
  syncToUrl(selectedId.value)
}

const goTo = (p: number) => {
  page.value = p
  syncToUrl()
  fetch(buildParams())
}

const fmt = (n: number) =>
  n >= 1_000_000 ? `${(n / 1_000_000).toFixed(1)}M`
  : n >= 1_000   ? `${(n / 1_000).toFixed(1)}K`
  : String(n)

const formatDate = (d: string) =>
  new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })

onMounted(() => {
  query.value = String(route.query.q    ?? '')
  from.value  = String(route.query.from ?? '')
  to.value    = String(route.query.to   ?? '')
  page.value  = Number(route.query.page ?? 1)

  const params    = buildParams()
  const hasFilter = params.user_id || params.email || params.from || params.to
  if (hasFilter) {
    searched.value = true
    fetch(params)
  }
})
</script>
