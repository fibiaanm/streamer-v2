<template>
  <div>
    <div class="flex flex-wrap gap-3 mb-5">
      <input
        v-model="userId"
        class="w-36 px-3 py-2 rounded-xl border border-white/10 bg-white/5 text-white/80 placeholder-white/25 text-sm focus:outline-none focus:border-white/20"
        type="number"
        placeholder="User ID…"
        @change="reload"
      />
      <input
        v-model="from"
        class="px-3 py-2 rounded-xl border border-white/10 bg-white/5 text-white/80 text-sm focus:outline-none focus:border-white/20"
        type="date"
        @change="reload"
      />
      <input
        v-model="to"
        class="px-3 py-2 rounded-xl border border-white/10 bg-white/5 text-white/80 text-sm focus:outline-none focus:border-white/20"
        type="date"
        @change="reload"
      />
    </div>

    <div class="rounded-2xl border border-white/8 bg-white/3 p-5">
      <table class="w-full text-sm border-collapse">
        <thead>
          <tr>
            <th class="text-left text-xs font-semibold uppercase tracking-widest text-white/30 pb-3 border-b border-white/8 px-2">User</th>
            <th class="text-right text-xs font-semibold uppercase tracking-widest text-white/30 pb-3 border-b border-white/8 px-2">Messages</th>
            <th class="text-right text-xs font-semibold uppercase tracking-widest text-white/30 pb-3 border-b border-white/8 px-2">Tokens</th>
            <th class="text-left text-xs font-semibold uppercase tracking-widest text-white/30 pb-3 border-b border-white/8 px-2">Started</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in conversations" :key="c.id" class="border-b border-white/4 last:border-0">
            <td class="py-3 px-2">
              <p class="font-medium text-white/80">{{ c.user_name }}</p>
              <p class="text-xs text-white/30">{{ c.user_email }}</p>
            </td>
            <td class="py-3 px-2 text-right tabular-nums text-white/60">{{ c.message_count }}</td>
            <td class="py-3 px-2 text-right tabular-nums text-white/60">{{ fmt(c.total_tokens) }}</td>
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

      <div v-if="pagination && pagination.last_page > 1" class="flex items-center justify-center gap-3 pt-5">
        <button
          :disabled="page === 1"
          class="text-xs px-3 py-1.5 rounded-lg border border-white/10 bg-white/5 text-white/50 disabled:opacity-30 hover:text-white/80 transition-colors cursor-pointer"
          @click="goTo(page - 1)"
        >Prev</button>
        <span class="text-xs text-white/30">{{ page }} / {{ pagination.last_page }}</span>
        <button
          :disabled="page === pagination.last_page"
          class="text-xs px-3 py-1.5 rounded-lg border border-white/10 bg-white/5 text-white/50 disabled:opacity-30 hover:text-white/80 transition-colors cursor-pointer"
          @click="goTo(page + 1)"
        >Next</button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAdminConversations } from '@/composables/admin/useAdminConversations'

const { conversations, pagination, loading, fetch } = useAdminConversations()

const userId = ref<number | ''>('')
const from   = ref('')
const to     = ref('')
const page   = ref(1)

const reload = () => { page.value = 1; load() }
const load   = () => fetch({
  user_id: userId.value || undefined,
  from:    from.value   || undefined,
  to:      to.value     || undefined,
  page:    page.value,
})
const goTo = (p: number) => { page.value = p; load() }

const fmt = (n: number) =>
  n >= 1_000_000 ? `${(n / 1_000_000).toFixed(1)}M`
  : n >= 1_000   ? `${(n / 1_000).toFixed(1)}K`
  : String(n)

const formatDate = (d: string) =>
  new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })

onMounted(load)
</script>
