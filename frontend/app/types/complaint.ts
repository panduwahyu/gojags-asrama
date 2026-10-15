import type { Tone } from './api'

export type ComplaintStatus = 'pending' | 'in_progress' | 'resolved'

export type Complaint = {
  id: number
  user_name: string
  title: string
  body: string
  status: ComplaintStatus
  status_label: string
  response: string | null
  created_at: string
  resolved_at: string | null
}

export type ComplaintInput = {
  title: string
  body: string
}

export const COMPLAINT_TONE: Record<ComplaintStatus, Tone> = {
  pending: 'info',
  in_progress: 'warning',
  resolved: 'success',
}
