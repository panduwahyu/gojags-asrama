import type { Tone } from './api'

export type PermitStatus = 'pending' | 'approved' | 'rejected' | 'cancelled'

export type LeavePermit = {
  id: number
  user_name: string
  leaves_at: string
  returns_at: string
  reason: string
  status: PermitStatus
  status_label: string
  rejection_reason: string | null
}

export type LeavePermitInput = {
  leaves_at: string
  returns_at: string
  reason: string
}

export type PermitReviewInput = {
  status: 'approved' | 'rejected'
  rejection_reason?: string
}

export const PERMIT_TONE: Record<PermitStatus, Tone> = {
  pending: 'info',
  approved: 'success',
  rejected: 'danger',
  cancelled: 'neutral',
}
