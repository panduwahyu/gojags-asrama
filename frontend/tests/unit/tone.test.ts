import { describe, expect, it } from 'vitest'
import { CATERING_TONE } from '../../app/types/catering'
import { COMPLAINT_TONE } from '../../app/types/complaint'
import { LAUNDRY_TONE } from '../../app/types/laundry'
import { PERMIT_TONE } from '../../app/types/leave-permit'
import { ROOM_TONE } from '../../app/types/room'

describe('status tone mappings', () => {
  it('maps room status to semantic tones', () => {
    expect(ROOM_TONE.empty).toBe('success')
    expect(ROOM_TONE.assigned).toBe('info')
    expect(ROOM_TONE.occupied).toBe('warning')
    expect(ROOM_TONE.full).toBe('danger')
  })

  it('maps laundry status to semantic tones', () => {
    expect(LAUNDRY_TONE.pending).toBe('info')
    expect(LAUNDRY_TONE.done).toBe('success')
  })

  it('maps catering status to semantic tones', () => {
    expect(CATERING_TONE.scheduled).toBe('info')
    expect(CATERING_TONE.preparing).toBe('warning')
    expect(CATERING_TONE.done).toBe('success')
  })

  it('maps leave permit status to semantic tones', () => {
    expect(PERMIT_TONE.pending).toBe('info')
    expect(PERMIT_TONE.approved).toBe('success')
    expect(PERMIT_TONE.rejected).toBe('danger')
  })

  it('maps complaint status to semantic tones', () => {
    expect(COMPLAINT_TONE.pending).toBe('info')
    expect(COMPLAINT_TONE.resolved).toBe('success')
  })
})
