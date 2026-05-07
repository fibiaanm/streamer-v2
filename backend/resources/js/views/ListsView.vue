<template>
  <AppLayout>
    <template #sidebar>
      <AssistantSidebar @select-session="goToChat" @new-session="goToChat" />
    </template>

    <template #header-left>
      <button
        @click="router.push('/app/assistant')"
        class="w-8 h-8 flex items-center justify-center rounded-xl text-white/40
               hover:text-white/80 hover:bg-white/8 transition-colors shrink-0"
      >
        <AppIcon name="ui/arrow-left" size="sm" />
      </button>
      <span class="text-sm font-semibold text-white/70">Listas</span>
    </template>

    <template #header-right>
      <UserMenu back-url="/app" />
    </template>

    <template #sidebar-right>
      <ListDetail
        v-if="activeList"
        :list="activeList"
      />
      <div v-else class="flex flex-col items-center justify-center h-full gap-2 px-6 text-center">
        <AppIcon name="ui/check" size="lg" class="text-white/15" />
        <p class="text-xs text-white/30">Selecciona una lista para ver sus ítems</p>
      </div>
    </template>

    <div class="h-full overflow-y-auto pretty-scroll pt-[84px] pb-8">

      <!-- Status tabs -->
      <div class="px-6 pt-4 pb-2 flex items-center gap-1">
        <button
          v-for="tab in tabs"
          :key="tab.value"
          @click="setStatus(tab.value)"
          class="px-3 py-1.5 rounded-xl text-sm transition-colors"
          :class="statusFilter === tab.value
            ? 'bg-white/10 text-white/90 font-medium'
            : 'text-white/40 hover:text-white/70 hover:bg-white/6'"
        >
          {{ tab.label }}
          <span class="ml-1.5 text-[10px] tabular-nums" :class="statusFilter === tab.value ? 'text-white/50' : 'text-white/25'">
            {{ tabCount(tab.value) }}
          </span>
        </button>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex flex-col gap-3 px-6 pt-4">
        <div v-for="i in 4" :key="i" class="h-20 rounded-2xl bg-white/5 animate-pulse" />
      </div>

      <template v-else>
        <!-- Empty state -->
        <div v-if="filteredLists.length === 0" class="px-6 pt-8 text-center">
          <p class="text-sm text-white/25">
            {{ statusFilter === 'active' ? 'Sin listas activas' : 'Sin listas completadas' }}
          </p>
        </div>

        <!-- Cards -->
        <div v-else class="flex flex-col gap-2 px-6 pt-2">
          <ListCard
            v-for="list in filteredLists"
            :key="list.id"
            :list="list"
            :selected="selectedListId === list.id"
            @select="handleSelectList"
          />
        </div>
      </template>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppLayout        from '@/components/AppLayout.vue'
import AppIcon          from '@/components/AppIcon.vue'
import UserMenu         from '@/components/UserMenu.vue'
import AssistantSidebar from '@/components/assistant/AssistantSidebar.vue'
import ListCard         from '@/components/assistant/ListCard.vue'
import ListDetail       from '@/components/assistant/ListDetail.vue'
import { useLists }     from '@/composables/assistant/useLists'

const router = useRouter()
const route  = useRoute()
const { lists, activeList, loading, loadLists, loadList } = useLists()

// ── Query params ──────────────────────────────────────────────────────────────

const statusFilter = computed(() =>
    (route.query.status as string) === 'completed' ? 'completed' : 'active',
)

const selectedListId = computed(() => route.query.list as string | undefined)

function setStatus(value: string) {
    const entries = Object.entries(route.query).filter(([k]) => k !== 'status')
    if (value !== 'active') entries.push(['status', value])
    router.replace({ query: Object.fromEntries(entries) })
}

function handleSelectList(id: string) {
    const isSame = selectedListId.value === id
    const entries = Object.entries(route.query).filter(([k]) => k !== 'list')
    if (!isSame) {
        entries.push(['list', id])
        loadList(id)
    }
    router.replace({ query: Object.fromEntries(entries) })
}

// ── Filtering & sorting ───────────────────────────────────────────────────────

const tabs = [
    { value: 'active',    label: 'Activas' },
    { value: 'completed', label: 'Completadas' },
]

const sortedLists = computed(() =>
    [...lists.value].sort((a, b) => b.created_at.localeCompare(a.created_at)),
)

function isActive(l: { items_count: { pending: number; done: number } }) {
    return l.items_count.pending > 0 || (l.items_count.pending === 0 && l.items_count.done === 0)
}

const filteredLists = computed(() =>
    sortedLists.value.filter((l) =>
        statusFilter.value === 'completed' ? !isActive(l) : isActive(l),
    ),
)

function tabCount(value: string) {
    return sortedLists.value.filter((l) =>
        value === 'completed' ? !isActive(l) : isActive(l),
    ).length
}

// ── Init ──────────────────────────────────────────────────────────────────────

onMounted(async () => {
    await loadLists()
    if (selectedListId.value) loadList(selectedListId.value)
})

watch(selectedListId, (id) => {
    if (!id) activeList.value = null
})

function goToChat(sessionId: string) {
    router.push({ path: '/app/assistant', query: { session: sessionId } })
}
</script>
