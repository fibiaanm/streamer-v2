import { ref } from 'vue'
import { useApi } from '@/lib/api'
import type { FailedJobsSummary, FailedJobsTimelinePoint, TimelineGroupBy } from '@/types/admin'

export interface FailedJobsFilters {
  from: string
  to: string
  queue?: string
}

export const useAdminFailedJobs = () => {
  const summary  = ref<FailedJobsSummary | null>(null)
  const timeline = ref<FailedJobsTimelinePoint[]>([])
  const loading  = ref(false)
  const error    = ref<string | null>(null)

  const buildParams = (filters: FailedJobsFilters, extra?: Record<string, string>) => {
    const merged = { ...filters, ...extra } as Record<string, string | undefined>
    const clean  = Object.fromEntries(
      Object.entries(merged).filter(([, v]) => v !== undefined && v !== '' && v !== 'undefined'),
    ) as Record<string, string>
    return new URLSearchParams(clean).toString()
  }

  const fetchSummary = async (filters: FailedJobsFilters) => {
    loading.value = true
    error.value   = null
    try {
      const { data } = await useApi().get(`/admin/failed-jobs/summary?${buildParams(filters)}`)
      summary.value = data.data
    } catch {
      error.value = 'Error loading summary'
    } finally {
      loading.value = false
    }
  }

  const fetchTimeline = async (filters: FailedJobsFilters, groupBy: TimelineGroupBy = 'day') => {
    loading.value = true
    error.value   = null
    try {
      const { data } = await useApi().get(`/admin/failed-jobs/timeline?${buildParams(filters, { group_by: groupBy })}`)
      timeline.value = data.data
    } catch {
      error.value = 'Error loading timeline'
    } finally {
      loading.value = false
    }
  }

  const fetchAll = async (filters: FailedJobsFilters, groupBy: TimelineGroupBy = 'day') => {
    await Promise.all([
      fetchSummary(filters),
      fetchTimeline(filters, groupBy),
    ])
  }

  return {
    summary, timeline, loading, error,
    fetchSummary, fetchTimeline, fetchAll,
  }

}
