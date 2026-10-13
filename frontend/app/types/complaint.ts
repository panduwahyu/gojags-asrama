import type { Tone } from './api'

export type ComplaintStatus = 'pending' | 'resolved'

export type Complaint = {
  id: number
  user_name: string
  title: string
  body: string
  status: ComplaintStatus
  status_label: string
  created_at: string
  resolved_at: string | null
}

export type ComplaintInput = {
  title: string
  body: string
}

export const COMPLAINT_TONE: Record<ComplaintStatus, Tone> = {
  pending: 'info',
  resolved: 'success',
}
