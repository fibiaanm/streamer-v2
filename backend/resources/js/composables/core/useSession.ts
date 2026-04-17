import { ref, computed } from 'vue'
import { createSharedComposableById } from './createSharedComposableById'
import type { SessionUser } from '@/types'

const USER_KEY = 'auth_user'

export const useSession = () => createSharedComposableById('session', () => {
  const user = ref<SessionUser | null>(
    JSON.parse(localStorage.getItem(USER_KEY) ?? 'null'),
  )

  const authenticated = computed(() => !!user.value)

  const setTokens = (_access: string, _refresh: string, userData?: SessionUser) => {
    if (userData) {
      user.value = userData
      localStorage.setItem(USER_KEY, JSON.stringify(userData))
    }
  }

  const setUser = (userData: SessionUser) => {
    user.value = userData
    localStorage.setItem(USER_KEY, JSON.stringify(userData))
  }

  const clear = () => {
    user.value = null
    localStorage.removeItem(USER_KEY)
  }

  return { user, authenticated, setTokens, setUser, clear }
})
