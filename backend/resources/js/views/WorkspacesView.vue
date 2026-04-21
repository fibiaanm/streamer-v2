<template>
  <AppLayout>
    <template #header-left>
      <AppMenuSwitcher />
    </template>
    <template #header-right>
      <UserMenu />
    </template>

    <div class="flex items-start p-6 gap-4" style="height: calc(100vh - 4.5rem)">

      <!-- Sidebar -->
      <div class="w-56 shrink-0 h-full flex flex-col rounded-2xl border border-white/8 bg-white/3 overflow-hidden">
        <div class="px-4 py-3 border-b border-white/6 shrink-0 flex items-center justify-between">
          <span class="text-[11px] font-semibold uppercase tracking-widest text-white/35">Workspaces</span>
          <button
            class="w-5 h-5 flex items-center justify-center rounded-md text-white/30 hover:text-white/60 hover:bg-white/6 transition-colors cursor-pointer"
            @click="openCreateFolder()"
          >
            <AppIcon name="ui/plus" size="xs" />
          </button>
        </div>

        <div class="flex-1 overflow-y-auto py-1.5">
          <template v-if="sidebarLoading">
            <div v-for="i in 3" :key="i" class="mx-2 my-0.5 h-9 rounded-xl bg-white/4 animate-pulse" />
          </template>
          <WorkspaceSidebarItem
            v-for="ws in workspaces"
            :key="ws.id"
            :workspace="ws"
            :is-active="selected === ws.id"
            @select="selected = ws.id"
          />
        </div>

        <div class="px-3 py-2.5 border-t border-white/6 shrink-0 flex flex-col gap-1.5">
          <div class="flex items-center justify-between">
            <span class="text-[10px] text-white/25">Workspaces</span>
            <span class="text-[10px] text-white/35">
              {{ workspaces.length }} / {{ quota.limit === -1 ? '∞' : quota.limit }}
            </span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-[10px] text-white/25">Almacenamiento</span>
            <span class="text-[10px] text-white/35">{{ storageLabel }}</span>
          </div>
          <div class="mt-0.5 h-0.5 rounded-full bg-white/6 overflow-hidden">
            <div class="h-full rounded-full bg-white/20 transition-all" :style="{ width: storagePct + '%' }" />
          </div>
        </div>
      </div>

      <!-- Main -->
      <div class="flex-1 h-full flex flex-col rounded-2xl border border-white/8 bg-white/3 overflow-hidden min-w-0">

        <template v-if="currentFolder">
          <!-- Workspace header -->
          <div class="px-6 py-4 border-b border-white/6 shrink-0 flex items-center gap-4">
            <WorkspaceBreadcrumb :crumbs="breadcrumbs" @navigate="navigateToCrumb($event)" />
            <button
              class="shrink-0 text-[11px] text-white/30 hover:text-white/60 transition-colors px-2 py-1 rounded-lg hover:bg-white/5 cursor-pointer"
              @click="openSettings(currentFolder!)"
            >
              Configuración
            </button>
          </div>

          <div class="flex-1 overflow-y-auto p-6 flex flex-col gap-6">
            <!-- Folders -->
            <div>
              <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-white/40 uppercase tracking-wide">Folders</span>
                <button
                  class="text-[11px] text-white/30 hover:text-white/60 transition-colors flex items-center gap-1 cursor-pointer"
                  @click="openCreateFolder(currentFolder ?? undefined)"
                >
                  <AppIcon name="ui/plus" size="xs" />
                  Nuevo
                </button>
              </div>

              <template v-if="childrenLoading">
                <div class="grid grid-cols-3 gap-2.5">
                  <div v-for="i in 3" :key="i" class="h-20 rounded-xl bg-white/4 animate-pulse" />
                </div>
              </template>
              <template v-else>
                <div v-if="children.length" class="grid grid-cols-3 gap-2.5">
                  <WorkspaceChildCard
                    v-for="child in children"
                    :key="child.id"
                    :workspace="child"
                    @select="navigateInto(child)"
                    @settings="openSettings(child)"
                  />
                </div>
                <p v-else class="text-xs text-white/20">Sin carpetas</p>
              </template>
            </div>

            <!-- Assets -->
            <div>
              <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-white/40 uppercase tracking-wide">Assets</span>
                <button class="text-[11px] text-white/30 hover:text-white/60 transition-colors flex items-center gap-1 cursor-pointer">
                  <AppIcon name="ui/plus" size="xs" />
                  Subir
                </button>
              </div>
              <p class="text-xs text-white/20">Sin assets</p>
            </div>
          </div>
        </template>

        <!-- Empty state -->
        <template v-else>
          <div class="flex-1 flex flex-col items-center justify-center gap-4 text-center p-10">
            <div class="w-16 h-16 rounded-2xl bg-white/4 border border-white/6 flex items-center justify-center">
              <AppIcon name="ui/grid" size="xl" class="text-white/15" />
            </div>
            <div class="space-y-1.5 max-w-[220px]">
              <p class="text-sm font-medium text-white/25">Selecciona un workspace</p>
              <p class="text-xs text-white/15 leading-relaxed">O crea uno nuevo para empezar</p>
            </div>
          </div>
        </template>

      </div>
    </div>

    <!-- Modals -->
    <WorkspaceCreateFolderModal
      :is-open="showCreateFolder"
      :parent-name="createFolderCtx?.name"
      @close="showCreateFolder = false"
      @created="onFolderCreated"
    />

    <WorkspaceSettingsModal
      :is-open="showSettings"
      :workspace="settingsTarget"
      @close="showSettings = false"
      @renamed="onWorkspaceRenamed"
      @archived="onWorkspaceArchived"
    />
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import AppLayout                  from '@/components/AppLayout.vue'
import AppMenuSwitcher            from '@/components/AppMenuSwitcher.vue'
import AppIcon                    from '@/components/AppIcon.vue'
import UserMenu                   from '@/components/UserMenu.vue'
import WorkspaceSidebarItem       from './Workspaces/WorkspaceSidebarItem.vue'
import WorkspaceChildCard         from './Workspaces/WorkspaceChildCard.vue'
import WorkspaceBreadcrumb        from './Workspaces/WorkspaceBreadcrumb.vue'
import WorkspaceCreateFolderModal from './Workspaces/WorkspaceCreateFolderModal.vue'
import WorkspaceSettingsModal     from './Workspaces/WorkspaceSettingsModal.vue'
import { useWorkspacesApi }       from '@/composables/api/useWorkspacesApi'
import { useToasts }              from '@/composables/core/useToasts'
import type { Workspace, WorkspaceQuota } from '@/types'

