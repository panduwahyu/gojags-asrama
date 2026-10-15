import { describe, expect, it } from 'vitest'
import {
  sampleCateringMenu,
  sampleComplaint,
  sampleLaundryOrder,
  sampleLeavePermit,
  sampleResident,
  sampleRoom,
  sampleStay,
} from '../support/samples'

describe('test support samples', () => {
  it('generates sampleRoom with default and custom values', () => {
    const room = sampleRoom({ number: '401', floor: 4, status: 'occupied' })
    expect(room.number).toBe('401')
    expect(room.floor).toBe(4)
    expect(room.status).toBe('occupied')
    expect(room.status_label).toBe('Terisi')
    expect(room.capacity).toBe(2)
  })

  it('generates sampleResident with defaults', () => {
    const res = sampleResident({ name: 'Andi' })
    expect(res.name).toBe('Andi')
    expect(res.gender).toBe('male')
    expect(res.religion).toBe('islam')
    expect(res.room_id).toBeNull()
  })

  it('generates sampleLeavePermit with status label matching', () => {
    const permit = sampleLeavePermit({ status: 'approved' })
    expect(permit.status).toBe('approved')
    expect(permit.status_label).toBe('Disetujui')
  })

  it('generates sampleComplaint with resolved state', () => {
    const resolved = sampleComplaint({ status: 'resolved' })
    expect(resolved.status).toBe('resolved')
    expect(resolved.status_label).toBe('Resolved')
    expect(resolved.resolved_at).toBeTruthy()

    const pending = sampleComplaint({ status: 'pending' })
    expect(pending.status).toBe('pending')
    expect(pending.status_label).toBe('Diajukan')
    expect(pending.resolved_at).toBeNull()
  })

  it('generates sampleLaundryOrder with appropriate cost', () => {
    const done = sampleLaundryOrder({ status: 'done' })
    expect(done.status).toBe('done')
    expect(done.cost).toBe('15000.00')

    const pending = sampleLaundryOrder({ status: 'pending' })
    expect(pending.status).toBe('pending')
    expect(pending.cost).toBeNull()
  })

  it('generates sampleCateringMenu with meal time labels', () => {
    const breakfast = sampleCateringMenu({ meal_time: 'breakfast' })
    expect(breakfast.meal_time).toBe('breakfast')
    expect(breakfast.meal_time_label).toBe('Pagi')

    const dinner = sampleCateringMenu({ meal_time: 'dinner' })
    expect(dinner.meal_time).toBe('dinner')
    expect(dinner.meal_time_label).toBe('Malam')
  })

  it('generates sampleStay with default and custom attributes', () => {
    const stay = sampleStay({ room_number: '302', floor: 3 })
    expect(stay.room_number).toBe('302')
    expect(stay.floor).toBe(3)
    expect(stay.checked_out_at).toBeNull()
  })
})
