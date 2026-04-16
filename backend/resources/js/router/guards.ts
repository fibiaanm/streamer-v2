import type { NavigationGuardWithThis } from 'vue-router'
import { useSession } from '@/composables/core/useSession'

export const authGuard: NavigationGuardWithThis<undefined> = (to) => {
  const { authenticated } = useSession()

  const requiresAuth = to.path.startsWith('/app')

  if (requiresAuth && !authenticated.value) {
    return { path: '/login' }
  }

  if (!requiresAuth && authenticated.value && (to.path === '/login' || to.path === '/register')) {
    return { path: '/app' }
  }
}
