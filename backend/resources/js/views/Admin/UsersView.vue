<template>
  <div>
    <div class="mb-5">
      <input
        v-model="search"
        class="w-80 px-3 py-2 rounded-xl border border-white/10 bg-white/5 text-white/80 placeholder-white/25 text-sm focus:outline-none focus:border-white/20"
        type="text"
        placeholder="Search by name or email…"
        @input="debouncedFetch"
      />
    </div>

    <div class="rounded-2xl border border-white/8 bg-white/3 p-5">
      <table class="w-full text-sm border-collapse">
        <thead>
          <tr>
            <th class="text-left text-xs font-semibold uppercase tracking-widest text-white/30 pb-3 border-b border-white/8 px-2">User</th>
            <th class="text-right text-xs font-semibold uppercase tracking-widest text-white/30 pb-3 border-b border-white/8 px-2">Total tokens</th>
            <th class="text-left text-xs font-semibold uppercase tracking-widest text-white/30 pb-3 border-b border-white/8 px-2">Joined</th>
            <th class="text-xs font-semibold uppercase tracking-widest text-white/30 pb-3 border-b border-white/8 px-2"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="u in users" :key="u.id" class="border-b border-white/4 last:border-0">
            <td class="py-3 px-2">
              <p class="font-medium text-white/80">{{ u.name }}</p>
              <p class="text-xs text-white/30">{{ u.email }}</p>
            </td>
            <td class="py-3 px-2 text-right tabular-nums text-white/60">{{ fmt(u.total_tokens) }}</td>
            <td class="py-3 px-2 text-xs text-white/30">{{ formatDate(u.created_at) }}</td>
            <td class="py-3 px-2">
              <span v-if="u.is_admin" class="text-xs font-bold px-2 py-0.5 rounded-full bg-brand-500/20 text-brand-300">Admin</span>
            </td>
          </tr>
          <tr v-if="!users.length && !loading">
            <td colspan="4" class="py-10 text-center text-sm text-white/25">No users found</td>
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
import { useAdminUsers } from '@/composables/admin/useAdminUsers'

const { users, pagination, loading, fetch } = useAdminUsers()

const search = ref('')
const page   = ref(1)

let debounceTimer: ReturnType<typeof setTimeout>
const debouncedFetch = () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => { page.value = 1; load() }, 350)
}

const load = () => fetch({ search: search.value || undefined, page: page.value })
const goTo = (p: number) => { page.value = p; load() }

const fmt  = (n: number) =>
  n >= 1_000_000 ? `${(n / 1_000_000).toFixed(1)}M`
  : n >= 1_000   ? `${(n / 1_000).toFixed(1)}K`
  : String(n)

const formatDate = (d: string) =>
  new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })

onMounted(load)
</script>
