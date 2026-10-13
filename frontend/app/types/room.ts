import type { Paginated, Tone } from './api'

export type RoomStatus = 'empty' | 'assigned' | 'occupied' | 'full'

export type Gender = 'male' | 'female'

export type RoomResident = {
  id: number
  name: string
  checked_in_at: string | null
}

export type Room = {
  id: number
  number: string
  floor: number
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
  cohort: string | null
  gender: Gender | null
  religion: string | null
  room_id: number | null
}

export type ResidentProfileInput = {
  cohort: string
  gender: Gender
  religion: string
}

export type AllocationResult = {
  message: string
  allocated: { id: number; name: string; room: string; floor: number }[]
  waiting: { id: number; name: string }[]
  incomplete: { id: number; name: string }[]
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
}
