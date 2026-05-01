export interface UsageSummary {
  total_input: number
  total_output: number
  total_tokens: number
  total_conversations: number
  top_model: string | null
}

export interface UsageTimelinePoint {
  date: string
  input_tokens: number
  output_tokens: number
}

export interface UsageBreakdownItem {
  key: string
  input_tokens: number
  output_tokens: number
  total: number
}

export interface UsageTopUser {
  user_id: number
  name: string
  email: string
  input_tokens: number
  output_tokens: number
  total_tokens: number
  conversations: number
}

export interface AdminUser {
  id: number
  name: string
  email: string
  is_admin: boolean
  created_at: string
  total_tokens: number
}

export interface AdminUserDetail extends AdminUser {
  total_input: number
  total_output: number
  total_conversations: number
  usage_by_model: Array<{ model: string; total: number }>
}

export interface AdminConversation {
  id: number
  user_id: number
  user_name: string
  user_email: string
  message_count: number
  total_tokens: number
  created_at: string
}

export interface AdminPagination {
  current_page: number
  last_page: number
  total: number
}

export type BreakdownGroupBy = 'model' | 'provider' | 'type'
export type TimelineGroupBy  = 'day' | 'week'

export interface UsageFilters {
  from: string
  to: string
  provider?: string
  model?: string
  type?: string
}
