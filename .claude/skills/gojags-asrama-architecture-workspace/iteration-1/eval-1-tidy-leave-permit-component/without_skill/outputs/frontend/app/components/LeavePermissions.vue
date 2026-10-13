<script setup lang="ts">
type PermissionStatus = 'Diajukan' | 'Disetujui' | 'Ditolak'
type Decision = Exclude<PermissionStatus, 'Diajukan'>

type Permission = {
  id: number
  user_name: string
  waktu_keluar: string
  waktu_kembali: string
  alasan: string
  status: PermissionStatus
}

type Form = {
  waktu_keluar: string
  waktu_kembali: string
  alasan: string
}

type PermissionPage = {
  records: {
    data: Permission[]
    current_page: number
    last_page: number
  }
}

type MutationResponse = { message: string }

const TIME_ZONE = 'Asia/Jakarta'
const SEARCH_DEBOUNCE_MS = 300
const POLL_INTERVAL_MS = 15000

const STATUS_CLASSES: Record<PermissionStatus, string> = {
  Diajukan: 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300',
  Disetujui: 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300',
  Ditolak: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300',
}

// Locale sv-SE menghasilkan bagian tanggal berformat ISO (YYYY-MM-DD HH:mm).
const inputTimeFormat = new Intl.DateTimeFormat('sv-SE', {
  timeZone: TIME_ZONE,
  year: 'numeric',
  month: '2-digit',
  day: '2-digit',
  hour: '2-digit',
  minute: '2-digit',
  hourCycle: 'h23',
})

const displayTimeFormat = new Intl.DateTimeFormat('id-ID', {
  dateStyle: 'medium',
  timeStyle: 'short',
  timeZone: TIME_ZONE,
})

const props = defineProps<{ admin?: boolean }>()

const endpoint = props.admin ? 'izin-keluar' : 'user/izin-keluar'
const { mutate, errorMessage } = useAsrama()

// ---------------------------------------------------------------------------
// Tabel: pencarian, pengurutan, paginasi
// ---------------------------------------------------------------------------

const parameters = ref({ q: '', page: 1, per_page: 10, sort: 'id', direction: 'desc' })
const search = ref('')

const page = computed({
  get: () => parameters.value.page,
  set: (value: number) => {
    parameters.value = { ...parameters.value, page: value }
  },
})

const perPage = computed({
  get: () => parameters.value.per_page,
  set: (value: number) => {
    parameters.value = { ...parameters.value, per_page: value, page: 1 }
  },
})

const { data, status, error: loadError, refresh } = await useFetch<PermissionPage>(
  `/backend/api/asrama/${endpoint}`,
  { query: parameters },
)

const loading = computed(() => status.value === 'pending')

const columns = computed(() => [
  ...(props.admin ? [{ column: 'user_name', label: 'Nama user' }] : []),
  { column: 'waktu_keluar', label: 'Waktu keluar' },
  { column: 'waktu_kembali', label: 'Waktu kembali' },
  { column: 'alasan', label: 'Alasan' },
  { column: 'status', label: 'Status' },
])

// Kolom yang dapat diurutkan + kolom "Aksi".
const columnCount = computed(() => columns.value.length + 1)

let debounce: ReturnType<typeof setTimeout> | undefined

function find() {
  clearTimeout(debounce)
  parameters.value = { ...parameters.value, q: search.value, page: 1 }
}

function resetSearch() {
  search.value = ''
  find()
}

function sortBy(sort: string) {
  clearTimeout(debounce)

  const current = parameters.value
  const direction = current.sort === sort && current.direction === 'asc' ? 'desc' : 'asc'

  parameters.value = { ...current, q: search.value, page: 1, sort, direction }
}

watch(search, () => {
  clearTimeout(debounce)
  debounce = setTimeout(find, SEARCH_DEBOUNCE_MS)
})

// ---------------------------------------------------------------------------
// Format waktu (selalu WIB)
// ---------------------------------------------------------------------------

/** Mengubah waktu dari API menjadi nilai untuk <input type="datetime-local">. */
function localTime(value: string) {
  const parts = inputTimeFormat.formatToParts(new Date(value))
  const part = (key: string) => parts.find(p => p.type === key)?.value

  return `${part('year')}-${part('month')}-${part('day')}T${part('hour')}:${part('minute')}`
}

