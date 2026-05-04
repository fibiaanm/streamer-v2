import { ref } from 'vue'
import { useApi } from '@/lib/api'
import type { AssistantEvent } from '@/types'

const events  = ref<AssistantEvent[]>([])
const loading = ref(false)

export const useEvents = () => {
    async function loadEvents(params: { from?: string; to?: string; status?: string } = {}) {
        loading.value = true
        try {
            const api = useApi()
            const res = await api.get('/assistant/events', { params })
            events.value = res.data.data as AssistantEvent[]
        } finally {
            loading.value = false
        }
    }

    async function cancelEvent(id: string, series = false): Promise<AssistantEvent> {
        const api = useApi()
        const res = await api.post(`/assistant/events/${id}/cancel`, { series })
        return res.data.data as AssistantEvent
    }

    async function snoozeEvent(id: string, until: string): Promise<AssistantEvent> {
        const api = useApi()
        const res = await api.post(`/assistant/events/${id}/snooze`, { until })
        return res.data.data as AssistantEvent
    }

    async function updateEvent(
        id: string,
        payload: Partial<Pick<AssistantEvent, 'content' | 'event_at' | 'event_end' | 'type'>>,
    ): Promise<AssistantEvent> {
        const api = useApi()
        const res = await api.patch(`/assistant/events/${id}`, payload)
        return res.data.data as AssistantEvent
    }

    function replaceEvent(updated: AssistantEvent) {
        const idx = events.value.findIndex((e) => e.id === updated.id)
        if (idx !== -1) events.value[idx] = updated
    }

    function removeEvent(id: string) {
        events.value = events.value.filter((e) => e.id !== id)
    }

    return { events, loading, loadEvents, cancelEvent, snoozeEvent, updateEvent, replaceEvent, removeEvent }
}
