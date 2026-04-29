import { watch, onUnmounted } from 'vue'
import type { Ref } from 'vue'
import { useSocket } from '@/composables/core/useSocket'
import { useWorkspaceSource } from './useWorkspaceSource'
import type {
  WorkspaceMemberRole,
  WsMemberRoleChangedPayload,
  WsRolePermissionsUpdatedPayload,
} from '@/types'

interface WorkspaceSyncOptions {
  workspaceId:           Ref<string | null>
  roleId:                Ref<string | null>
  currentUserId:         string | null
  onCapabilitiesChanged: (capabilities: string[], role: WorkspaceMemberRole) => void
  onChildCreated:        (ws: { id: string; name: string }) => void
  onChildDeleted:        (wsId: string) => void
  onMemberAdded:         (payload: { memberId: string; user: { id: string; name: string }; role: WorkspaceMemberRole }) => void
  onMemberRemoved:       (memberId: string) => void
}

export function useWorkspaceSync(opts: WorkspaceSyncOptions) {
  const { emit, on, off } = useSocket()
  const source = useWorkspaceSource()

  let currentWsId:   string | null = null
  let currentRoleId: string | null = null

  // ── Handlers ────────────────────────────────────────────────────────────────

  const onMemberRoleChanged = async (raw: unknown) => {
    const data = raw as WsMemberRoleChangedPayload
    if (data.userId !== opts.currentUserId) return

    const wsId = opts.workspaceId.value
    if (!wsId) return

    try {
      const detail = await source.getDetail(wsId)
      // Swap role room before calling the callback
      if (currentRoleId) off('role.permissions_updated', onRolePermissionsUpdated)
      currentRoleId = detail.role.id
      joinRooms(wsId, detail.role.id)
      on('role.permissions_updated', onRolePermissionsUpdated)

      opts.onCapabilitiesChanged(detail.capabilities, detail.role)
    } catch { /* non-critical, capabilities stay stale until next navigation */ }
  }

  const onRolePermissionsUpdated = (raw: unknown) => {
    const data = raw as WsRolePermissionsUpdatedPayload
    const role = { id: data.roleId, name: '' } as WorkspaceMemberRole
    opts.onCapabilitiesChanged(data.permissions, role)
  }

  const onChildCreated = (raw: unknown) => {
    opts.onChildCreated(raw as { id: string; name: string })
  }

  const onChildDeleted = (raw: unknown) => {
    opts.onChildDeleted((raw as { workspaceId: string }).workspaceId)
  }

  const onMemberAdded = (raw: unknown) => {
    opts.onMemberAdded(raw as Parameters<typeof opts.onMemberAdded>[0])
  }

  const onMemberRemoved = (raw: unknown) => {
    opts.onMemberRemoved((raw as { memberId: string }).memberId)
  }

  // ── Room management ──────────────────────────────────────────────────────────

  function joinRooms(wsId: string, roleId: string) {
    emit('join_workspace', { workspaceId: wsId, roleId })
  }

  function leaveRooms(wsId: string, roleId: string) {
    emit('leave_workspace', { workspaceId: wsId, roleId })
  }

  function registerHandlers() {
    on('member.role_changed',    onMemberRoleChanged)
    on('role.permissions_updated', onRolePermissionsUpdated)
    on('child.created',          onChildCreated)
    on('child.deleted',          onChildDeleted)
    on('member.added',           onMemberAdded)
    on('member.removed',         onMemberRemoved)
  }

  function unregisterHandlers() {
    off('member.role_changed',    onMemberRoleChanged)
    off('role.permissions_updated', onRolePermissionsUpdated)
    off('child.created',          onChildCreated)
    off('child.deleted',          onChildDeleted)
    off('member.added',           onMemberAdded)
    off('member.removed',         onMemberRemoved)
  }

  // ── Watch workspace/role changes ─────────────────────────────────────────────

  watch(
    [opts.workspaceId, opts.roleId],
    ([newWsId, newRoleId], [oldWsId, oldRoleId]) => {
      if (oldWsId && oldRoleId) {
        leaveRooms(oldWsId, oldRoleId)
        unregisterHandlers()
      }

      currentWsId   = newWsId
      currentRoleId = newRoleId

      if (newWsId && newRoleId) {
        joinRooms(newWsId, newRoleId)
        registerHandlers()
      }
    },
    { immediate: false },
  )

  onUnmounted(() => {
    if (currentWsId && currentRoleId) {
      leaveRooms(currentWsId, currentRoleId)
      unregisterHandlers()
    }
  })
}
