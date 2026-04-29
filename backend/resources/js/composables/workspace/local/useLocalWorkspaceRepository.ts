import { idbGet, idbPut, idbDelete, idbGetAll } from '@/lib/idb'
import type { LocalWorkspace } from './LocalWorkspace'

const STORE = 'workspaces'

export const useLocalWorkspaceRepository = () => ({
  find    : (id: string)               => idbGet<LocalWorkspace>(STORE, id),
  findAll : ()                         => idbGetAll<LocalWorkspace>(STORE),
  findByParentId: async (pid: string | null) => {
    const all = await idbGetAll<LocalWorkspace>(STORE)
    return all.filter(w => w.parent_id === pid)
  },
  save    : (ws: LocalWorkspace)       => idbPut<LocalWorkspace>(STORE, ws),
  remove  : (id: string)               => idbDelete(STORE, id),
})
