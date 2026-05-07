import { ref } from 'vue'
import { useApi } from '@/lib/api'
import type { AssistantList, ListItem } from '@/types'

const lists      = ref<AssistantList[]>([])
const activeList = ref<AssistantList | null>(null)
const loading    = ref(false)
const loadingDetail = ref(false)

export const useLists = () => {
    async function loadLists() {
        loading.value = true
        try {
            const api = useApi()
            const res = await api.get('/assistant/lists')
            lists.value = res.data.data as AssistantList[]
        } finally {
            loading.value = false
        }
    }

    async function loadList(id: string) {
        loadingDetail.value = true
        try {
            const api = useApi()
            const res = await api.get(`/assistant/lists/${id}`)
            activeList.value = res.data.data as AssistantList
        } finally {
            loadingDetail.value = false
        }
    }

    async function addItem(listId: string, content: string): Promise<ListItem[]> {
        const api = useApi()
        const res = await api.post(`/assistant/lists/${listId}/items`, {
            items: [{ content }],
        })
        const created = res.data.data as ListItem[]
        if (activeList.value?.id === listId) {
            activeList.value.items = [...(activeList.value.items ?? []), ...created]
        }
        syncCount(listId, 'pending', 1)
        return created
    }

    async function toggleItem(listId: string, itemId: string, done: boolean) {
        const api = useApi()
        await api.patch(`/assistant/lists/${listId}/items/${itemId}`, {
            status: done ? 'done' : 'pending',
        })
        if (activeList.value?.id === listId && activeList.value.items) {
            const item = activeList.value.items.find((i) => i.id === itemId)
            if (item) {
                const prev = item.status
                item.status = done ? 'done' : 'pending'
                if (prev !== item.status) {
                    syncCount(listId, 'pending', done ? -1 : 1)
                    syncCount(listId, 'done',    done ?  1 : -1)
                }
            }
        }
    }

    async function removeItem(listId: string, itemId: string) {
        const api = useApi()
        await api.delete(`/assistant/lists/${listId}/items/${itemId}`)
        if (activeList.value?.id === listId && activeList.value.items) {
            const item = activeList.value.items.find((i) => i.id === itemId)
            const wasPending = item?.status === 'pending'
            activeList.value.items = activeList.value.items.filter((i) => i.id !== itemId)
            syncCount(listId, wasPending ? 'pending' : 'done', -1)
        }
    }

    async function clearCompleted(listId: string) {
        const api = useApi()
        await api.delete(`/assistant/lists/${listId}/items/completed`)
        if (activeList.value?.id === listId && activeList.value.items) {
            const doneCount = activeList.value.items.filter((i) => i.status === 'done').length
            activeList.value.items = activeList.value.items.filter((i) => i.status !== 'done')
            syncCount(listId, 'done', -doneCount)
        }
    }

    async function deleteList(listId: string) {
        const api = useApi()
        await api.delete(`/assistant/lists/${listId}`)
        lists.value = lists.value.filter((l) => l.id !== listId)
        if (activeList.value?.id === listId) activeList.value = null
    }

    function syncCount(listId: string, key: 'pending' | 'done', delta: number) {
        const list = lists.value.find((l) => l.id === listId)
        if (list) list.items_count[key] = Math.max(0, list.items_count[key] + delta)
        if (activeList.value?.id === listId) {
            activeList.value.items_count[key] = Math.max(0, activeList.value.items_count[key] + delta)
        }
    }

    return {
        lists,
        activeList,
        loading,
        loadingDetail,
        loadLists,
        loadList,
        addItem,
        toggleItem,
        removeItem,
        clearCompleted,
        deleteList,
    }
}