function displayTime(value: string) {
  return `${displayTimeFormat.format(new Date(value))} WIB`
}

// ---------------------------------------------------------------------------
// Formulir pengajuan (penghuni)
// ---------------------------------------------------------------------------

const form = reactive<Form>({ waktu_keluar: '', waktu_kembali: '', alasan: '' })
const editing = ref<number | null>(null)
const formElement = ref<HTMLFormElement | null>(null)

const busy = ref(false)
const error = ref('')
const success = ref('')

const description = computed(() => props.admin
  ? 'Tinjau pengajuan izin keluar penghuni.'
  : 'Isi waktu keluar, waktu kembali, dan alasan. Pengajuan dapat diedit selama berstatus Diajukan.')

const formTitle = computed(() => editing.value ? 'Edit pengajuan izin' : 'Ajukan izin keluar')

const submitLabel = computed(() => {
  if (busy.value) return 'Memproses...'
  return editing.value ? 'Simpan perubahan izin' : 'Ajukan izin'
})

function clearMessages() {
  error.value = ''
  success.value = ''
}

function reset() {
  editing.value = null
  Object.assign(form, { waktu_keluar: '', waktu_kembali: '', alasan: '' })
}

function edit(row: Permission) {
  editing.value = row.id
  Object.assign(form, {
    waktu_keluar: localTime(row.waktu_keluar),
    waktu_kembali: localTime(row.waktu_kembali),
    alasan: row.alasan,
  })
  clearMessages()

  formElement.value?.scrollIntoView({ behavior: 'smooth', block: 'center' })
  formElement.value?.querySelector('input')?.focus({ preventScroll: true })
}

// Batalkan mode edit bila pengajuan yang sedang diedit ternyata sudah diproses admin.
watch(data, (value) => {
  const current = value?.records.data.find(row => row.id === editing.value)

  if (current && current.status !== 'Diajukan') {
    reset()
    error.value = 'Pengajuan sudah diproses admin dan tidak dapat diedit.'
  }
})

// ---------------------------------------------------------------------------
// Mutasi
// ---------------------------------------------------------------------------

/**
 * Menjalankan satu mutasi dengan penanganan busy/pesan yang seragam.
 * `onSuccess` dipanggil setelah pesan sukses dipasang dan sebelum tabel dimuat ulang.
 */
async function runMutation(request: () => Promise<unknown>, onSuccess?: () => void) {
  busy.value = true
  clearMessages()

  try {
    const response = await request() as MutationResponse
    success.value = response.message
    onSuccess?.()
    await refresh()
  } catch (e) {
    error.value = errorMessage(e)
    await refresh()
  } finally {
    busy.value = false
  }
}

function submit() {
  const path = editing.value ? `${endpoint}/${editing.value}` : endpoint
  const method = editing.value ? 'PATCH' : 'POST'

  return runMutation(() => mutate(path, { ...form }, method), () => {
    reset()
    parameters.value = { ...parameters.value, page: 1 }
  })
}

function review(id: number, decision: Decision) {
  return runMutation(() => mutate(`${endpoint}/${id}`, { status: decision }, 'PATCH'))
}

// ---------------------------------------------------------------------------
// Pembaruan berkala
// ---------------------------------------------------------------------------

let poll: ReturnType<typeof setInterval> | undefined

function refreshWhenIdle() {
  if (!document.hidden && !busy.value && status.value !== 'pending') refresh()
}

onMounted(() => {
  poll = setInterval(refreshWhenIdle, POLL_INTERVAL_MS)
})

onBeforeUnmount(() => {
  clearTimeout(debounce)
  clearInterval(poll)
})
</script>

<!--
  Catatan whitespace: teks sengaja dibiarkan menempel pada tag pembuka/penutupnya
  (mis. `>{{ row.alasan }}</td>`, `<label>Alasan<textarea`) supaya hasil render
  identik dengan versi sebelumnya; sel alasan memakai `whitespace-pre-wrap`.
