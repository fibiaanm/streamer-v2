import { useApi } from '@/lib/api'

export interface MemberUser {
  id:    string
  name:  string
  email: string
}

export interface MemberRole {
  id:   string
  name: string
}

export interface Member {
  id:     string
  status: 'active' | 'suspended'
  user:   MemberUser
  role:   MemberRole
}

export interface Invitation {
  id:          string
  email:       string
  status:      'pending' | 'accepted' | 'expired' | 'revoked'
  expires_at:  string
  role:        MemberRole
  invited_by:  MemberUser
}

export const useMembersApi = () => {
  const api = useApi()

  const listMembers      = ()                                    => api.get<Member[]>('/enterprises/current/members')
  const removeMember     = (userId: string)                      => api.delete(`/enterprises/current/members/${userId}`)
  const assignRole       = (userId: string, roleId: string)      => api.patch(`/enterprises/current/members/${userId}/role`, { role_id: roleId })

  const listInvitations  = ()                                    => api.get<Invitation[]>('/enterprises/current/invitations')
  const invite           = (email: string, roleId: string)       => api.post('/enterprises/current/invitations', { email, role_id: roleId })
  const cancelInvitation = (id: string)                          => api.delete(`/enterprises/current/invitations/${id}`)

  return { listMembers, removeMember, assignRole, listInvitations, invite, cancelInvitation }
}
