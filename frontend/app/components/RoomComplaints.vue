<script setup lang="ts">
type Complaint = { id: number; user_name: string; judul: string; isi: string; status: 'Diajukan' | 'Resolved'; created_at: string; resolved_at: string | null }
const props = defineProps<{ admin?: boolean }>()
const { mutate, errorMessage } = useAsrama()
const endpoint = props.admin ? 'keluhan' : 'user/keluhan'
// Keep this table's filters independent from rooms and leave permissions.
const parameters = ref({ q: '', page: 1, per_page: 10, sort: 'id', direction: 'desc' })
const search = ref('')
const page = computed({ get: () => parameters.value.page, set: (value: number) => { parameters.value = { ...parameters.value, page: value } } })
const perPage = computed({ get: () => parameters.value.per_page, set: (value: number) => { parameters.value = { ...parameters.value, page: 1, per_page: value } } })
const { data, status, error: loadError, refresh } = await useFetch<{ records: { data: Complaint[]; current_page: number; last_page: number } }>(`/backend/api/asrama/${endpoint}`, { query: parameters })
const form = reactive({ judul: '', isi: '' })
const busy = ref(false)
const error = ref('')
const success = ref('')
const columns = computed(() => [
  ...(props.admin ? [{ key: 'user_name', label: 'Nama user' }] : []),
  { key: 'judul', label: 'Judul' }, { key: 'isi', label: 'Isi keluhan' }, { key: 'created_at', label: 'Waktu diajukan' },
  { key: 'status', label: 'Status' }, { key: 'resolved_at', label: 'Waktu resolved' },
])
let debounce: ReturnType<typeof setTimeout> | undefined
let poll: ReturnType<typeof setInterval> | undefined
function find() {
  clearTimeout(debounce)
  parameters.value = { ...parameters.value, q: search.value, page: 1 }
}
watch(search, () => { clearTimeout(debounce); debounce = setTimeout(find, 300) })
function sortBy(sort: string) {
  clearTimeout(debounce)
  parameters.value = { ...parameters.value, q: search.value, page: 1, sort, direction: parameters.value.sort === sort && parameters.value.direction === 'asc' ? 'desc' : 'asc' }
}
function time(value: string | null) {
  return value ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short', timeZone: 'Asia/Jakarta' }).format(new Date(value)) + ' WIB' : 'Belum diselesaikan'
}
async function submit() {
  busy.value = true; error.value = ''; success.value = ''
  try {
    const response = await mutate(endpoint, { ...form }) as { message: string }
    form.judul = ''; form.isi = ''; success.value = response.message
    page.value = 1
    await refresh()
  } catch (e) { error.value = errorMessage(e) }
  finally { busy.value = false }
}
async function resolve(id: number) {
  busy.value = true; error.value = ''; success.value = ''
  try {
    const response = await mutate(`keluhan/${id}`, { status: 'Resolved' }, 'PATCH') as { message: string }
    success.value = response.message
    await refresh()
  } catch (e) { error.value = errorMessage(e) }
  finally { busy.value = false }
}
onMounted(() => { poll = setInterval(() => { if (!document.hidden && !busy.value && status.value !== 'pending') refresh() }, 15000) })
onBeforeUnmount(() => { clearTimeout(debounce); clearInterval(poll) })
</script>

<template>
  <section aria-label="Keluhan asrama" class="card mt-6 p-4 md:p-6">
    <h2 class="text-xl font-semibold">Keluhan asrama</h2>
    <p class="muted mt-2 text-sm">{{ admin ? 'Tinjau keluhan penghuni dan tandai Resolved setelah ditangani.' : 'Sampaikan keluhan terkait asrama, lalu pantau status penanganannya di bawah.' }}</p>
    <p v-if="error || loadError" role="alert" class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700 dark:bg-red-950/50 dark:text-red-300">{{ error || 'Data keluhan tidak dapat dimuat.' }} <button v-if="loadError" class="underline" @click="refresh()">Coba lagi</button></p>
    <p v-if="success" role="status" class="mt-4 rounded-lg bg-green-50 p-3 text-sm text-green-700 dark:bg-green-950/50 dark:text-green-300">{{ success }}</p>
    <form v-if="!admin" class="mt-5 space-y-4" @submit.prevent="submit">
      <label>Judul keluhan<input v-model="form.judul" maxlength="150" required :disabled="busy"></label>
      <label>Isi keluhan<textarea v-model="form.isi" maxlength="2000" rows="4" required :disabled="busy" placeholder="Jelaskan keluhan dan lokasi yang perlu ditangani." class="rounded-lg border border-slate-200 bg-white p-3 outline-none focus:border-orange-400 dark:border-slate-700 dark:bg-slate-800" /></label>
      <button class="btn" :disabled="busy">{{ busy ? 'Memproses...' : 'Kirim keluhan' }}</button>
    </form>
    <form class="my-6 flex gap-3" @submit.prevent="find"><input v-model="search" aria-label="Cari keluhan asrama" placeholder="Cari keluhan..." maxlength="100"><button class="btn" :disabled="status === 'pending'">Cari</button><button v-if="search" type="button" class="muted text-xs" @click="search = ''; find()">Reset</button></form>
    <p v-if="status === 'pending'" role="status" class="muted mb-3 text-sm">Memuat keluhan...</p>
    <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs text-slate-500 dark:bg-slate-800 dark:text-slate-400"><tr>
      <SortableHeader v-for="column in columns" :key="column.key" :column="column.key" :label="column.label" :sort="parameters.sort" :direction="parameters.direction" :loading="status === 'pending'" @sort="sortBy" /><th v-if="admin" class="p-3">Aksi</th>
    </tr></thead><tbody>
      <tr v-for="row in data?.records.data" :key="row.id" class="border-b border-slate-100 dark:border-slate-800">
        <td v-if="admin" class="p-3">{{ row.user_name }}</td><td class="min-w-36 break-words p-3 font-semibold">{{ row.judul }}</td><td class="min-w-48 whitespace-pre-wrap break-words p-3">{{ row.isi }}</td><td class="whitespace-nowrap p-3">{{ time(row.created_at) }}</td>
        <td class="p-3"><span class="rounded-full px-3 py-1 text-xs font-semibold" :class="row.status === 'Resolved' ? 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300' : 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300'">{{ row.status }}</span></td><td class="whitespace-nowrap p-3">{{ time(row.resolved_at) }}</td>
        <td v-if="admin" class="p-3"><button v-if="row.status === 'Diajukan'" class="btn whitespace-nowrap" :disabled="busy" @click="resolve(row.id)">Tandai Resolved</button><span v-else class="muted text-xs">Sudah ditangani</span></td>
      </tr>
      <tr v-if="data && !data.records.data.length"><td :colspan="admin ? 7 : 5" class="muted py-10 text-center">Belum ada keluhan asrama.</td></tr>
    </tbody></table></div>
    <TablePagination v-if="data" v-model:page="page" v-model:per-page="perPage" :current="data.records.current_page" :last="data.records.last_page" :loading="status === 'pending'" />
  </section>
</template>