-->
<template>
  <section aria-label="Izin keluar" class="card mt-6 p-4 md:p-6">
    <h2 class="text-xl font-semibold">Izin keluar</h2>
    <p class="muted mt-2 text-sm">{{ description }} Semua waktu menggunakan WIB.</p>

    <!-- Pesan -->
    <p
      v-if="error || loadError"
      role="alert"
      class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700 dark:bg-red-950/50 dark:text-red-300"
    >{{ error || 'Data izin keluar tidak dapat dimuat.' }} <button v-if="loadError" class="underline" @click="refresh()">Coba lagi</button></p>
    <p
      v-if="success"
      role="status"
      class="mt-4 rounded-lg bg-green-50 p-3 text-sm text-green-700 dark:bg-green-950/50 dark:text-green-300"
    >{{ success }}</p>

    <!-- Formulir pengajuan (penghuni) -->
    <form v-if="!admin" ref="formElement" class="mt-5 space-y-4" @submit.prevent="submit">
      <h3 class="font-semibold">{{ formTitle }}</h3>

      <div class="grid gap-4 sm:grid-cols-2">
        <label>Waktu keluar (WIB)<input
          v-model="form.waktu_keluar"
          type="datetime-local"
          required
          :disabled="busy"
        ></label>
        <label>Waktu kembali (WIB)<input
          v-model="form.waktu_kembali"
          type="datetime-local"
          :min="form.waktu_keluar || undefined"
          required
          :disabled="busy"
        ></label>
      </div>

      <label>Alasan<textarea
        v-model="form.alasan"
        required
        maxlength="2000"
        rows="3"
        :disabled="busy"
        class="w-full rounded-lg border border-slate-200 bg-white p-3 text-sm outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-100 dark:border-slate-700 dark:bg-slate-800 dark:focus:ring-orange-900/50"
      /></label>

      <div class="flex gap-3">
        <button class="btn" :disabled="busy">{{ submitLabel }}</button>
        <button v-if="editing" type="button" class="muted text-sm" :disabled="busy" @click="reset">Batal edit</button>
      </div>
    </form>

    <!-- Pencarian -->
    <form class="my-6 flex gap-3" @submit.prevent="find">
      <input v-model="search" aria-label="Cari izin keluar" placeholder="Cari izin keluar..." maxlength="100">
      <button class="btn" :disabled="loading">Cari</button>
      <button v-if="search" type="button" class="muted text-xs" @click="resetSearch">Reset</button>
    </form>

    <p v-if="loading" role="status" class="muted mb-3 text-sm">Memuat izin keluar...</p>

    <!-- Tabel -->
    <div class="overflow-x-auto">
      <table class="w-full text-left text-sm">
        <thead class="bg-slate-50 text-xs text-slate-500 dark:bg-slate-800 dark:text-slate-400">
          <tr>
            <SortableHeader
              v-for="item in columns"
              :key="item.column"
              :column="item.column"
              :label="item.label"
              :sort="parameters.sort"
              :direction="parameters.direction"
              :loading="loading"
              @sort="sortBy"
            />
            <th class="p-3">Aksi</th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="row in data?.records.data" :key="row.id" class="border-b border-slate-100 dark:border-slate-800">
            <td v-if="admin" class="p-3">{{ row.user_name }}</td>
            <td class="whitespace-nowrap p-3">{{ displayTime(row.waktu_keluar) }}</td>
            <td class="whitespace-nowrap p-3">{{ displayTime(row.waktu_kembali) }}</td>
            <td class="min-w-48 whitespace-pre-wrap break-words p-3">{{ row.alasan }}</td>
            <td class="p-3">
              <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="STATUS_CLASSES[row.status] ?? STATUS_CLASSES.Ditolak">{{ row.status }}</span>
            </td>
            <td class="p-3">
              <div v-if="row.status === 'Diajukan'" class="flex gap-3">
                <template v-if="admin">
                  <button class="btn" :disabled="busy" @click="review(row.id, 'Disetujui')">Setujui</button>
                  <button class="text-red-600 dark:text-red-400" :disabled="busy" @click="review(row.id, 'Ditolak')">Tolak</button>
                </template>
                <button v-else class="text-orange-600 dark:text-orange-400" :disabled="busy" @click="edit(row)">Edit</button>
              </div>
              <span v-else class="muted text-xs">Sudah diproses</span>
            </td>
          </tr>

          <tr v-if="data && !data.records.data.length">
            <td :colspan="columnCount" class="muted py-8 text-center">Belum ada pengajuan izin keluar.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <TablePagination
      v-if="data"
      v-model:page="page"
      v-model:per-page="perPage"
      :current="data.records.current_page"
      :last="data.records.last_page"
      :loading="loading"
    />
  </section>
</template>
