import type { CateringMenu } from '~/types/catering'
import type { Complaint } from '~/types/complaint'
import type { LaundryOrder } from '~/types/laundry'
import type { LeavePermit } from '~/types/leave-permit'
import type { Resident, Room, Stay } from '~/types/room'

let nextId = 1

function takeId(): number {
  const id = nextId
  nextId += 1
  return id
}

export function sampleRoom(overrides: Partial<Room> = {}): Room {
  const id = overrides.id ?? takeId()
  const status = overrides.status ?? 'empty'
  const labels: Record<Room['status'], string> = {
    empty: 'Kosong',
    assigned: 'Assigned',
    occupied: 'Terisi',
    full: 'Penuh',
  }
  return {
    id,
    number: `50${id}`,
    floor: 5,
    status,
    status_label: labels[status],
    assigned_count: 0,
    capacity: 2,
    residents: [],
    ...overrides,
  }
}

export function sampleResident(overrides: Partial<Resident> = {}): Resident {
  const id = overrides.id ?? takeId()
  return {
    id,
    name: `Penghuni ${id}`,
    cohort: '2026',
    gender: 'male',
    religion: 'islam',
    room_id: null,
    ...overrides,
  }
}

export function sampleLeavePermit(overrides: Partial<LeavePermit> = {}): LeavePermit {
  const id = overrides.id ?? takeId()
  const status = overrides.status ?? 'pending'
  const labels: Record<LeavePermit['status'], string> = {
    pending: 'Diajukan',
    approved: 'Disetujui',
    rejected: 'Ditolak',
  }
  return {
    id,
    user_name: 'Penghuni Uji',
    leaves_at: '2026-10-13T01:00:00.000000Z',
    returns_at: '2026-10-13T11:00:00.000000Z',
    reason: 'Keperluan keluarga',
    status,
    status_label: labels[status],
    ...overrides,
  }
}

export function sampleComplaint(overrides: Partial<Complaint> = {}): Complaint {
  const id = overrides.id ?? takeId()
  const status = overrides.status ?? 'pending'
  return {
    id,
    user_name: 'Penghuni Uji',
    title: 'Lampu kamar redup',
    body: 'Lampu utama kamar berkedip sejak kemarin malam.',
    status,
    status_label: status === 'resolved' ? 'Resolved' : 'Diajukan',
    created_at: '2026-10-13T02:00:00.000000Z',
    resolved_at: status === 'resolved' ? '2026-10-13T04:00:00.000000Z' : null,
    ...overrides,
  }
}

export function sampleLaundryOrder(overrides: Partial<LaundryOrder> = {}): LaundryOrder {
  const id = overrides.id ?? takeId()
  const status = overrides.status ?? 'pending'
  return {
    id,
    user_name: 'Penghuni Uji',
    items: 'Kemeja dan celana',
    set_count: 3,
    status,
    status_label: status === 'done' ? 'Selesai' : 'Diajukan',
    cost: status === 'done' ? '15000.00' : null,
    created_at: '2026-10-13T03:00:00.000000Z',
    ...overrides,
  }
}

export function sampleCateringMenu(overrides: Partial<CateringMenu> = {}): CateringMenu {
  const id = overrides.id ?? takeId()
  const status = overrides.status ?? 'scheduled'
  const labels: Record<CateringMenu['status'], string> = {
    scheduled: 'Terjadwal',
    preparing: 'Disiapkan',
    done: 'Selesai',
  }
  return {
    id,
    menu_number: id,
    provider: 'Dapur Nusantara',
    served_at: '2026-10-13T05:00:00.000000Z',
    status,
    status_label: labels[status],
    rating_average: null,
    rating_count: 0,
    my_rating: null,
    ...overrides,
  }
}

export function sampleStay(overrides: Partial<Stay> = {}): Stay {
  const id = overrides.id ?? takeId()
  return {
    id,
    room_number: '501',
    floor: 5,
    checked_in_at: '2026-10-01T02:00:00.000000Z',
    checked_out_at: null,
    ...overrides,
  }
}
