import { ref, computed } from 'vue'
import { useApi } from '@/lib/api'
import type {
  UsageSummary, UsageTimelinePoint, UsageBreakdownItem,
  UsageTopUser, UsageFilters, BreakdownGroupBy, TimelineGroupBy,
} from '@/types/admin'

export const useAdminUsage = () => {
  const summary   = ref<UsageSummary | null>(null)
  const timeline  = ref<UsageTimelinePoint[]>([])
  const breakdown = ref<UsageBreakdownItem[]>([])
  const topUsers  = ref<UsageTopUser[]>([])
  const loading   = ref(false)
  const error     = ref<string | null>(null)

  const totalTokens = computed(() =>
    timeline.value.reduce((s, p) => s + p.input_tokens + p.output_tokens, 0),
  )

  const buildParams = (filters: UsageFilters, extra?: Record<string, string>) => {
    const merged = { ...filters, ...extra } as Record<string, string | undefined>
    const clean  = Object.fromEntries(
      Object.entries(merged).filter(([, v]) => v !== undefined && v !== '' && v !== 'undefined'),
    ) as Record<string, string>
    return new URLSearchParams(clean).toString()
  }

  const fetchSummary = async (filters: UsageFilters) => {
    loading.value = true
    error.value   = null
    try {
      const { data } = await useApi().get(`/admin/usage/summary?${buildParams(filters)}`)
      summary.value = data.data
    } catch {
      error.value = 'Error loading summary'
    } finally {
      loading.value = false
    }
  }

  const fetchTimeline = async (filters: UsageFilters, groupBy: TimelineGroupBy = 'day') => {
    loading.value = true
    error.value   = null
    try {
      const { data } = await useApi().get(`/admin/usage/timeline?${buildParams(filters, { group_by: groupBy })}`)
      timeline.value = data.data
    } catch {
      error.value = 'Error loading timeline'
    } finally {
      loading.value = false
    }
  }

  const fetchBreakdown = async (filters: UsageFilters, groupBy: BreakdownGroupBy = 'model') => {
    loading.value = true
    error.value   = null
    try {
      const { data } = await useApi().get(`/admin/usage/breakdown?${buildParams(filters, { group_by: groupBy })}`)
      breakdown.value = data.data
    } catch {
      error.value = 'Error loading breakdown'
    } finally {
      loading.value = false
    }
  }

  const fetchTopUsers = async (filters: UsageFilters) => {
    loading.value = true
    error.value   = null
    try {
      const { data } = await useApi().get(`/admin/usage/top-users?${buildParams(filters)}`)
      topUsers.value = data.data
    } catch {
      error.value = 'Error loading top users'
    } finally {
      loading.value = false
    }
  }

  const fetchAll = async (filters: UsageFilters, opts?: { groupBy?: BreakdownGroupBy; timelineGroupBy?: TimelineGroupBy }) => {
    await Promise.all([
      fetchSummary(filters),
      fetchTimeline(filters, opts?.timelineGroupBy),
      fetchBreakdown(filters, opts?.groupBy),
      fetchTopUsers(filters),
    ])
  }

  return {
    summary, timeline, breakdown, topUsers,
    loading, error, totalTokens,
    fetchSummary, fetchTimeline, fetchBreakdown, fetchTopUsers, fetchAll,
  }
}
