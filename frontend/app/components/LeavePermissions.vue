<script setup lang="ts">
type Permission = { id: number; user_name: string; waktu_keluar: string; waktu_kembali: string; alasan: string; status: 'Diajukan' | 'Disetujui' | 'Ditolak' }
type Form = { waktu_keluar: string; waktu_kembali: string; alasan: string }
const props = defineProps<{ admin?: boolean }>()
const feedback = useFeedback()
const endpoint = props.admin ? 'izin-keluar' : 'user/izin-keluar'
const { mutate, errorMessage } = useAsrama()
const parameters = ref({ q: '', page: 1, per_page: 10, sort: 'id', direction: 'desc' })
const search = ref('')
const page = computed({ get: () => parameters.value.page, set: (value: number) => { parameters.value = { ...parameters.value, page: value } } })
const perPage = computed({ get: () => parameters.value.per_page, set: (value: number) => { parameters.value = { ...parameters.value, per_page: value, page: 1 } } })
const { data, status, error: loadError, refresh } = await useFetch<{ records: { data: Permission[]; current_page: number; last_page: number } }>(`/backend/api/asrama/${endpoint}`, { query: parameters })
const form = reactive<Form>({ waktu_keluar: '', waktu_kembali: '', alasan: '' })
const editing = ref<number | null>(null)
const busy = ref(false)
const error = ref('')
const success = ref('')
const formElement = ref<HTMLFormElement | null>(null)
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
function localTime(value: string) {
  const parts = new Intl.DateTimeFormat('sv-SE', { timeZone: 'Asia/Jakarta', year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hourCycle: 'h23' }).formatToParts(new Date(value))
  const part = (key: string) => parts.find(p => p.type === key)?.value
  return `${part('year')}-${part('month')}-${part('day')}T${part('hour')}:${part('minute')}`
}
function displayTime(value: string) {
  return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short', timeZone: 'Asia/Jakarta' }).format(new Date(value)) + ' WIB'
}
function reset() {
  editing.value = null
  Object.assign(form, { waktu_keluar: '', waktu_kembali: '', alasan: '' })
}
watch(data, value => {
  const current = value?.records.data.find(row => row.id === editing.value)
  if (current && current.status !== 'Diajukan') {
    reset()
    error.value = 'Pengajuan sudah diproses admin dan tidak dapat diedit.'
    feedback.show(error.value, 'error')
  }
})
function edit(row: Permission) {
  editing.value = row.id
  Object.assign(form, { waktu_keluar: localTime(row.waktu_keluar), waktu_kembali: localTime(row.waktu_kembali), alasan: row.alasan })
  error.value = ''; success.value = ''
  formElement.value?.scrollIntoView({ behavior: 'smooth', block: 'center' })
  formElement.value?.querySelector('input')?.focus({ preventScroll: true })
}
async function submit() {
  busy.value = true; error.value = ''; success.value = ''
  try {
    const response = await mutate(editing.value ? `${endpoint}/${editing.value}` : endpoint, { ...form }, editing.value ? 'PATCH' : 'POST') as { message: string }
    success.value = response.message
    reset()
    parameters.value = { ...parameters.value, page: 1 }
    await refresh()
  } catch (e) { error.value = errorMessage(e); await refresh() }
  finally { busy.value = false }
}
async function review(id: number, decision: 'Disetujui' | 'Ditolak') {
  busy.value = true; error.value = ''; success.value = ''
  try {
    const response = await mutate(`${endpoint}/${id}`, { status: decision }, 'PATCH') as { message: string }
    success.value = response.message
    await refresh()
  } catch (e) { error.value = errorMessage(e); await refresh() }
  finally { busy.value = false }
}
onMounted(() => { poll = setInterval(() => { if (!document.hidden && !busy.value && status.value !== 'pending') refresh() }, 15000) })
onBeforeUnmount(() => { clearTimeout(debounce); clearInterval(poll) })
</script>

