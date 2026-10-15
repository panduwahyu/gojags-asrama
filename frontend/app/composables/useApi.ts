import type { ApiMessage } from '~/types/api'

type Method = 'POST' | 'PATCH' | 'PUT' | 'DELETE'
type Query = Record<string, string | number | undefined>

export function useApi() {
  const { csrfToken, load, expire } = useSession()

  /** Jawaban 401 berarti sesi habis: pengguna dialihkan ke login, galatnya tetap dilempar. */
  async function guarded<T>(task: () => Promise<T>, expireOn401 = true): Promise<T> {
    try {
      return await task()
    } catch (error) {
      if (expireOn401 && statusOf(error) === 401) {
        await expire()
      }
      throw error
    }
  }

  function get<T>(path: string, query?: Query): Promise<T> {
    return guarded(() => $fetch<T>(`/api/${path}`, { query }))
  }

  function send<T = ApiMessage>(method: Method, path: string, body?: object): Promise<T> {
    const request = () => $fetch<T>(`/api/${path}`, {
      method,
      body,
      headers: { 'X-CSRF-TOKEN': csrfToken.value },
    })

    // Login yang salah juga bisa dijawab 4xx, tetapi itu bukan sesi yang habis.
    return guarded(async () => {
      try {
        return await request()
      } catch (error) {
        if (statusOf(error) !== 419) {
          throw error
        }
        await load()
        return await request()
      }
    }, path !== 'login')
  }

  /** Mengunduh berkas (Excel/PDF) hasil GET dan menyimpannya lewat peramban. */
  async function download(path: string, query: Query, fallbackName: string): Promise<void> {
    const response = await guarded(() => $fetch.raw<Blob>(`/api/${path}`, { query, responseType: 'blob' }))
    const disposition = response.headers.get('content-disposition') ?? ''
    const filename = /filename="?([^";]+)"?/.exec(disposition)?.[1] ?? fallbackName
    const url = URL.createObjectURL(response._data as Blob)
    const link = document.createElement('a')
    link.href = url
    link.download = filename
    link.click()
    URL.revokeObjectURL(url)
  }

  return {
    get,
    download,
    post: <T = ApiMessage>(path: string, body?: object) => send<T>('POST', path, body),
    patch: <T = ApiMessage>(path: string, body?: object) => send<T>('PATCH', path, body),
    put: <T = ApiMessage>(path: string, body?: object) => send<T>('PUT', path, body),
    delete: <T = ApiMessage>(path: string) => send<T>('DELETE', path),
  }
}
