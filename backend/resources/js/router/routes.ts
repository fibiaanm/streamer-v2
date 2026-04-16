import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/app',
    component: () => import('@/views/WorkspacesView.vue'),
  },
  {
    path: '/app/settings',
    component: () => import('@/views/SettingsView.vue'),
  },
  // Workspaces, rooms y streams se añaden en sus etapas respectivas
  {
    path: '/design-test',
    component: () => import('@/views/DesignTest.vue'),
  },
]

export { routes }
