import type { Session, User } from '~/types/session'

export function useSession() {
  const user = useState<User | null>('session-user', () => null)
  const csrfToken = useState('session-csrf', () => '')
  const isAdmin = computed(() => user.value?.role === 'admin')

  function apply(session: Session) {
    user.value = session.user
    csrfToken.value = session.csrfToken
  }

  async function load() {
    apply(await $fetch<Session>('/api/session'))
  }

  /** Sesi habis saat halaman terbuka: kosongkan pengguna lalu kembali ke halaman login. */
  async function expire() {
    user.value = null
    if (import.meta.client && window.location.pathname === '/login') {
      return
    }
    await navigateTo('/login', { replace: true })
  }

  return { user, csrfToken, isAdmin, apply, load, expire }
}
