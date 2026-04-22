import type { Workspace, WorkspaceCapabilities, WorkspaceMember, WorkspaceQuota, WorkspaceRole } from '@/types'

export interface WorkspaceDataSource {
  getQuota        (): Promise<WorkspaceQuota>
  listWorkspaces  (): Promise<Workspace[]>
  createWorkspace (name: string, parent_workspace_id?: string): Promise<Workspace>
  getWorkspace    (id: string): Promise<Workspace>
  updateWorkspace (id: string, name: string): Promise<Workspace>
  deleteWorkspace (id: string): Promise<void>
  archiveWorkspace(id: string): Promise<Workspace>
  getAncestors    (id: string): Promise<Workspace[]>
  listChildren    (id: string): Promise<Workspace[]>
  getCapabilities (id: string): Promise<WorkspaceCapabilities>
  listMembers     (id: string): Promise<WorkspaceMember[]>
  inviteMember    (id: string, email: string, role_id: string): Promise<void>
  removeMember    (wsId: string, memberId: string): Promise<void>
  assignRole      (wsId: string, memberId: string, roleId: string): Promise<void>
  listRoles       (id: string): Promise<WorkspaceRole[]>
  createRole      (id: string, name: string, permissions: string[]): Promise<WorkspaceRole>
  deleteRole      (wsId: string, roleId: string): Promise<void>
}
