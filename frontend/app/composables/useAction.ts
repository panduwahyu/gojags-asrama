import type { ApiMessage } from '~/types/api'

export function useAction() {
  const busy = ref(false)
  const error = ref('')
  const success = ref('')

  /** Menjalankan aksi tulis; mengembalikan true bila berhasil. */
  async function run(task: () => Promise<ApiMessage | void>): Promise<boolean> {
    busy.value = true
    error.value = ''
    success.value = ''
    try {
      const result = await task()
      success.value = result?.message ?? ''
      return true
    } catch (caught) {
      error.value = errorMessage(caught)
      return false
    } finally {
      busy.value = false
    }
  }

  return { busy, error, success, run }
}
