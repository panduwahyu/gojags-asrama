<script setup lang="ts">
type Menu = { id: number; menu: number | null; name: string; detail: string; penyedia: string | null; waktu_catering: string | null; rating_average: number | null; rating_count: number; my_rating: number | null }
const props = defineProps<{ admin?: boolean }>()
const { mutate, errorMessage } = useAsrama()
const { search, query, page, perPage, parameters, find, sortBy } = useTableSearch()
const endpoint = props.admin ? 'catering' : 'user/catering'
const { data, status, error: loadError, refresh } = await useFetch<{ records: { data: Menu[]; current_page: number; last_page: number } }>(`/backend/api/asrama/${endpoint}`, { query: parameters })
const form = reactive({ menu: '', penyedia: '', waktu_catering: '' })
const ratings = reactive<Record<number, number>>({})
watch(data, value => {
  for (const row of value?.records.data ?? []) if (ratings[row.id] === undefined) ratings[row.id] = row.my_rating ?? 0
}, { immediate: true })
const busy = ref(false)
const error = ref('')
const success = ref('')
const columns = computed(() => [
  { key: 'menu', label: 'Menu' }, { key: 'penyedia', label: 'Penyedia' }, { key: 'waktu_catering', label: 'Waktu catering' },
  { key: 'rating_average', label: 'Rata-rata rating' }, { key: 'rating_count', label: 'Jumlah penilai' },
  ...(!props.admin ? [{ key: 'my_rating', label: 'Rating saya' }] : []),
])
function time(value: string | null) {
  return value ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short', timeZone: 'Asia/Jakarta' }).format(new Date(value)) + ' WIB' : 'Belum diatur'
}
async function create() {
  busy.value = true; error.value = ''; success.value = ''
  try {
    const response = await mutate('catering', { ...form }) as { message: string }
    Object.assign(form, { menu: '', penyedia: '', waktu_catering: '' })
    success.value = response.message; page.value = 1
    await refresh()
  } catch (e) { error.value = errorMessage(e) }
  finally { busy.value = false }
}
async function rate(id: number) {
  busy.value = true; error.value = ''; success.value = ''
  try {
    const response = await mutate(`user/catering/${id}/rating`, { rating: ratings[id] }) as { message: string }
    success.value = response.message
    await refresh()
  } catch (e) { error.value = errorMessage(e) }
  finally { busy.value = false }
}
let poll: ReturnType<typeof setInterval> | undefined
onMounted(() => { poll = setInterval(() => { if (!document.hidden && !busy.value && status.value !== 'pending') refresh() }, 15000) })
onBeforeUnmount(() => clearInterval(poll))
</script>

<template>
  <div>
    <p class="overline">LAYANAN ASRAMA</p><h1 class="mb-6 mt-3 text-3xl font-bold">Catering</h1>
    <p v-if="error || loadError" role="alert" class="mb-5 rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-950/50 dark:text-red-300">{{ error || 'Data catering tidak dapat dimuat.' }} <button v-if="loadError" class="underline" @click="refresh()">Coba lagi</button></p>
    <p v-if="success" role="status" class="mb-5 rounded-lg bg-green-50 p-4 text-sm text-green-700 dark:bg-green-950/50 dark:text-green-300">{{ success }}</p>
    <section v-if="admin" class="card mb-6 p-6"><h2 class="text-xl font-semibold">Tambah menu catering</h2>
      <form class="mt-5 space-y-4" @submit.prevent="create"><div class="grid gap-4 sm:grid-cols-3">
        <label>Nomor menu<input v-model="form.menu" type="number" min="1" max="10000" step="1" required :disabled="busy"></label>
        <label>Penyedia<input v-model="form.penyedia" maxlength="255" required :disabled="busy"></label>
        <label>Waktu catering (WIB)<input v-model="form.waktu_catering" type="datetime-local" required :disabled="busy"></label>
      </div><button class="btn" :disabled="busy">{{ busy ? 'Memproses...' : 'Tambah menu' }}</button></form>
    </section>
    <section aria-label="Menu catering" class="card p-4 md:p-6"><h2 class="text-xl font-semibold">Menu dan rating catering</h2><p class="muted mt-2 text-sm">Rating menggunakan skala 1–5 bintang. Hasil menampilkan rata-rata dan jumlah penilai.</p>
      <form class="my-6 flex gap-3" @submit.prevent="find"><input v-model="search" aria-label="Cari catering" placeholder="Cari menu atau penyedia..."><button class="btn" :disabled="status === 'pending'">Cari</button><button v-if="query" type="button" class="muted text-xs" @click="search = ''; find()">Reset</button></form>
      <p v-if="status === 'pending'" role="status" class="muted mb-4 text-sm">Memuat catering...</p>
      <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs text-slate-500 dark:bg-slate-800 dark:text-slate-400"><tr>
        <SortableHeader v-for="column in columns" :key="column.key" :column="column.key" :label="column.label" :sort="parameters.sort" :direction="parameters.direction" :loading="status === 'pending'" @sort="sortBy" /><th v-if="!admin" class="p-3">Beri rating</th>
      </tr></thead><tbody>
        <tr v-for="row in data?.records.data" :key="row.id" class="border-b border-slate-100 dark:border-slate-800">
          <td class="whitespace-nowrap p-3 font-semibold">{{ row.menu ? `Menu ${row.menu}` : row.name }}</td><td class="p-3">{{ row.penyedia ?? row.detail }}</td><td class="whitespace-nowrap p-3">{{ time(row.waktu_catering) }}</td>
          <td class="whitespace-nowrap p-3"><span v-if="row.rating_average !== null" class="text-orange-600 dark:text-orange-400">{{ row.rating_average.toLocaleString('id-ID', { maximumFractionDigits: 2 }) }} / 5 ★</span><span v-else class="muted">Belum ada rating</span></td><td class="p-3">{{ row.rating_count }}</td>
          <template v-if="!admin"><td class="whitespace-nowrap p-3">{{ row.my_rating ? `${row.my_rating} / 5 ★` : 'Belum menilai' }}</td><td class="min-w-40 p-3"><form class="space-y-3" @submit.prevent="rate(row.id)"><label :for="`rating-${row.id}`">Rating untuk {{ row.name }}</label><select :id="`rating-${row.id}`" v-model.number="ratings[row.id]" :disabled="busy" required><option :value="0" disabled>Pilih rating</option><option v-for="score in 5" :key="score" :value="score">{{ score }} ★</option></select><button class="btn" :disabled="busy || !ratings[row.id]">Simpan rating</button></form></td></template>
        </tr>
        <tr v-if="data && !data.records.data.length"><td :colspan="admin ? 5 : 7" class="muted py-10 text-center">Belum ada menu catering.</td></tr>
      </tbody></table></div>
      <TablePagination v-if="data" v-model:page="page" v-model:per-page="perPage" :current="data.records.current_page" :last="data.records.last_page" :loading="status === 'pending'" />
    </section>
  </div>
</template>
