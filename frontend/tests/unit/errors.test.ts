import { describe, expect, it } from 'vitest'
import { errorMessage, statusOf } from '~/utils/errors'

describe('statusOf', () => {
  it('membaca statusCode atau status dari objek error', () => {
    expect(statusOf({ statusCode: 401 })).toBe(401)
    expect(statusOf({ status: 422 })).toBe(422)
    expect(statusOf(new Error('network'))).toBeUndefined()
    expect(statusOf(null)).toBeUndefined()
  })
})

describe('errorMessage', () => {
  it('menggabungkan semua pesan validasi 422 dari objek errors', () => {
    const err = {
      statusCode: 422,
      data: {
        errors: {
          leaves_at: ['Waktu keluar wajib diisi.'],
          returns_at: ['Waktu kembali harus setelah waktu keluar.'],
        },
      },
    }
    expect(errorMessage(err)).toBe(
      'Waktu keluar wajib diisi. Waktu kembali harus setelah waktu keluar.',
    )
  })

  it('memetakan kode status 401, 403, 419, 429, dan gagal jaringan', () => {
    expect(errorMessage({ statusCode: 401 })).toBe('Sesi telah berakhir. Silakan login kembali.')
    expect(errorMessage({ statusCode: 403 })).toBe('Anda tidak memiliki izin untuk halaman ini.')
    expect(errorMessage({ statusCode: 419 })).toBe(
      'Sesi tidak valid. Muat ulang halaman dan coba kembali.',
    )
    expect(errorMessage({ statusCode: 429 })).toBe(
      'Terlalu banyak percobaan. Coba kembali sebentar lagi.',
    )
    expect(errorMessage(new Error('Failed to fetch'))).toBe(
      'Tidak dapat menghubungi server. Pastikan backend Laravel berjalan.',
    )
  })
})
