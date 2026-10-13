import { describe, expect, it } from 'vitest'
import { formatRupiah } from '~/utils/currency'

describe('formatRupiah', () => {
  it('mengembalikan Belum ditentukan untuk null atau string kosong', () => {
    expect(formatRupiah(null)).toBe('Belum ditentukan')
    expect(formatRupiah('')).toBe('Belum ditentukan')
  })

  it('memformat angka ke mata uang Rupiah id-ID', () => {
    const formatted = formatRupiah('25000.00')
    expect(formatted).toContain('Rp')
    expect(formatted).toContain('25.000')
  })
})
