import type { AvatarUrl } from '@/types'
import { useApi } from '@/lib/api'

export interface MemberUser {
  id:         string
  name:       string
  email:      string
  avatar_url: AvatarUrl
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

  const listMembers           = ()  => api.get<{ data: Member[] }>('/enterprises/current/members')
  const listSuspendedMembers  = ()  => api.get<{ data: Member[] }>('/enterprises/current/members?status=suspended')
  const removeMember     = (memberId: string)                    => api.delete(`/enterprises/current/members/${memberId}`)
  const assignRole       = (userId: string, roleId: string)      => api.patch(`/enterprises/current/members/${userId}/role`, { role_id: roleId })

  const listInvitations  = ()                                    => api.get<{ data: Invitation[] }>('/enterprises/current/invitations')
  const invite           = (emails: string[], roleId: string)    => api.post<{ data: Invitation[] }>('/enterprises/current/invitations', { emails, role_id: roleId })
  const cancelInvitation = (id: string)                          => api.delete(`/enterprises/current/invitations/${id}`)

  return { listMembers, listSuspendedMembers, removeMember, assignRole, listInvitations, invite, cancelInvitation }
}
