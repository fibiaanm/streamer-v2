import { useSession } from '@/composables/core/useSession'
import { useWorkspacesApi } from '@/composables/api/useWorkspacesApi'
import { useLocalWorkspaceSource } from './useLocalWorkspaceSource'
import type { WorkspaceDataSource } from './WorkspaceDataSource'

export const useWorkspaceSource = (): WorkspaceDataSource => {
  const { user, authenticated } = useSession()

  if (!authenticated.value || !user.value?.enterprise.id) {
    return useLocalWorkspaceSource()
  }

  const api = useWorkspacesApi()

  return {
    getQuota:         async ()                    => (await api.getQuota()).data.data,
    listWorkspaces:   async ()                    => (await api.listWorkspaces()).data.data,
    createWorkspace:  async (name, pid)           => (await api.createWorkspace(name, pid)).data.data,
    getWorkspace:     async (id)                  => (await api.getWorkspace(id)).data.data,
    updateWorkspace:  async (id, name)            => (await api.updateWorkspace(id, name)).data.data,
    deleteWorkspace:  async (id)                  => { await api.deleteWorkspace(id) },
    archiveWorkspace: async (id)                  => (await api.archiveWorkspace(id)).data.data,
    listChildren:     async (id)                  => (await api.listChildren(id)).data.data,
    getCapabilities:  async (id)                  => ({ permissions: (await api.getCapabilities(id)).data.data }),
    listMembers:      async (id)                  => (await api.listMembers(id)).data.data,
    inviteMember:     async (id, email, role_id)  => { await api.inviteMember(id, email, role_id) },
    removeMember:     async (wsId, memberId)      => { await api.removeMember(wsId, memberId) },
    assignRole:       async (wsId, mId, roleId)   => { await api.assignRole(wsId, mId, roleId) },
    listRoles:        async (id)                  => (await api.listRoles(id)).data.data,
    createRole:       async (id, name, perms)     => (await api.createRole(id, name, perms)).data.data,
    deleteRole:       async (wsId, roleId)        => { await api.deleteRole(wsId, roleId) },
  }
}
