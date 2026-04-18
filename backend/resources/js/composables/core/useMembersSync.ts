import type { MemberRemovedPayload, MemberRoleChangedPayload, InvitationCreatedPayload, InvitationCancelledPayload } from '@/types'
import type { Invitation } from '@/composables/api/useMembersApi'
import { useScopedSocketEvents } from './useScopedSocketEvents'
import type { useMembersState } from './useMembersState'

export const useMembersSync = (state: ReturnType<typeof useMembersState>) => {
  useScopedSocketEvents({
    'member.removed': (data) => {
      const { id } = data as MemberRemovedPayload
      state.members.value = state.members.value.filter(m => m.id !== id)
    },
    'member.role_changed': (data) => {
      const { memberId, role } = data as MemberRoleChangedPayload
      const m = state.members.value.find(x => x.id === memberId)
      if (m) m.role = role
    },
    'invitation.created': (data) => {
      const payload = data as InvitationCreatedPayload
      if (!state.invitations.value.find(i => i.id === payload.id)) {
        state.invitations.value.push(payload as unknown as Invitation)
      }
    },
    'invitation.cancelled': (data) => {
      const { id } = data as InvitationCancelledPayload
      state.invitations.value = state.invitations.value.filter(i => i.id !== id)
    },
  })
}
