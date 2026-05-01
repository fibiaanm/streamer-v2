import { ref } from 'vue'
import { useApi } from '@/lib/api'
import type { AdminUser, AdminUserDetail, AdminPagination } from '@/types/admin'

export const useAdminUsers = () => {
  const users      = ref<AdminUser[]>([])
  const detail     = ref<AdminUserDetail | null>(null)
  const pagination = ref<AdminPagination | null>(null)
  const loading    = ref(false)
  const error      = ref<string | null>(null)

  const fetch = async (params: { search?: string; from?: string; to?: string; page?: number; per_page?: number } = {}) => {
    loading.value = true
    error.value   = null
    try {
      const qs = new URLSearchParams(
        Object.fromEntries(Object.entries(params).filter(([, v]) => v != null).map(([k, v]) => [k, String(v)])),
      ).toString()
      const { data } = await useApi().get(`/admin/users?${qs}`)
      users.value      = data.data
      pagination.value = data.meta?.pagination ?? null
    } catch {
      error.value = 'Error loading users'
    } finally {
      loading.value = false
    }
  }

  const fetchDetail = async (id: number, params: { from?: string; to?: string } = {}) => {
    loading.value = true
    error.value   = null
    try {
      const qs = new URLSearchParams(
        Object.fromEntries(Object.entries(params).filter(([, v]) => v != null).map(([k, v]) => [k, String(v)])),
      ).toString()
      const { data } = await useApi().get(`/admin/users/${id}?${qs}`)
      detail.value = data.data
    } catch {
      error.value = 'Error loading user detail'
    } finally {
      loading.value = false
    }
  }

  return { users, detail, pagination, loading, error, fetch, fetchDetail }
}
