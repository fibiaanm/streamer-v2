export interface UsageSummary {
  total_input: number
  total_output: number
  total_tokens: number
  total_records: number
  total_conversations: number
  avg_tokens_per_conv: number | null
  memory_tokens: number
  top_model: string | null
  last_run_at: string | null
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
  requests: number
}

export interface AdminUser {
  id: number
  name: string
  email: string
  is_admin: boolean
  created_at: string
}

export interface AdminUserDetail extends AdminUser {
  total_input: number
  total_output: number
  total_requests: number
  usage_by_model: Array<{ model: string; total: number }>
}

export interface AdminConversation {
  id: number
  user_id: number
  user_name: string
  user_email: string
  title: string | null
  message_count: number
  cost: { input: number; output: number; total: number }
  created_at: string
}

export interface AdminConversationMessage {
  id: number
  session_id: number
  role: 'user' | 'assistant' | 'system' | 'tool_call' | 'tool_result'
  channel: string
  content: string
  actions_json: unknown | null
  metadata_json: unknown | null
  memory_processed: boolean
  created_at: string
}

export interface AdminConversationDetail {
  id: number
  user_id: number
  user_name: string
  user_email: string
  title: string | null
  message_count: number
  cost: { input: number; output: number; total: number }
  created_at: string
  messages: AdminConversationMessage[]
}

export interface AdminJob {
  id: number
  queue: string
  display_name: string
  attempts: number
  available_at: number
  created_at: number
}

export interface AdminJobDetail extends AdminJob {
  reminder: {
    id: number
    message: string
    fire_at: string
    status: string
    fired_at: string | null
    event: {
      id: number
      content: string
      event_at: string
      type: string
      user_id: number
      user: { name: string; email: string } | null
    } | null
  } | null
}

export interface AdminPagination {
  current_page: number
  last_page?: number
  total?: number
  has_more?: boolean
}

export interface FailedJobsSummary {
  total: number
  last_failed_at: string | null
  most_failing_job: string | null
  by_queue: Array<{ queue: string; count: number }>
  queues: string[]
}

export interface FailedJobsTimelinePoint {
  date: string
  queue: string
  count: number
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
