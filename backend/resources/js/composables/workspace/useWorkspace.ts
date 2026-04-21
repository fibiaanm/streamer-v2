import { ref, watch, onScopeDispose } from 'vue'
import { createSharedComposableById } from '@/composables/core/createSharedComposableById'
import { useSocket } from '@/composables/core/useSocket'
import { useWorkspaceSource } from './useWorkspaceSource'
import type { Workspace, WorkspaceMember, WorkspaceRole, WsMemberAddedPayload, WsMemberRemovedPayload, WsMemberRoleChangedPayload } from '@/types'

export const useWorkspace = (id: string) =>
  createSharedComposableById(`workspace.${id}`, () => {
    const source              = useWorkspaceSource()
    const { emit, on, connected } = useSocket()

    const workspace    = ref<Workspace | null>(null)
    const capabilities = ref<string[]>([])
    const members      = ref<WorkspaceMember[]>([])
    const roles        = ref<WorkspaceRole[]>([])
    const children     = ref<Workspace[]>([])
    const loading      = ref(false)

    const loadData = async () => {
      loading.value = true
      const [ws, caps, mems, rols, kids] = await Promise.all([
        source.getWorkspace(id),
        source.getCapabilities(id),
        source.listMembers(id),
        source.listRoles(id),
        source.listChildren(id),
      ])
      workspace.value    = ws
      capabilities.value = caps.permissions
      members.value      = mems
      roles.value        = rols
      children.value     = kids
      loading.value      = false
    }

    const can = (permission: string) => capabilities.value.includes(permission)

    // ── Socket room ─────────────────────────────────────────────────────────

    let listenersRegistered = false

    function onMemberAdded(data: unknown) {
      const { member } = data as WsMemberAddedPayload
      if (!members.value.find(m => m.id === member.id)) {
        members.value.push(member)
      }
    }

    function onMemberRemoved(data: unknown) {
      const { member_id } = data as WsMemberRemovedPayload
      members.value = members.value.filter(m => m.id !== member_id)
    }

    function onMemberRoleChanged(data: unknown) {
      const { member_id, role } = data as WsMemberRoleChangedPayload
      const m = members.value.find(x => x.id === member_id)
      if (m) m.role = role
    }

    watch(connected, (isConnected) => {
      if (!isConnected) return
      emit('join_workspace', { workspaceId: id })

      if (!listenersRegistered) {
        on('workspace.member.added',        onMemberAdded)
        on('workspace.member.removed',      onMemberRemoved)
        on('workspace.member.role_changed', onMemberRoleChanged)
        listenersRegistered = true
      }
    }, { immediate: true })

    onScopeDispose(() => {
      emit('leave_workspace', { workspaceId: id })
    })

    return { workspace, capabilities, members, roles, children, loading, loadData, can }
  })
