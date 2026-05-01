<template>
  <div class="rounded-2xl border border-white/8 bg-white/3 p-5">
    <p class="text-sm font-semibold text-white/80 mb-4">Top users by token consumption</p>
    <table class="w-full text-sm border-collapse">
      <thead>
        <tr>
          <th class="text-left text-xs font-semibold uppercase tracking-widest text-white/30 pb-3 border-b border-white/8 px-2">User</th>
          <th class="text-right text-xs font-semibold uppercase tracking-widest text-white/30 pb-3 border-b border-white/8 px-2">Input</th>
          <th class="text-right text-xs font-semibold uppercase tracking-widest text-white/30 pb-3 border-b border-white/8 px-2">Output</th>
          <th class="text-right text-xs font-semibold uppercase tracking-widest text-white/30 pb-3 border-b border-white/8 px-2">Total</th>
          <th class="text-right text-xs font-semibold uppercase tracking-widest text-white/30 pb-3 border-b border-white/8 px-2">Convs</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="u in users" :key="u.user_id" class="border-b border-white/4 last:border-0">
          <td class="py-3 px-2">
            <p class="font-medium text-white/80">{{ u.name }}</p>
            <p class="text-xs text-white/30">{{ u.email }}</p>
          </td>
          <td class="py-3 px-2 text-right tabular-nums text-white/50">{{ fmt(u.input_tokens) }}</td>
          <td class="py-3 px-2 text-right tabular-nums text-white/50">{{ fmt(u.output_tokens) }}</td>
          <td class="py-3 px-2 text-right tabular-nums font-semibold text-white/80">{{ fmt(u.total_tokens) }}</td>
          <td class="py-3 px-2 text-right tabular-nums text-white/50">{{ u.conversations }}</td>
        </tr>
        <tr v-if="!users.length">
          <td colspan="5" class="py-10 text-center text-sm text-white/25">No data for this period</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
import type { UsageTopUser } from '@/types/admin'

defineProps<{ users: UsageTopUser[] }>()

const fmt = (n: number) =>
  n >= 1_000_000 ? `${(n / 1_000_000).toFixed(1)}M`
  : n >= 1_000   ? `${(n / 1_000).toFixed(1)}K`
  : String(n)
</script>
