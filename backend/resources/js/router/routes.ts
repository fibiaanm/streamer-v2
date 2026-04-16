import type { RouteRecordRaw } from 'vue-router'

export const routes: RouteRecordRaw[] = [
  {
    path: '/app',
    component: () => import('@/views/WorkspacesView.vue'),
  },
  {
    path: '/app/settings',
    component: () => import('@/views/SettingsView.vue'),
  },
  // Workspaces, rooms y streams se añaden en sus etapas respectivas
]
