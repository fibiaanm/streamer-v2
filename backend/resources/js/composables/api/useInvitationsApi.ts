import { useApi } from '@/lib/api'

export interface InvitationInfo {
  email:           string
  enterprise_name: string
  role_name:       string
  user_exists:     boolean
}

export interface AcceptInvitationPayload {
  password: string
  name?:    string
}

export const useInvitationsApi = () => {
  const api = useApi()

  const getInvitation = (token: string) =>
    api.get<{ data: InvitationInfo }>(`/invitations/${token}`)

  const acceptInvitation = (token: string, payload: AcceptInvitationPayload) =>
    api.post<{ data: { access_token: string; refresh_token: string; expires_in: number; enterprise_id: string } }>(
      `/invitations/${token}/accept`,
      payload,
    )

  return { getInvitation, acceptInvitation }
}