<template>
  <section aria-label="Izin keluar" class="card mt-6 p-4 md:p-6">
    <h2 class="text-xl font-semibold">Izin keluar</h2>
    <p class="muted mt-2 text-sm">{{ admin ? 'Tinjau pengajuan izin keluar penghuni.' : 'Isi waktu keluar, waktu kembali, dan alasan. Pengajuan dapat diedit selama berstatus Diajukan.' }} Semua waktu menggunakan WIB.</p>
    <FeedbackNotice :message="loadError ? 'Data tidak dapat dimuat. Coba lagi atau muat ulang halaman.' : ''" /><button v-if="loadError" class="muted mb-4 underline" @click="refresh()">Coba lagi</button>

    <form v-if="!admin" ref="formElement" class="mt-5 space-y-4" @submit.prevent="submit">
      <h3 class="font-semibold">{{ editing ? 'Edit pengajuan izin' : 'Ajukan izin keluar' }}</h3>
      <div class="grid gap-4 sm:grid-cols-2">
        <label>Waktu keluar (WIB)<input v-model="form.waktu_keluar" type="datetime-local" required :disabled="busy"></label>
        <label>Waktu kembali (WIB)<input v-model="form.waktu_kembali" type="datetime-local" :min="form.waktu_keluar || undefined" required :disabled="busy"></label>
      </div>
      <label>Alasan<textarea v-model="form.alasan" required maxlength="2000" rows="3" :disabled="busy" class="w-full rounded-lg border border-slate-200 bg-white p-3 text-sm outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-100 dark:border-slate-700 dark:bg-slate-800 dark:focus:ring-orange-900/50" /></label>
      <div class="flex gap-3"><button class="btn" :disabled="busy">{{ busy ? 'Memproses...' : editing ? 'Simpan perubahan izin' : 'Ajukan izin' }}</button><button v-if="editing" type="button" class="muted text-sm" :disabled="busy" @click="reset">Batal edit</button></div>
    </form>
    <form class="my-6 flex gap-3" @submit.prevent="find"><input v-model="search" aria-label="Cari izin keluar" placeholder="Cari izin keluar..." maxlength="100"><button class="btn" :disabled="status === 'pending'">Cari</button><button v-if="search" type="button" class="muted text-xs" @click="search = ''; find()">Reset</button></form>
    <p v-if="status === 'pending'" role="status" class="muted mb-3 text-sm">Memuat izin keluar...</p>
    <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs text-slate-500 dark:bg-slate-800 dark:text-slate-400"><tr>
      <SortableHeader v-if="admin" column="user_name" label="Nama user" :sort="parameters.sort" :direction="parameters.direction" :loading="status === 'pending'" @sort="sortBy" />
      <SortableHeader column="waktu_keluar" label="Waktu keluar" :sort="parameters.sort" :direction="parameters.direction" :loading="status === 'pending'" @sort="sortBy" />
      <SortableHeader column="waktu_kembali" label="Waktu kembali" :sort="parameters.sort" :direction="parameters.direction" :loading="status === 'pending'" @sort="sortBy" />
      <SortableHeader column="alasan" label="Alasan" :sort="parameters.sort" :direction="parameters.direction" :loading="status === 'pending'" @sort="sortBy" />
      <SortableHeader column="status" label="Status" :sort="parameters.sort" :direction="parameters.direction" :loading="status === 'pending'" @sort="sortBy" /><th class="p-3">Aksi</th>
    </tr></thead><tbody>
      <tr v-for="row in data?.records.data" :key="row.id" class="border-b border-slate-100 dark:border-slate-800">
        <td v-if="admin" class="p-3">{{ row.user_name }}</td><td class="whitespace-nowrap p-3">{{ displayTime(row.waktu_keluar) }}</td><td class="whitespace-nowrap p-3">{{ displayTime(row.waktu_kembali) }}</td><td class="min-w-48 whitespace-pre-wrap break-words p-3">{{ row.alasan }}</td>
        <td class="p-3"><span class="rounded-full px-3 py-1 text-xs font-semibold" :class="row.status === 'Diajukan' ? 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300' : row.status === 'Disetujui' ? 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300'">{{ row.status }}</span></td>
        <td class="p-3"><div v-if="row.status === 'Diajukan'" class="flex gap-3"><template v-if="admin"><button class="btn" :disabled="busy" @click="review(row.id, 'Disetujui')">Setujui</button><button class="text-red-600 dark:text-red-400" :disabled="busy" @click="review(row.id, 'Ditolak')">Tolak</button></template><button v-else class="text-orange-600 dark:text-orange-400" :disabled="busy" @click="edit(row)">Edit</button></div><span v-else class="muted text-xs">Sudah diproses</span></td>
      </tr>
      <tr v-if="data && !data.records.data.length"><td :colspan="admin ? 6 : 5" class="muted py-8 text-center">Belum ada pengajuan izin keluar.</td></tr>
    </tbody></table></div>
    <TablePagination v-if="data" v-model:page="page" v-model:per-page="perPage" :current="data.records.current_page" :last="data.records.last_page" :loading="status === 'pending'" />
  </section>
</template>
