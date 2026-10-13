import { describe, expect, it } from 'vitest'
import { formatWib, isoToWibInput, wibInputToIso } from '~/utils/datetime'

describe('wibInputToIso', () => {
  it('mengubah jam WIB menjadi UTC', () => {
    expect(wibInputToIso('2026-10-13T08:00')).toBe('2026-10-13T01:00:00.000Z')
  })

  it('melewati batas hari (lewat tengah malam)', () => {
    expect(wibInputToIso('2026-10-13T03:00')).toBe('2026-10-12T20:00:00.000Z')
  })
})

describe('isoToWibInput', () => {
  it('adalah kebalikan wibInputToIso', () => {
    expect(isoToWibInput(wibInputToIso('2026-10-13T23:59'))).toBe('2026-10-13T23:59')
  })

  it('mengubah waktu UTC malam hari ke hari berikutnya dalam WIB', () => {
    expect(isoToWibInput('2026-10-12T20:00:00.000Z')).toBe('2026-10-13T03:00')
  })
})

describe('formatWib', () => {
  it('memformat tanggal ISO ke tampilan WIB berakhiran WIB', () => {
    expect(formatWib('2026-10-13T07:30:00.000Z')).toContain('WIB')
  })
})
