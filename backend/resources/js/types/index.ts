export type LimitType      = 'permanent' | 'concurrent' | 'monthly'
export type EnterpriseType = 'personal' | 'enterprise'

export interface LimitValue {
  type: LimitType
  max: number
}

export interface PlanLimits {
  members: LimitValue
  workspaces: LimitValue
  workspace_depth: LimitValue
  storage_gb: LimitValue
  streams_concurrent: LimitValue
  stream_minutes: LimitValue
  rooms_concurrent: LimitValue
  room_participants: LimitValue
  room_guests: LimitValue
}

export interface Plan {
  name: string
  limits: PlanLimits
}

export interface Enterprise {
  id: string
  name: string
  type: EnterpriseType
  role: string
  permissions: string[]
  plan: Plan
}

export interface AvatarUrl {
  jpeg: string
  webp: string
}

export interface SessionUser {
  id: string
  name: string
  email: string
  avatar_url: AvatarUrl
  enterprise: Enterprise
}

export interface EnterpriseUpdatedPayload {
  id: string
  name: string
}

export interface MemberRemovedPayload {
  id: string
}

export interface MemberKickedPayload {
  enterpriseId: string
  enterpriseName: string
}

export interface InvitationCreatedPayload {
  id: string
  email: string
  status: 'pending'
  expires_at: string
  role: { id: string; name: string }
  invited_by: { id: string; name: string }
}

export interface InvitationCancelledPayload {
  id: string
}

export interface MemberRoleChangedPayload {
  memberId: string
  role:     { id: string; name: string }
}

export interface MemberAddedPayload {
  member: {
    id:     string
    status: 'active' | 'suspended'
    user:   { id: string; name: string; email: string; avatar_url: AvatarUrl }
    role:   { id: string; name: string }
  }
  invitation_id: string
}

export interface MemberRoleAssignedPayload {
  role: { id: string; name: string; permissions: string[] }
}

export interface RoleSocketPayload {
  id:          string
  name:        string
  is_global:   boolean
  permissions: string[]
}

export interface RoleDeletedPayload {
  id: string
}

export interface ApiError {
  code: string
  context?: Record<string, unknown>
}

export interface ApiResponse<T> {
  data: T
}
