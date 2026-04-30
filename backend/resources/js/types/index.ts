export type LimitType      = 'permanent' | 'concurrent' | 'monthly' | 'daily'
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

export interface BooleanLimit {
  type:  'boolean'
  value: boolean
}

export interface AssistantLimits {
  assistant_enabled:   BooleanLimit
  messages_daily:      LimitValue
  memory_total:        LimitValue
  memory_categories:   LimitValue
  friends:             LimitValue
  friend_categories:   LimitValue
  lists:               LimitValue
  list_items_max:      LimitValue
  events_monthly:      LimitValue
  reminders_active:    LimitValue
  context_messages:    LimitValue
  storage_mb:          LimitValue
  upload_monthly_mb:   LimitValue
  upload_max_mb:       LimitValue
  expenses_enabled:    BooleanLimit
  custom_instructions: BooleanLimit
  export_enabled:      BooleanLimit
}

export interface ProductInfo {
  plan:   string
  limits: PlanLimits | AssistantLimits
}

export interface Enterprise {
  id:          string | null
  name:        string | null
  type:        EnterpriseType
  role:        string
  permissions: string[]
  products:    Record<string, ProductInfo> | null
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

// ── Workspaces ───────────────────────────────────────────────────────────────

export interface WorkspaceOwner {
  id:   string
  name: string
}

export interface Workspace {
  id:         string
  name:       string
  status:     'active' | 'archived' | 'orphaned'
  path:       string
  owner:      WorkspaceOwner
  parent_id:  string | null
  created_at: string
}

export interface WorkspaceQuota {
  used:  number
  limit: number
}

export interface WorkspaceCapabilities {
  permissions: string[]
}

export interface WorkspaceMemberUser {
  id:         string
  name:       string
  email:      string
  avatar_url: AvatarUrl
}

export interface WorkspaceMemberRole {
  id:   string
  name: string
}

export interface WorkspaceMember {
  id:   string
  user: WorkspaceMemberUser
  role: WorkspaceMemberRole
}

export interface WorkspaceRole {
  id:          string
  name:        string
  is_base:     boolean
  permissions: string[]
}

export interface WorkspaceDetail {
  workspace:    Workspace
  ancestors:    Workspace[]
  children:     Workspace[]
  mode:         'my' | 'shared'
  role:         WorkspaceMemberRole
  capabilities: string[]
}

export interface WsMemberAddedPayload           { memberId: string; user: { id: string; name: string }; role: WorkspaceMemberRole }
export interface WsMemberRemovedPayload         { memberId: string }
export interface WsMemberRoleChangedPayload     { memberId: string; userId: string; role: WorkspaceMemberRole }
export interface WsRolePermissionsUpdatedPayload { roleId: string; permissions: string[] }
