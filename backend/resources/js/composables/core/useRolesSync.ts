import type { RoleSocketPayload, RoleDeletedPayload } from '@/types'
import type { Role } from '@/composables/api/useRolesApi'
import { useScopedSocketEvents } from './useScopedSocketEvents'
import type { useRolesState } from './useRolesState'

export const useRolesSync = (state: ReturnType<typeof useRolesState>) => {
  useScopedSocketEvents({
    'role.created': (data) => {
      const payload = data as RoleSocketPayload
      if (!state.roles.value.find(r => r.id === payload.id)) {
        state.roles.value.push(payload as Role)
      }
    },
    'role.updated': (data) => {
      const payload = data as RoleSocketPayload
      const idx = state.roles.value.findIndex(r => r.id === payload.id)
      if (idx !== -1) state.roles.value[idx] = payload as Role
    },
    'role.deleted': (data) => {
      const { id } = data as RoleDeletedPayload
      state.roles.value = state.roles.value.filter(r => r.id !== id)
    },
  })
}
