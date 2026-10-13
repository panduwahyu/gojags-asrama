export type User = { id: number; name: string; email: string; role: 'admin' | 'user' }
export type Category = 'kamar' | 'laundry' | 'catering'
export type AsramaRecord = { id: number; name: string; detail: string; status: string; date: string }
export type PageData = {
  counts: Record<Category, number>
  statuses: string[]
  records: { data: AsramaRecord[]; current_page: number; last_page: number }
}

export function useAsrama() {
  const feedback = useFeedback()
  const user = useState<User | null>('asrama-user', () => null)
  const csrf = useState<string>('asrama-csrf', () => '')
  async function session() {
    const data = await $fetch<{ user: User | null; csrfToken: string }>('/backend/api/asrama/session')
    user.value = data.user
    csrf.value = data.csrfToken
  }
  async function mutate(path: string, body: Record<string, unknown> = {}, method: 'POST' | 'PATCH' = 'POST') {
    const trigger = document.activeElement instanceof HTMLElement ? document.activeElement : null
    try {
      await session()
      const response = await $fetch<{ message?: string }>(`/backend/api/asrama/${path}`, {
        method, body, headers: { 'X-CSRF-TOKEN': csrf.value, Accept: 'application/json' },
      })
      await session()
      const message = response.message || (path === 'login' ? 'Login berhasil.' : 'Perubahan berhasil disimpan.')
      feedback.show(message, 'success', path === 'user/izin-keluar' && method === 'POST' ? 'Berhasil diajukan' : undefined, trigger)
      return response
    } catch (error) {
      try { await session() } catch { /* Preserve the original action error if session refresh also fails. */ }
      feedback.show(errorMessage(error), 'error', undefined, trigger)
      throw error
    }
  }
  function errorMessage(error: unknown): string {
    const e = error as { data?: { errors?: Record<string, string[]>; message?: string }; statusCode?: number }
    if (e.data?.errors) return Object.values(e.data.errors).flat().join(' ')
    if (e.statusCode === 401) return 'Sesi telah berakhir. Silakan login kembali.'
    if (e.statusCode === 403) return 'Anda tidak memiliki izin untuk halaman ini.'
    if (e.statusCode === 419) return 'Sesi tidak valid. Muat ulang halaman dan coba kembali.'
    if (e.statusCode === 429) return 'Terlalu banyak percobaan. Coba kembali sebentar lagi.'
    return 'Tidak dapat menghubungi server. Pastikan backend Laravel berjalan.'
  }
  return { user, session, mutate, errorMessage }
}
