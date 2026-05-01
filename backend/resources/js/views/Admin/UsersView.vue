<template>
  <div>
    <div class="mb-5">
      <form class="flex gap-2" @submit.prevent="search">
        <input
          v-model="query"
          class="w-80 px-3 py-2 rounded-xl border border-white/10 bg-white/5 text-white/80 placeholder-white/25 text-sm focus:outline-none focus:border-white/20"
          type="text"
          placeholder="User ID or email…"
        />
        <button
          type="submit"
          class="px-4 py-2 rounded-xl border border-white/10 bg-white/5 text-white/60 text-sm hover:text-white/90 hover:bg-white/8 transition-colors cursor-pointer"
        >Search</button>
      </form>
    </div>

    <div v-if="searched" class="rounded-2xl border border-white/8 bg-white/3 p-5">
      <table class="w-full text-sm border-collapse">
        <thead>
          <tr>
            <th class="text-left text-xs font-semibold uppercase tracking-widest text-white/30 pb-3 border-b border-white/8 px-2">User</th>
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
            <td class="py-3 px-2 text-xs text-white/30">{{ formatDate(u.created_at) }}</td>
            <td class="py-3 px-2">
              <span v-if="u.is_admin" class="text-xs font-bold px-2 py-0.5 rounded-full bg-brand-500/20 text-brand-300">Admin</span>
            </td>
          </tr>
          <tr v-if="!users.length && !loading">
            <td colspan="3" class="py-10 text-center text-sm text-white/25">No users found</td>
          </tr>
          <tr v-if="loading">
            <td colspan="3" class="py-10 text-center text-sm text-white/25">Loading…</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useAdminUsers } from '@/composables/admin/useAdminUsers'

const { users, loading, fetch } = useAdminUsers()

const query   = ref('')
const searched = ref(false)

const search = () => {
  const q = query.value.trim()
  if (!q) return

  searched.value = true

  const isId = /^\d+$/.test(q)
  fetch(isId ? { id: Number(q) } : { email: q })
}

const formatDate = (d: string) =>
  new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
</script>
