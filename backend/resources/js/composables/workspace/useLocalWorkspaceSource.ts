import type { WorkspaceCapabilities, WorkspaceMember, WorkspaceRole } from '@/types'
import type { WorkspaceDataSource }                                   from './WorkspaceDataSource'
import { useLocalWorkspaceService, LOCAL_CAPABILITIES }               from './local/useLocalWorkspaceService'

const notSupported = (): Promise<never> =>
  Promise.reject(new Error('not_supported_in_local_mode'))

export const useLocalWorkspaceSource = (): WorkspaceDataSource => {
  const svc = useLocalWorkspaceService()

  return {
    getQuota:        ()                         => svc.getQuota(),
    listWorkspaces:  ()                         => svc.listRoot(),
    getDetail:       (id)                       => svc.getDetail(id),
    createWorkspace: (name, parentId)           => svc.createWorkspace(name, parentId),
    getWorkspace:    (id)                       => svc.getWorkspace(id),
    updateWorkspace: (id, name)                 => svc.updateWorkspace(id, name),
    deleteWorkspace: (id)                       => svc.deleteWorkspace(id),
    archiveWorkspace:(id)                       => svc.archiveWorkspace(id),
    getAncestors:    (id)                       => svc.getAncestors(id),
    listChildren:    (id)                       => svc.listChildren(id),
    getCapabilities: ()                         => Promise.resolve<WorkspaceCapabilities>({ permissions: LOCAL_CAPABILITIES }),
    listMembers:     ()                         => Promise.resolve<WorkspaceMember[]>([]),
    listRoles:       ()                         => Promise.resolve<WorkspaceRole[]>([]),
    inviteMember:    ()                         => notSupported(),
    removeMember:    ()                         => notSupported(),
    assignRole:      ()                         => notSupported(),
    createRole:      ()                         => notSupported(),
    updateRole:      ()                         => notSupported(),
    deleteRole:      ()                         => notSupported(),
  }
}
