export function useTableSearch() {
  const route = useRoute()
  const search = ref(String(route.query.q || ''))
  const initialSize = Number(route.query.per_page)
  const parameters = ref({ q: search.value, page: Math.max(1, Number(route.query.page) || 1), per_page: [10, 50, 100].includes(initialSize) ? initialSize : 10, sort: String(route.query.sort || 'id'), direction: route.query.direction === 'asc' ? 'asc' : 'desc' })
  function sortBy(column: string) {
    clearTimeout(timer)
    parameters.value = { ...parameters.value, q: search.value, page: 1, sort: column, direction: parameters.value.sort === column && parameters.value.direction === 'asc' ? 'desc' : 'asc' }
  }
  const perPage = computed({
    get: () => parameters.value.per_page,
    set: (value: number) => { parameters.value = { ...parameters.value, q: search.value, page: 1, per_page: value } },
  })
  const query = computed(() => parameters.value.q)
  const page = computed({
    get: () => parameters.value.page,
    set: (value: number) => { parameters.value = { ...parameters.value, page: value } },
  })
  let timer: ReturnType<typeof setTimeout> | undefined
  function find() {
    clearTimeout(timer)
    parameters.value = { ...parameters.value, q: search.value, page: 1 }
  }
  watch(search, () => { clearTimeout(timer); timer = setTimeout(find, 300) })
  onBeforeUnmount(() => clearTimeout(timer))
  return { search, query, page, perPage, parameters, find, sortBy }
}
