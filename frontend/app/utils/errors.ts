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
  if (status === 401) {
    return 'Sesi telah berakhir. Silakan login kembali.'
  }
  if (status === 403) {
    return 'Anda tidak memiliki izin untuk halaman ini.'
  }
  if (status === 419) {
    return 'Sesi tidak valid. Muat ulang halaman dan coba kembali.'
  }
  if (status === 429) {
    return 'Terlalu banyak percobaan. Coba kembali sebentar lagi.'
  }

  return 'Tidak dapat menghubungi server. Pastikan backend Laravel berjalan.'
}
