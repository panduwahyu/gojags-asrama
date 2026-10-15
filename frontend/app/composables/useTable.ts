import type { Paginated } from '~/types/api'

export type SortDirection = 'asc' | 'desc'

export function useTable<T, R extends Paginated<T> = Paginated<T>>(path: string) {
  const api = useApi()

  const search = ref('')
  const committedQuery = ref('')
  const sort = ref('id')
  const direction = ref<SortDirection>('desc')
  const page = ref(1)
  const perPageSize = ref(10)

  const rows = shallowRef<T[]>([])
  const meta = ref<Paginated<T>['meta'] | undefined>(undefined)
  const response = shallowRef<R | null>(null)
  const loading = ref(false)
  const loadError = ref(false)

  let debounceTimer: ReturnType<typeof setTimeout> | undefined

  function cancelDebounce() {
    if (debounceTimer !== undefined) {
      clearTimeout(debounceTimer)
    }
  }

  const perPage = computed({
    get: () => perPageSize.value,
    set: (value: number) => {
      perPageSize.value = value
      page.value = 1
    },
  })

  let currentRequestId = 0

  async function refresh() {
    const requestId = ++currentRequestId
    loading.value = true
    loadError.value = false
    try {
      const data = await api.get<R>(path, {
        q: committedQuery.value || undefined,
        sort: sort.value,
        direction: direction.value,
        per_page: perPageSize.value,
        page: page.value,
      })
      if (requestId !== currentRequestId) {
        return
      }
      response.value = data
      rows.value = data.data
      meta.value = data.meta

      // Halaman yang diminta sudah tidak ada (datanya berkurang): pindah ke halaman terakhir.
      if (data.meta.last_page >= 1 && page.value > data.meta.last_page) {
        page.value = data.meta.last_page
      }
    } catch {
      if (requestId === currentRequestId) {
        loadError.value = true
      }
    } finally {
      if (requestId === currentRequestId) {
        loading.value = false
      }
    }
  }

  function applySearch() {
    cancelDebounce()
    const unchanged = committedQuery.value === search.value && page.value === 1
    committedQuery.value = search.value
    page.value = 1
    if (unchanged) {
      refresh()
    }
  }

  function resetSearch() {
    search.value = ''
    applySearch()
  }

  function sortBy(column: string) {
    cancelDebounce()
    if (sort.value === column) {
      direction.value = direction.value === 'asc' ? 'desc' : 'asc'
    } else {
      sort.value = column
      direction.value = 'asc'
    }
    committedQuery.value = search.value
    page.value = 1
  }

  function goToFirstPage() {
    page.value = 1
  }

  watch(search, (value) => {
    cancelDebounce()
    debounceTimer = setTimeout(() => {
      committedQuery.value = value
      page.value = 1
    }, 300)
  })

  watch([committedQuery, sort, direction, page, perPageSize], () => {
    refresh()
  }, { immediate: true })

  onBeforeUnmount(cancelDebounce)

  return {
    rows,
    meta,
    response,
    loading,
    loadError,
    search,
    committedQuery,
    sort,
    direction,
    page,
    perPage,
    sortBy,
    applySearch,
    resetSearch,
    goToFirstPage,
    refresh,
  }
}
