import type { Paginated, Tone } from './api'

export type RoomStatus = 'empty' | 'assigned' | 'occupied' | 'full' | 'inactive'

export type Gender = 'male' | 'female'

export type Religion = 'islam' | 'kristen' | 'katolik' | 'hindu' | 'buddha' | 'konghucu'

export const RELIGIONS: { value: Religion; label: string }[] = [
  { value: 'islam', label: 'Islam' },
  { value: 'kristen', label: 'Kristen' },
  { value: 'katolik', label: 'Katolik' },
  { value: 'hindu', label: 'Hindu' },
  { value: 'buddha', label: 'Buddha' },
  { value: 'konghucu', label: 'Konghucu' },
]

export type RoomResident = {
  id: number
  name: string
  checked_in_at: string | null
}

export type Room = {
  id: number
  number: string
  floor: number
  is_active: boolean
  status: RoomStatus
  status_label: string
  assigned_count: number
  capacity: number
  residents: RoomResident[]
}

export type RoomMonitoring = Record<RoomStatus, number>

export type RoomsResponse = Paginated<Room> & {
  monitoring: RoomMonitoring
}

export type Resident = {
  id: number
  name: string
  email: string
  cohort: string | null
  gender: Gender | null
  religion: Religion | null
  room_id: number | null
  room_number: string | null
  checked_in_at: string | null
  can_cancel_check_out: boolean
}

export type ResidentProfileInput = {
  cohort: string
  gender: Gender
  religion: Religion
}

export type ResidentInput = ResidentProfileInput & {
  name: string
  email: string
}

/** Akun baru beserta sandi awal; hanya ditampilkan sekali. */
export type Credential = {
  name: string
  email: string
  password: string
}

export type AllocationResult = {
  message: string
  allocated: { id: number; name: string; room: string; floor: number; notes: string[] }[]
  waiting: { id: number; name: string }[]
  incomplete: { id: number; name: string }[]
}

/** Jawaban penempatan/pemindahan manual dan perbaikan profil. */
export type WarningsResult = {
  message: string
  warnings: string[]
}

export type MyRoom = {
  number: string
  floor: number
  checked_in_at: string | null
}

export type Stay = {
  id: number
  room_number: string
  floor: number
  checked_in_at: string
  checked_out_at: string | null
}

export type MyRoomData = {
  room: MyRoom | null
  stays: Stay[]
}

export const ROOM_TONE: Record<RoomStatus, Tone> = {
  empty: 'success',
  assigned: 'info',
  occupied: 'warning',
  full: 'danger',
  inactive: 'neutral',
}
