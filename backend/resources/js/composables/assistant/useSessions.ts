import { ref } from 'vue'
import { useApi } from '@/lib/api'

export interface AssistantSession {
  id:              string
  title:           string
  is_active:       boolean
  started_at:      string | null
  last_message_at: string | null
}

export const useSessions = () => {
  const sessions    = ref<AssistantSession[]>([])
  const loading     = ref(false)
  const hasMore     = ref(false)
  const nextCursor  = ref<string | null>(null)

  const loadSessions = async (): Promise<void> => {
    loading.value = true
    try {
      const api = useApi()
      const res = await api.get('/assistant/sessions')
      sessions.value  = res.data.data as AssistantSession[]
      nextCursor.value = res.data.meta?.next_cursor ?? null
      hasMore.value    = nextCursor.value !== null
    } finally {
      loading.value = false
    }
  }

  const loadMore = async (): Promise<void> => {
    if (!hasMore.value || loading.value) return
    loading.value = true
    try {
      const api = useApi()
      const res = await api.get('/assistant/sessions', {
        params: { cursor: nextCursor.value },
      })
      sessions.value   = [...sessions.value, ...(res.data.data as AssistantSession[])]
      nextCursor.value = res.data.meta?.next_cursor ?? null
      hasMore.value    = nextCursor.value !== null
    } finally {
      loading.value = false
    }
  }

  return { sessions, loading, hasMore, loadSessions, loadMore }
}
