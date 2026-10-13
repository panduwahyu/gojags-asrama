import type { Tone } from './api'

export type CateringStatus = 'scheduled' | 'preparing' | 'done'

export type CateringMenu = {
  id: number
  menu_number: number
  provider: string
  served_at: string
  status: CateringStatus
  status_label: string
  rating_average: number | null
  rating_count: number
  my_rating: number | null
}

export type CateringMenuInput = {
  menu_number: number
  provider: string
  served_at: string
}

export const CATERING_TONE: Record<CateringStatus, Tone> = {
  scheduled: 'info',
  preparing: 'warning',
  done: 'success',
}
