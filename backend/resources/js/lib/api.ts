import axios from 'axios'
import { createSharedComposableById } from '@/composables/core/createSharedComposableById'
import { useSession } from '@/composables/core/useSession'
import { useEnterpriseStore } from '@/stores/enterpriseStore'

export const useApi = () => createSharedComposableById('api', () => {
  const client = axios.create({
    baseURL: import.meta.env.VITE_API_URL as string,
    headers: { 'Content-Type': 'application/json' },
    withCredentials: true,
  })

  // ── Request: inyectar X-Enterprise-ID ────────────────────────────────────
  client.interceptors.request.use((config) => {
    const enterpriseStore = useEnterpriseStore()

    if (enterpriseStore.activeEnterpriseId) {
      config.headers['X-Enterprise-ID'] = enterpriseStore.activeEnterpriseId
    }

    return config
  })

  // ── Response: refresh automático en 401 ──────────────────────────────────
  let isRefreshing = false
  let pendingQueue: Array<{
    resolve: (value?: unknown) => void
    reject: (err: unknown) => void
  }> = []

  const processQueue = (error: unknown = null) => {
    pendingQueue.forEach(({ resolve, reject }) =>
      error ? reject(error) : resolve(),
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

      if (isRefreshing) {
        return new Promise((resolve, reject) => {
          pendingQueue.push({ resolve, reject })
        }).then(() => client(original))
      }

      original._retry = true
      isRefreshing    = true

      const session = useSession()

      try {
        await axios.post(
          `${import.meta.env.VITE_API_URL as string}/auth/refresh`,
          null,
          { withCredentials: true },
        )
        processQueue()
        return client(original)
      } catch (refreshError) {
        processQueue(refreshError)
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
