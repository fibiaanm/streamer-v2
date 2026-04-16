import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/login',
    component: () => import('@/Pages/Auth/Login.vue'),
  },
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
    component: () => import('@/Pages/DesignTest.vue'),
  },
]

export { routes }
