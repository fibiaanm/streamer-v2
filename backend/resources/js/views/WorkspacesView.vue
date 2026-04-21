<template>
  <AppLayout>
    <template #header-left>
      <AppMenuSwitcher />
    </template>

    <template #header-right>
      <UserMenu />
    </template>

    <!-- Island -->
    <div class="flex items-start p-6 gap-4" style="height: calc(100vh - 4.5rem)">

      <!-- Sidebar -->
      <div class="w-56 shrink-0 h-full flex flex-col rounded-2xl border border-white/8 bg-white/3 overflow-hidden">
        <div class="px-4 py-3 border-b border-white/6 shrink-0">
          <span class="text-[11px] font-semibold uppercase tracking-widest text-white/35">Workspaces</span>
        </div>
        <div class="flex-1 flex items-center justify-center p-4">
          <p class="text-xs text-white/20 text-center">Sin workspaces</p>
        </div>
      </div>

      <!-- Main -->
      <div class="flex-1 h-full flex flex-col rounded-2xl border border-white/8 bg-white/3 overflow-hidden">
        <div class="flex-1 flex flex-col items-center justify-center gap-4 text-center p-10">
          <div class="w-16 h-16 rounded-2xl bg-white/4 border border-white/6 flex items-center justify-center">
            <svg class="w-8 h-8 text-white/15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
              <rect x="3" y="3" width="8" height="8" rx="2" />
              <rect x="13" y="3" width="8" height="8" rx="2" />
              <rect x="3" y="13" width="8" height="8" rx="2" />
              <rect x="13" y="13" width="8" height="8" rx="2" />
            </svg>
          </div>
          <div class="space-y-1.5 max-w-[220px]">
            <p class="text-sm font-medium text-white/25">Selecciona un workspace</p>
            <p class="text-xs text-white/15 leading-relaxed">
              O crea uno nuevo para empezar
            </p>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import AppLayout       from '@/components/AppLayout.vue'
import AppMenuSwitcher from '@/components/AppMenuSwitcher.vue'
import UserMenu        from '@/components/UserMenu.vue'
</script>