const api              = useWorkspacesApi()
const { add: addToast } = useToasts()

// ── Workspaces list (sidebar) ─────────────────────────────────────────────────

const workspaces    = ref<Workspace[]>([])
const quota         = ref<WorkspaceQuota>({ used: 0, limit: -1 })
const sidebarLoading = ref(true)

// Storage is not yet in the API — kept as static until assets stage
const storage = { used: 0, limit: 1 }

const storageLabel = computed(() => {
  const used  = storage.used >= 1 ? `${storage.used.toFixed(1)} GB` : `${Math.round(storage.used * 1024)} MB`
  const limit = storage.limit === -1 ? '∞' : `${storage.limit} GB`
  return `${used} / ${limit}`
})

const storagePct = computed(() =>
  storage.limit === -1 ? 0 : Math.min((storage.used / storage.limit) * 100, 100),
)

onMounted(async () => {
  try {
    const [wsRes, quotaRes] = await Promise.all([
      api.listWorkspaces(),
      api.getQuota(),
    ])
    workspaces.value = wsRes.data.data
    quota.value      = quotaRes.data.data
    if (workspaces.value.length) selected.value = workspaces.value[0].id
  } catch {
    addToast({ type: 'error', title: 'No se pudieron cargar los workspaces', duration: 4000 })
  } finally {
    sidebarLoading.value = false
  }
})

// ── Selection + navigation ────────────────────────────────────────────────────

const selected        = ref<string | null>(null)
const folderStack     = ref<Workspace[]>([])
const children        = ref<Workspace[]>([])
const childrenLoading = ref(false)

const currentFolder = computed(() => folderStack.value[folderStack.value.length - 1] ?? null)

const breadcrumbs = computed(() =>
  currentFolder.value
    ? [{ id: '__root__', name: 'Workspaces' }, ...folderStack.value.map(w => ({ id: w.id, name: w.name }))]
    : [],
)

