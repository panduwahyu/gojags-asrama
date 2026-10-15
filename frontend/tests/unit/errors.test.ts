import { describe, expect, it } from 'vitest'
import { errorMessage, statusOf, validationMessages } from '~/utils/errors'
import { MESSAGES } from '../support/messages'

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
    expect(errorMessage(new Error('Failed to fetch'))).toBe(MESSAGES.CLIENT_NETWORK)
  })

  it('memetakan 404 dan galat server 5xx ke pesan yang sesuai, bukan pesan jaringan', () => {
    expect(errorMessage({ statusCode: 404 })).toBe(MESSAGES.CLIENT_NOT_FOUND)
    expect(errorMessage({ statusCode: 500 })).toBe(MESSAGES.CLIENT_SERVER_ERROR)
    expect(errorMessage({ statusCode: 503 })).toBe(MESSAGES.CLIENT_SERVER_ERROR)
  })

  it('tidak menyebut nama teknologi kepada pengguna akhir', () => {
    for (const status of [404, 500, undefined]) {
      expect(errorMessage({ statusCode: status })).not.toMatch(/laravel|backend/i)
    }
  })
})

describe('validationMessages', () => {
  it('mengambil daftar pesan dari satu kunci galat 422', () => {
    const error = { data: { errors: { file: ['Baris 2: A.', 'Baris 3: B.'], other: ['X'] } } }
    expect(validationMessages(error, 'file')).toEqual(['Baris 2: A.', 'Baris 3: B.'])
    expect(validationMessages(error, 'missing')).toEqual([])
    expect(validationMessages(new Error('x'), 'file')).toEqual([])
  })
})
