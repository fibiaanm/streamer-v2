import { useApi } from '@/lib/api'

export interface Role {
  id:          string
  name:        string
  is_global:   boolean
  permissions: string[]
}

export const useRolesApi = () => {
  const api = useApi()

  const listRoles       = ()                                           => api.get<Role[]>('/enterprises/current/roles')
  const listPermissions = ()                                           => api.get<string[]>('/enterprises/current/permissions')
  const createRole      = (name: string, permissions: string[])        => api.post<Role>('/enterprises/current/roles', { name, permissions })
  const updateRole      = (id: string, data: Partial<{ name: string; permissions: string[] }>) =>
    api.patch<Role>(`/enterprises/current/roles/${id}`, data)
  const deleteRole      = (id: string)                                 => api.delete(`/enterprises/current/roles/${id}`)

  return { listRoles, listPermissions, createRole, updateRole, deleteRole }
}