async function loadChildren(id: string) {
  childrenLoading.value = true
  children.value = []
  try {
    children.value = (await api.listChildren(id)).data.data
  } catch {
    addToast({ type: 'error', title: 'No se pudieron cargar las carpetas', duration: 3000 })
  } finally {
    childrenLoading.value = false
  }
}

watch(selected, async (id) => {
  folderStack.value = []
  if (!id) return
  const ws = workspaces.value.find(w => w.id === id)
  if (!ws) return
  folderStack.value = [ws]
  await loadChildren(id)
})

async function navigateInto(ws: Workspace) {
  folderStack.value = [...folderStack.value, ws]
  await loadChildren(ws.id)
}

async function navigateToCrumb(id: string) {
  if (id === '__root__') {
    selected.value = null
    return
  }
  const idx = folderStack.value.findIndex(w => w.id === id)
  if (idx === -1) return
  folderStack.value = folderStack.value.slice(0, idx + 1)
  await loadChildren(folderStack.value[idx].id)
}

// ── Create folder modal ───────────────────────────────────────────────────────

const showCreateFolder = ref(false)
const createFolderCtx  = ref<{ id: string; name: string } | null>(null)
const creating         = ref(false)

function openCreateFolder(parent?: { id: string; name: string }) {
  createFolderCtx.value  = parent ?? null
  showCreateFolder.value = true
}

async function onFolderCreated(name: string) {
  if (creating.value) return
  creating.value = true
  try {
    const res   = await api.createWorkspace(name, createFolderCtx.value?.id)
    const newWs = res.data.data

    if (createFolderCtx.value) {
      children.value.push(newWs)
    } else {
      workspaces.value.push(newWs)
      quota.value.used++
      selected.value = newWs.id
    }
  } catch (err: any) {
    const code = err?.response?.data?.error?.code
    if (code === 'PlanLimitExceeded') {
      addToast({ type: 'error', title: 'Límite de workspaces alcanzado', duration: 4000 })
    } else if (code === 'WorkspaceDepthExceeded') {
      addToast({ type: 'error', title: 'Profundidad máxima alcanzada', duration: 4000 })
    } else {
      addToast({ type: 'error', title: 'No se pudo crear la carpeta', duration: 4000 })
    }
  } finally {
    creating.value = false
  }
}

// ── Settings modal ────────────────────────────────────────────────────────────

const showSettings   = ref(false)
const settingsTarget = ref<{ id: string; name: string } | null>(null)

function openSettings(ws: { id: string; name: string }) {
  settingsTarget.value = ws
  showSettings.value   = true
}

async function onWorkspaceRenamed(id: string, name: string) {
  try {
    await api.updateWorkspace(id, name)
    const ws = workspaces.value.find(w => w.id === id)
    if (ws) ws.name = name
    const child = children.value.find(c => c.id === id)
    if (child) child.name = name
    const fsItem = folderStack.value.find(w => w.id === id)
    if (fsItem) fsItem.name = name
    if (settingsTarget.value?.id === id) settingsTarget.value = { id, name }
  } catch {
    addToast({ type: 'error', title: 'No se pudo guardar el nombre', duration: 4000 })
  }
}

async function onWorkspaceArchived(id: string) {
  try {
    await api.archiveWorkspace(id)
    workspaces.value = workspaces.value.filter(ws => ws.id !== id)
    children.value   = children.value.filter(c => c.id !== id)
    // If archived folder is in the nav stack, pop back to its parent
    const stackIdx = folderStack.value.findIndex(w => w.id === id)
    if (stackIdx !== -1) {
      folderStack.value = folderStack.value.slice(0, stackIdx)
      if (folderStack.value.length === 0) {
        selected.value = workspaces.value[0]?.id ?? null
      } else {
        await loadChildren(folderStack.value[folderStack.value.length - 1].id)
      }
    } else if (selected.value === id) {
      selected.value = workspaces.value[0]?.id ?? null
    }
    showSettings.value = false
    quota.value.used = Math.max(0, quota.value.used - 1)
  } catch {
    addToast({ type: 'error', title: 'No se pudo archivar el workspace', duration: 4000 })
  }
}
</script>
