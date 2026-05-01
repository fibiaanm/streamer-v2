import { ref } from 'vue'
import { useApi } from '@/lib/api'
import type { AdminConversation, AdminConversationDetail, AdminPagination } from '@/types/admin'

export const useAdminConversations = () => {
  const conversations = ref<AdminConversation[]>([])
  const detail        = ref<AdminConversationDetail | null>(null)
  const pagination    = ref<AdminPagination | null>(null)
  const loading       = ref(false)
  const loadingDetail = ref(false)
  const error         = ref<string | null>(null)

  const fetch = async (params: { user_id?: number; email?: string; from?: string; to?: string; page?: number } = {}) => {
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

  const fetchDetail = async (id: number) => {
    loadingDetail.value = true
    detail.value        = null
    try {
      const { data } = await useApi().get(`/admin/conversations/${id}`)
      detail.value = data.data
    } catch {
      error.value = 'Error loading conversation'
    } finally {
      loadingDetail.value = false
    }
  }

  return { conversations, detail, pagination, loading, loadingDetail, error, fetch, fetchDetail }
}
