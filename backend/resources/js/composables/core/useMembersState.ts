import { ref } from 'vue'
import { useMembersApi } from '@/composables/api/useMembersApi'
import type { Member, Invitation } from '@/composables/api/useMembersApi'

export const useMembersState = () => {
  const { listMembers, listInvitations } = useMembersApi()

  const members     = ref<Member[]>([])
  const invitations = ref<Invitation[]>([])

  const loadData = async () => {
    const [membersRes, invitationsRes] = await Promise.all([
      listMembers(),
      listInvitations(),
    ])
    members.value     = membersRes.data.data
    invitations.value = invitationsRes.data.data
  }

  return { members, invitations, loadData }
}
