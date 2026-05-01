<template>
  <AppLayout>
    <template #header-left>
      <span class="text-xs font-bold tracking-widest uppercase text-brand-400 px-1">Admin</span>
    </template>

    <template #header-right>
      <div class="flex items-center gap-3">
        <button
          class="flex items-center gap-1.5 text-xs text-white/40 hover:text-white/70 transition-colors cursor-pointer disabled:pointer-events-none"
          :disabled="spinning"
          @click="reload"
        >
          <AppIcon name="ui/rotate-cw" size="sm" :class="{ 'animate-spin': spinning }" />
          Reload
        </button>

        <a href="/app" class="flex items-center gap-2 text-sm text-white/40 hover:text-white/70 transition-colors">
          <AppIcon name="ui/arrow-left" size="sm" />
          App
        </a>
      </div>
    </template>

    <template #sidebar>
      <div class="flex flex-col h-full">
        <div class="px-4 py-4 border-b border-white/8">
          <p class="text-xs font-bold tracking-widest uppercase text-white/30">Navigation</p>
        </div>
        <nav class="flex-1 flex flex-col gap-0.5 p-2 pt-3">
          <AdminSidebarItem to="/admin/usage"         icon="ui/bar-chart-2"    label="Token Usage" />
          <AdminSidebarItem to="/admin/users"         icon="ui/users"          label="Users" />
          <AdminSidebarItem to="/admin/conversations" icon="ui/message-square" label="Conversations" />
        </nav>
      </div>
    </template>

    <div class="h-full overflow-y-auto pretty-scroll px-6 pb-6 pt-[92px]">
      <RouterView :key="reloadKey" />
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import AppLayout from '@/components/AppLayout.vue'
import AppIcon   from '@/components/AppIcon.vue'
import AdminSidebarItem from '@/components/Admin/AdminSidebarItem.vue'

const reloadKey = ref(0)
const spinning  = ref(false)

const reload = () => {
  spinning.value = true
  reloadKey.value++
  setTimeout(() => { spinning.value = false }, 600)
}
</script>
