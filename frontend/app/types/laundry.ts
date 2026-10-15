import type { Tone } from './api'

export type LaundryStatus = 'pending' | 'done' | 'cancelled'

export type LaundryOrder = {
  id: number
  user_name: string
  items: string
  set_count: number
  status: LaundryStatus
  status_label: string
  cost: string | null
  created_at: string
}

export type LaundryOrderInput = {
  items: string
  set_count: number
}

/** Admin hanya memilih Diajukan atau Selesai; pembatalan dilakukan penghuni. */
export type LaundryStatusInput = {
  status: 'pending' | 'done'
  cost: number | null
}

export const LAUNDRY_TONE: Record<LaundryStatus, Tone> = {
  pending: 'info',
  done: 'success',
  cancelled: 'neutral',
}
