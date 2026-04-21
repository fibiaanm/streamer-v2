import type { Workspace, WorkspaceCapabilities, WorkspaceMember, WorkspaceQuota, WorkspaceRole } from '@/types'
import { useApi } from '@/lib/api'

export const useWorkspacesApi = () => {
  const api = useApi()

  const getQuota         = ()                                                          => api.get<{ data: WorkspaceQuota }>('/workspaces/root-quota')
  const listWorkspaces   = ()                                                          => api.get<{ data: Workspace[] }>('/workspaces')
  const createWorkspace  = (name: string, parent_workspace_id?: string)                => api.post<{ data: Workspace }>('/workspaces', { name, parent_workspace_id })
  const getWorkspace     = (id: string)                                                => api.get<{ data: Workspace }>(`/workspaces/${id}`)
  const updateWorkspace  = (id: string, name: string)                                  => api.patch<{ data: Workspace }>(`/workspaces/${id}`, { name })
  const deleteWorkspace  = (id: string)                                                => api.delete(`/workspaces/${id}`)
  const archiveWorkspace = (id: string)                                                => api.patch<{ data: Workspace }>(`/workspaces/${id}/archive`)
  const listChildren     = (id: string)                                                => api.get<{ data: Workspace[] }>(`/workspaces/${id}/children`)
  const getCapabilities  = (id: string)                                                => api.get<{ data: string[] }>(`/workspaces/${id}/capabilities`)
  const listMembers      = (id: string)                                                => api.get<{ data: WorkspaceMember[] }>(`/workspaces/${id}/members`)
  const inviteMember     = (id: string, email: string, role_id: string)                => api.post(`/workspaces/${id}/invitations`, { email, role_id })
  const removeMember     = (wsId: string, memberId: string)                            => api.delete(`/workspaces/${wsId}/members/${memberId}`)
  const assignRole       = (wsId: string, memberId: string, roleId: string)            => api.patch(`/workspaces/${wsId}/members/${memberId}/role`, { role_id: roleId })
  const listRoles        = (id: string)                                                => api.get<{ data: WorkspaceRole[] }>(`/workspaces/${id}/roles`)
  const createRole       = (id: string, name: string, permissions: string[])           => api.post<{ data: WorkspaceRole }>(`/workspaces/${id}/roles`, { name, permissions })
  const deleteRole       = (wsId: string, roleId: string)                              => api.delete(`/workspaces/${wsId}/roles/${roleId}`)

  return {
    getQuota,
    listWorkspaces,
    createWorkspace,
    getWorkspace,
    updateWorkspace,
    deleteWorkspace,
    archiveWorkspace,
    listChildren,
    getCapabilities,
    listMembers,
    inviteMember,
    removeMember,
    assignRole,
    listRoles,
    createRole,
    deleteRole,
  }
}
