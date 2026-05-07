import { ref } from 'vue'
import { useApi } from '@/lib/api'
import type { AdminJob, AdminJobDetail, AdminPagination } from '@/types/admin'

export const useAdminJobs = () => {
  const jobs          = ref<AdminJob[]>([])
  const detail        = ref<AdminJobDetail | null>(null)
  const pagination    = ref<AdminPagination | null>(null)
  const loading       = ref(false)
  const loadingDetail = ref(false)
  const error         = ref<string | null>(null)

  const fetch = async (params: { id?: number; page?: number } = {}) => {
    loading.value = true
    error.value   = null
    try {
      const qs = new URLSearchParams(
        Object.fromEntries(Object.entries(params).filter(([, v]) => v != null).map(([k, v]) => [k, String(v)])),
      ).toString()
      const { data } = await useApi().get(`/admin/jobs?${qs}`)
      jobs.value       = data.data
      pagination.value = data.meta?.pagination ?? null
    } catch {
      error.value = 'Error loading jobs'
    } finally {
      loading.value = false
    }
  }

  const fetchDetail = async (id: number) => {
    loadingDetail.value = true
    detail.value        = null
    try {
      const { data } = await useApi().get(`/admin/jobs/${id}`)
      detail.value = data.data
    } catch {
      error.value = 'Error loading job detail'
    } finally {
      loadingDetail.value = false
    }
  }

  return { jobs, detail, pagination, loading, loadingDetail, error, fetch, fetchDetail }
}
