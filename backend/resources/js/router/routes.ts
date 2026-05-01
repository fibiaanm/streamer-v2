import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/login',
    component: () => import('@/Pages/Auth/Login.vue'),
  },
  {
    path: '/accept-invitation',
    component: () => import('@/Pages/Auth/AcceptInvitation.vue'),
  },
  {
    path: '/switch',
    component: () => import('@/Pages/Auth/Switch.vue'),
  },
  // also check config/auth.php (guest_paths) when updating auth requirements
  {
    path: '/app',
    component: () => import('@/views/AppView.vue'),
  },
  {
    path: '/app/workspaces',
    component: () => import('@/views/WorkspacesView.vue'),
    meta: { appName: 'Workspaces', appIcon: 'ui/building', appMenu: true, appProduct: 'core' },
  },
  {
    path: '/app/settings',
    component: () => import('@/views/SettingsView.vue'),
    meta: { appName: 'Configuración', appIcon: 'ui/settings', requiresAuth: true },
  },
  {
    path: '/app/assistant',
    component: () => import('@/views/AssistantView.vue'),
    meta: { appName: 'Asistente', appIcon: 'ui/message-circle', appMenu: true, appProduct: 'assistant' },
  },
  {
    path: '/app/image-studio',
    component: () => import('@/Pages/ImageStudio.vue'),
    meta: { appName: 'Image Studio', appIcon: 'ui/image', appMenu: true, appProduct: 'core' },
  },
  {
    path: '/app/workspaces/:id',
    component: () => import('@/views/WorkspaceView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/app/workspaces/:id/settings',
    component: () => import('@/views/WorkspaceSettingsView.vue'),
    meta: { requiresAuth: true },
  },
  // ── Admin ──────────────────────────────────────────────────────────────────
  {
    path: '/admin',
    component: () => import('@/views/AdminView.vue'),
    meta: { requiresAdmin: true },
    children: [
      { path: '',          redirect: '/admin/usage' },
      { path: 'usage',         component: () => import('@/views/Admin/UsageDashboard.vue') },
      { path: 'users',         component: () => import('@/views/Admin/UsersView.vue') },
      { path: 'conversations', component: () => import('@/views/Admin/ConversationsView.vue') },
    ],
  },

  // rooms y streams se añaden en sus etapas respectivas
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
