import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/login',
    component: () => import('@/Pages/Auth/Login.vue'),
  },
  {
    path: '/switch',
    component: () => import('@/Pages/Auth/Switch.vue'),
  },
  // also check config/auth.php (guest_paths) when updating auth requirements
  {
    path: '/app',
    component: () => import('@/views/WorkspacesView.vue'),
  },
  {
    path: '/app/settings',
    component: () => import('@/views/SettingsView.vue'),
    meta: { requiresAuth: true },
  },
  // Workspaces, rooms y streams se añaden en sus etapas respectivas
  {
    path: '/design-test',
    component: () => import('@/Pages/DesignTest.vue'),
  },
  {
    path: '/image-studio',
    component: () => import('@/Pages/ImageStudio.vue'),
  },
]

export { routes }
