function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null
}

export function statusOf(error: unknown): number | undefined {
  if (!isRecord(error)) {
    return undefined
  }
  if (typeof error.statusCode === 'number') {
    return error.statusCode
  }
  if (typeof error.status === 'number') {
    return error.status
  }
  return undefined
}

/** Daftar pesan validasi 422 dari satu kunci (mis. baris-baris galat impor). */
export function validationMessages(error: unknown, key: string): string[] {
  if (!isRecord(error) || !isRecord(error.data) || !isRecord(error.data.errors)) {
    return []
  }
  const entry = error.data.errors[key]
  return Array.isArray(entry) ? entry.filter((msg): msg is string => typeof msg === 'string') : []
}

export function errorMessage(error: unknown): string {
  if (isRecord(error) && isRecord(error.data) && isRecord(error.data.errors)) {
    const messages = Object.values(error.data.errors)
      .flatMap(entry => (Array.isArray(entry) ? entry : []))
      .filter((msg): msg is string => typeof msg === 'string')

    if (messages.length > 0) {
      return messages.join(' ')
    }
  }

  const status = statusOf(error)
  if (
    status === 422
    && isRecord(error)
    && isRecord(error.data)
    && typeof error.data.message === 'string'
    && error.data.message.length > 0
  ) {
    return error.data.message
  }
  if (status === 401) {
    return 'Sesi telah berakhir. Silakan login kembali.'
  }
  if (status === 403) {
    return 'Anda tidak memiliki izin untuk halaman ini.'
  }
  if (status === 404) {
    return 'Data tidak ditemukan. Mungkin sudah diubah atau dihapus.'
  }
  if (status === 419) {
    return 'Sesi tidak valid. Muat ulang halaman dan coba kembali.'
  }
  if (status === 429) {
    return 'Terlalu banyak percobaan. Coba kembali sebentar lagi.'
  }
  if (status !== undefined && status >= 500) {
    return 'Terjadi kesalahan di server. Coba kembali sebentar lagi.'
  }

  return 'Tidak dapat menghubungi server. Periksa koneksi Anda.'
}
