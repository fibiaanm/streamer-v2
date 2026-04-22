import { computed } from 'vue'
import type { Workspace, WorkspaceCapabilities, WorkspaceMember, WorkspaceQuota, WorkspaceRole } from '@/types'
import { usePermissions } from '@/composables/core/usePermissions'
import type { WorkspaceDataSource } from './WorkspaceDataSource'

const LOCAL_PERSONAL_PERMISSIONS = [
  'workspace.view',
  'workspace.edit',
  'workspace.delete',
  'workspace.create_child',
  'asset.upload',
  'asset.rename',
  'asset.move',
  'asset.delete',
  'room.create',
  'room.manage',
]

const notSupported = (): Promise<never> =>
  Promise.reject(new Error('not_supported_in_local_mode'))

export const useLocalWorkspaceSource = (): WorkspaceDataSource => {
  const { coreLimits } = usePermissions()

  const maxWorkspaces = computed(() => coreLimits.value?.workspaces?.max ?? -1)

  return {
  getQuota: () => Promise.resolve<WorkspaceQuota>({ used: 0, limit: maxWorkspaces.value }),
  listWorkspaces:   () => Promise.resolve<Workspace[]>([]),
  createWorkspace:  () => notSupported(),
  getWorkspace:     () => notSupported(),
  updateWorkspace:  () => notSupported(),
  deleteWorkspace:  () => notSupported(),
  archiveWorkspace: () => notSupported(),
  getAncestors:     () => Promise.resolve<Workspace[]>([]),
  listChildren:     () => Promise.resolve<Workspace[]>([]),
  getCapabilities:  () => Promise.resolve<WorkspaceCapabilities>({ permissions: LOCAL_PERSONAL_PERMISSIONS }),
  listMembers:      () => Promise.resolve<WorkspaceMember[]>([]),
  inviteMember:     () => notSupported(),
  removeMember:     () => notSupported(),
  assignRole:       () => notSupported(),
  listRoles:        () => Promise.resolve<WorkspaceRole[]>([]),
  createRole:       () => notSupported(),
  deleteRole:       () => notSupported(),
  }
}
