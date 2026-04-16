import { ref, computed } from 'vue'
import { createSharedComposableById } from './createSharedComposableById'
import type { SessionUser } from '@/types'

const ACCESS_TOKEN_KEY  = 'access_token'
const REFRESH_TOKEN_KEY = 'refresh_token'
const USER_KEY          = 'auth_user'

export const useSession = () => createSharedComposableById('session', () => {
  const accessToken  = ref<string | null>(localStorage.getItem(ACCESS_TOKEN_KEY))
  const refreshToken = ref<string | null>(localStorage.getItem(REFRESH_TOKEN_KEY))
  const user         = ref<SessionUser | null>(
    JSON.parse(localStorage.getItem(USER_KEY) ?? 'null'),
  )

  const authenticated = computed(() => !!accessToken.value)

  const setTokens = (access: string, refresh: string, userData?: SessionUser) => {
    accessToken.value  = access
    refreshToken.value = refresh
    localStorage.setItem(ACCESS_TOKEN_KEY,  access)
    localStorage.setItem(REFRESH_TOKEN_KEY, refresh)
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
    accessToken.value  = null
    refreshToken.value = null
    user.value         = null
    localStorage.removeItem(ACCESS_TOKEN_KEY)
    localStorage.removeItem(REFRESH_TOKEN_KEY)
    localStorage.removeItem(USER_KEY)
  }

  return { accessToken, refreshToken, user, authenticated, setTokens, setUser, clear }
})
