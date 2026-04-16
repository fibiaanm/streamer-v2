import axios from 'axios'
import { createSharedComposableById } from '@/composables/core/createSharedComposableById'
import { useSession } from '@/composables/core/useSession'
import { useEnterpriseStore } from '@/stores/enterpriseStore'

export const useApi = () => createSharedComposableById('api', () => {
  const client = axios.create({
    baseURL: import.meta.env.VITE_API_URL as string,
    headers: { 'Content-Type': 'application/json' },
  })

  // ── Request: inyectar Authorization + X-Enterprise-ID ────────────────────
  client.interceptors.request.use((config) => {
    const { accessToken }  = useSession()
    const enterpriseStore  = useEnterpriseStore()

    if (accessToken.value) {
      config.headers['Authorization'] = `Bearer ${accessToken.value}`
    }
    if (enterpriseStore.activeEnterpriseId) {
      config.headers['X-Enterprise-ID'] = enterpriseStore.activeEnterpriseId
    }

    return config
  })

  // ── Response: refresh automático en 401 ──────────────────────────────────
  let isRefreshing = false
  let pendingQueue: Array<{
    resolve: (token: string) => void
    reject: (err: unknown) => void
  }> = []

  const processQueue = (newToken: string | null, error: unknown = null) => {
    pendingQueue.forEach(({ resolve, reject }) =>
      error ? reject(error) : resolve(newToken!),
    )
    pendingQueue = []
  }

  client.interceptors.response.use(
    (response) => response,
    async (error) => {
      const original = error.config as typeof error.config & { _retry?: boolean }

      if (error.response?.status !== 401 || original._retry) {
        return Promise.reject(error)
      }

      const session = useSession()

      if (!session.refreshToken.value) {
        session.clear()
        window.location.href = '/login'
        return Promise.reject(error)
      }

      if (isRefreshing) {
        return new Promise((resolve, reject) => {
          pendingQueue.push({ resolve, reject })
        }).then((token) => {
          original.headers['Authorization'] = `Bearer ${token}`
          return client(original)
        })
      }

      original._retry = true
      isRefreshing    = true

      try {
        const res = await axios.post(
          `${import.meta.env.VITE_API_URL as string}/auth/refresh`,
          { refresh_token: session.refreshToken.value },
        )
        const { access_token, refresh_token } = res.data.data as {
          access_token: string
          refresh_token: string
        }
        session.setTokens(access_token, refresh_token)
        processQueue(access_token)
        original.headers['Authorization'] = `Bearer ${access_token}`
        return client(original)
      } catch (refreshError) {
        processQueue(null, refreshError)
        session.clear()
        window.location.href = '/login'
        return Promise.reject(refreshError)
      } finally {
        isRefreshing = false
      }
    },
  )

  return client
})
