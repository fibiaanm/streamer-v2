<template>
  <div :class="{ dark: isDark }">
    <div class="h-screen w-screen overflow-hidden bg-canvas dark:bg-canvas-dark flex">

      <!-- Sidebar -->
      <aside class="w-52 shrink-0 flex flex-col border-r border-white/8 bg-white/4 backdrop-blur-xl">
        <div class="px-4 py-5 border-b border-white/8">
          <span class="text-xs font-bold tracking-widest uppercase text-brand-400">Admin</span>
        </div>

        <nav class="flex-1 flex flex-col gap-0.5 p-2 pt-3">
          <AdminSidebarItem to="/admin/usage"         icon="ui/bar-chart-2"    label="Token Usage" />
          <AdminSidebarItem to="/admin/users"         icon="ui/users"          label="Users" />
          <AdminSidebarItem to="/admin/conversations" icon="ui/message-square" label="Conversations" />
        </nav>

        <div class="p-2 border-t border-white/8">
          <a
            href="/app"
            class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm text-white/40 hover:text-white/70 hover:bg-white/4 transition-colors"
          >
            <AppIcon name="ui/arrow-left" size="sm" />
            Back to app
          </a>
        </div>
      </aside>

      <!-- Main -->
      <div class="flex-1 flex flex-col overflow-hidden">
        <header class="h-14 shrink-0 flex items-center justify-between px-7 border-b border-white/8">
          <h1 class="text-sm font-semibold text-white/80">{{ title }}</h1>
          <span class="text-xs text-white/35">{{ user?.email }}</span>
        </header>

        <main class="flex-1 overflow-y-auto p-7">
          <slot />
        </main>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useTheme } from '@/composables/core/useTheme'
import { useSession } from '@/composables/core/useSession'
import AdminSidebarItem from './AdminSidebarItem.vue'
import AppIcon from '@/components/AppIcon.vue'

const { isDark } = useTheme()
const { user }   = useSession()
const route      = useRoute()

const titles: Record<string, string> = {
  '/admin/usage':         'Token Usage',
  '/admin/users':         'Users',
  '/admin/conversations': 'Conversations',
}

const title = computed(() => titles[route.path] ?? 'Admin')
</script>
