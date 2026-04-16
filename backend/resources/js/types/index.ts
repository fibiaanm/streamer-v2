export interface LimitValue {
  type: 'permanent' | 'concurrent' | 'monthly'
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
  type: 'personal' | 'enterprise'
  role: string
  permissions: string[]
  plan: Plan
}

export interface SessionUser {
  id: string
  name: string
  email: string
  enterprise: Enterprise
}

export interface ApiError {
  code: string
  context?: Record<string, unknown>
}

export interface ApiResponse<T> {
  data: T
}
