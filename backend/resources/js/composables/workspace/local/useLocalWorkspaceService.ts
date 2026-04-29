import { usePermissions }              from '@/composables/core/usePermissions'
import { useLocalWorkspaceRepository } from './useLocalWorkspaceRepository'
import type { LocalWorkspace }         from './LocalWorkspace'
import type { Workspace, WorkspaceDetail, WorkspaceQuota } from '@/types'

const LOCAL_OWNER = { id: 'local', name: 'Tú' }
const LOCAL_ROLE  = { id: 'local', name: 'Owner' }

export const LOCAL_CAPABILITIES = [
  'workspace.view',
  'workspace.edit',
  'workspace.delete',
  'workspace.create_child',
  'workspace.members.view',
  'workspace.members.add',
  'workspace.members.delete',
  'workspace.members.change_role',
  'workspace.roles.view',
  'workspace.roles.add',
  'workspace.roles.edit',
  'workspace.roles.delete',
  'asset.upload',
  'asset.rename',
  'asset.move',
  'asset.delete',
  'room.create',
  'room.manage',
]

function localError(code: string): Error {
  return Object.assign(new Error(code), { code })
}

function toWorkspace(w: LocalWorkspace): Workspace {
  return {
    id:         w.id,
    name:       w.name,
    status:     w.status,
    path:       w.path,
    owner:      LOCAL_OWNER,
    parent_id:  w.parent_id,
    created_at: w.created_at,
  }
}

export const useLocalWorkspaceService = () => {
  const repo           = useLocalWorkspaceRepository()
  const { coreLimits } = usePermissions()

  const maxWorkspaces = () => coreLimits.value?.workspaces?.max     ?? -1
  const maxDepth      = () => coreLimits.value?.workspace_depth?.max ?? -1

  // ── Quota ────────────────────────────────────────────────────────────────────

  async function getQuota(): Promise<WorkspaceQuota> {
    const all  = await repo.findAll()
    const used = all.filter(w => w.parent_id === null && w.status === 'active').length
    return { used, limit: maxWorkspaces() }
  }

  // ── Read ─────────────────────────────────────────────────────────────────────

  async function getWorkspace(id: string): Promise<Workspace> {
    const w = await repo.find(id)
    if (!w) throw localError('WorkspaceNotFound')
    return toWorkspace(w)
  }

  async function listRoot(): Promise<Workspace[]> {
    const roots = await repo.findByParentId(null)
    return roots.filter(w => w.status === 'active').map(toWorkspace)
  }

  async function listChildren(id: string): Promise<Workspace[]> {
    const children = await repo.findByParentId(id)
    return children.filter(w => w.status === 'active').map(toWorkspace)
  }

  async function getAncestors(id: string): Promise<Workspace[]> {
    const w = await repo.find(id)
    if (!w) return []

    const segments = w.path.split('.')
    if (segments.length <= 1) return []

    const ancestorIds = segments.slice(0, -1)
    const ancestors: Workspace[] = []

    for (const seg of ancestorIds) {
      const ancestor = await repo.find(seg)
      if (ancestor) ancestors.push(toWorkspace(ancestor))
    }

    return ancestors
  }

  async function getDetail(id: string): Promise<WorkspaceDetail> {
    const w = await repo.find(id)
    if (!w) throw localError('WorkspaceNotFound')

    const [ancestors, children] = await Promise.all([getAncestors(id), listChildren(id)])

    return { workspace: toWorkspace(w), ancestors, children, mode: 'my', role: LOCAL_ROLE, capabilities: LOCAL_CAPABILITIES }
  }

  // ── Write ────────────────────────────────────────────────────────────────────

  async function createWorkspace(name: string, parentId?: string): Promise<Workspace> {
    if (!parentId) {
      const { used, limit } = await getQuota()
      if (limit !== -1 && used >= limit) throw localError('PlanLimitExceeded')
    }

    if (parentId) {
      const parent = await repo.find(parentId)
      if (!parent) throw localError('WorkspaceNotFound')

      const depth = parent.path.split('.').length
      const max   = maxDepth()
      if (max !== -1 && depth >= max) throw localError('WorkspaceDepthExceeded')
    }

    const id   = crypto.randomUUID()
    const base = parentId ? (await repo.find(parentId))!.path : null
    const path = base ? `${base}.${id}` : id

    const ws: LocalWorkspace = {
      id,
      name,
      status:     'active',
      path,
      parent_id:  parentId ?? null,
      created_at: new Date().toISOString(),
    }

    await repo.save(ws)
    return toWorkspace(ws)
  }

  async function updateWorkspace(id: string, name: string): Promise<Workspace> {
    const w = await repo.find(id)
    if (!w) throw localError('WorkspaceNotFound')

    const updated = { ...w, name }
    await repo.save(updated)
    return toWorkspace(updated)
  }

  async function archiveWorkspace(id: string): Promise<Workspace> {
    const w = await repo.find(id)
    if (!w) throw localError('WorkspaceNotFound')

    const updated = { ...w, status: 'archived' as const }
    await repo.save(updated)
    return toWorkspace(updated)
  }

  async function deleteWorkspace(id: string): Promise<void> {
    await repo.remove(id)
  }

  return {
    getQuota,
    listRoot,
    listChildren,
    getWorkspace,
    getAncestors,
    getDetail,
    createWorkspace,
    updateWorkspace,
    archiveWorkspace,
    deleteWorkspace,
  }
}
