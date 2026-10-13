import type { ApiMessage } from '~/types/api'

type Method = 'POST' | 'PATCH' | 'PUT'

export function useApi() {
  const { csrfToken, load } = useSession()

  function get<T>(path: string, query?: Record<string, string | number | undefined>): Promise<T> {
    return $fetch<T>(`/api/${path}`, { query })
  }

  async function send<T = ApiMessage>(method: Method, path: string, body?: object): Promise<T> {
    const request = () => $fetch<T>(`/api/${path}`, {
      method,
      body,
      headers: { 'X-CSRF-TOKEN': csrfToken.value },
    })

    try {
      return await request()
    } catch (error) {
      if (statusOf(error) !== 419) {
        throw error
      }
      await load()
      return await request()
    }
  }

  return {
    get,
    post: <T = ApiMessage>(path: string, body?: object) => send<T>('POST', path, body),
    patch: <T = ApiMessage>(path: string, body?: object) => send<T>('PATCH', path, body),
    put: <T = ApiMessage>(path: string, body?: object) => send<T>('PUT', path, body),
  }
}
