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

  return { user, csrfToken, isAdmin, apply, load }
}
