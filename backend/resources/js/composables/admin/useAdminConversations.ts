import { ref } from 'vue'
import { useApi } from '@/lib/api'
import type { AdminConversation, AdminPagination } from '@/types/admin'

export const useAdminConversations = () => {
  const conversations = ref<AdminConversation[]>([])
  const pagination    = ref<AdminPagination | null>(null)
  const loading       = ref(false)
  const error         = ref<string | null>(null)

  const fetch = async (params: { user_id?: number; from?: string; to?: string; page?: number; per_page?: number } = {}) => {
    loading.value = true
    error.value   = null
    try {
      const qs = new URLSearchParams(
        Object.fromEntries(Object.entries(params).filter(([, v]) => v != null).map(([k, v]) => [k, String(v)])),
      ).toString()
      const { data } = await useApi().get(`/admin/conversations?${qs}`)
      conversations.value = data.data
      pagination.value    = data.meta?.pagination ?? null
    } catch {
      error.value = 'Error loading conversations'
    } finally {
      loading.value = false
    }
  }

  return { conversations, pagination, loading, error, fetch }
}
