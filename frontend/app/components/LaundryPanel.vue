<script setup lang="ts">
type Laundry = { id: number; name: string; detail: string; jumlah_stel: number | null; date: string; status: string; biaya: string | null }
type Draft = { status: string; biaya: string }
const props = defineProps<{ admin?: boolean }>()
const { mutate, errorMessage } = useAsrama()
const endpoint = props.admin ? 'laundry' : 'user/laundry'
const { search, query, page, perPage, parameters, find, sortBy } = useTableSearch()
const { data, status, error: loadError, refresh } = await useFetch<{ records: { data: Laundry[]; current_page: number; last_page: number } }>(`/backend/api/asrama/${endpoint}`, { query: parameters })
const form = reactive({ detail: '', jumlah_stel: '' })
const drafts = reactive<Record<number, Draft>>({})
watch(data, value => {
  for (const row of value?.records.data ?? []) {
    if (!drafts[row.id]) drafts[row.id] = { status: row.status === 'Selesai' ? 'Selesai' : 'Diajukan', biaya: row.biaya ?? '' }
  }
}, { immediate: true })
const busy = ref(false)
const error = ref('')
const success = ref('')
function rupiah(value: string | null) {
  return value === null ? 'Belum ditentukan' : new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(Number(value))
}
async function submit() {
  busy.value = true; error.value = ''; success.value = ''
  try {
    const response = await mutate(endpoint, { ...form }) as { message: string }
    form.detail = ''; form.jumlah_stel = ''; success.value = response.message
    page.value = 1
    await refresh()
  } catch (e) { error.value = errorMessage(e) }
  finally { busy.value = false }
}
async function update(id: number) {
  busy.value = true; error.value = ''; success.value = ''
  try {
    const response = await mutate(`laundry/${id}`, { ...drafts[id] }, 'PATCH') as { message: string }
    success.value = response.message
    await refresh()
  } catch (e) { error.value = errorMessage(e) }
  finally { busy.value = false }
}
let poll: ReturnType<typeof setInterval> | undefined
onMounted(() => { if (!props.admin) poll = setInterval(() => { if (!document.hidden && !busy.value && status.value !== 'pending') refresh() }, 15000) })
onBeforeUnmount(() => clearInterval(poll))
</script>

<template>
  <div>
    <p class="overline">LAYANAN ASRAMA</p><h1 class="mb-6 mt-3 text-3xl font-bold">Laundry</h1>
    <FeedbackNotice :message="loadError ? 'Data tidak dapat dimuat. Coba lagi atau muat ulang halaman.' : ''" /><button v-if="loadError" class="muted mb-4 underline" @click="refresh()">Coba lagi</button>

    <section v-if="!admin" class="card mb-6 p-6"><h2 class="text-xl font-semibold">Ajukan laundry</h2><p class="muted mt-2 text-sm">Tuliskan pakaian atau barang yang akan dilaundry dan total jumlah stel.</p>
      <form class="mt-5 space-y-4" @submit.prevent="submit">
        <label>Pakaian / barang yang dilaundry<textarea v-model="form.detail" required maxlength="255" rows="3" :disabled="busy" placeholder="Contoh: kemeja, celana, dan seragam" class="rounded-lg border border-slate-200 bg-white p-3 outline-none focus:border-orange-400 dark:border-slate-700 dark:bg-slate-800" /></label>
        <label>Jumlah stel<input v-model="form.jumlah_stel" type="number" min="1" max="1000" step="1" required :disabled="busy"></label>
        <button class="btn" :disabled="busy">{{ busy ? 'Memproses...' : 'Ajukan laundry' }}</button>
      </form>
    </section>
    <section aria-label="Pengajuan laundry" class="card p-4 md:p-6"><h2 class="mb-5 text-xl font-semibold">{{ admin ? 'Pengajuan laundry user' : 'Riwayat pengajuan laundry' }}</h2>
      <form class="mb-6 flex gap-3" @submit.prevent="find"><input v-model="search" aria-label="Cari laundry" placeholder="Cari laundry..."><button class="btn" :disabled="status === 'pending'">Cari</button><button v-if="query" type="button" class="muted text-xs" @click="search = ''; find()">Reset</button></form>
      <p v-if="status === 'pending'" role="status" class="muted mb-4 text-sm">Memuat laundry...</p>
      <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs text-slate-500 dark:bg-slate-800 dark:text-slate-400"><tr>
        <SortableHeader v-if="admin" column="name" label="Nama user" :sort="parameters.sort" :direction="parameters.direction" :loading="status === 'pending'" @sort="sortBy" />
        <SortableHeader v-for="column in [{ key: 'detail', label: 'Pakaian / barang' }, { key: 'jumlah_stel', label: 'Jumlah stel' }, { key: 'date', label: 'Tanggal' }, { key: 'status', label: 'Status' }, { key: 'biaya', label: 'Biaya laundry' }]" :key="column.key" :column="column.key" :label="column.label" :sort="parameters.sort" :direction="parameters.direction" :loading="status === 'pending'" @sort="sortBy" />
        <th v-if="admin" class="p-3">Perbarui status</th>
      </tr></thead><tbody>
        <tr v-for="row in data?.records.data" :key="row.id" class="border-b border-slate-100 dark:border-slate-800">
          <td v-if="admin" class="p-3">{{ row.name }}</td><td class="min-w-40 whitespace-pre-wrap break-words p-3">{{ row.detail }}</td><td class="p-3">{{ row.jumlah_stel ?? '-' }}</td><td class="whitespace-nowrap p-3">{{ row.date }}</td>
          <td class="p-3"><span class="rounded-full px-3 py-1 text-xs font-semibold" :class="row.status === 'Selesai' ? 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300' : 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300'">{{ row.status }}</span></td>
          <td class="whitespace-nowrap p-3">{{ rupiah(row.biaya) }}</td>
          <td v-if="admin" class="min-w-52 p-3"><form class="space-y-3" @submit.prevent="update(row.id)"><label>Status<select v-model="drafts[row.id]!.status" :disabled="busy" :aria-label="`Status ${row.name}`"><option>Diajukan</option><option>Selesai</option></select></label><label>Biaya laundry (Rp)<input v-model="drafts[row.id]!.biaya" type="number" min="0" max="9999999999.99" step="0.01" required :disabled="busy" :aria-label="`Biaya laundry ${row.name}`"></label><button class="btn" :disabled="busy">Simpan</button></form></td>
        </tr>
        <tr v-if="data && !data.records.data.length"><td :colspan="admin ? 7 : 5" class="muted py-10 text-center">Belum ada pengajuan laundry.</td></tr>
      </tbody></table></div>
      <TablePagination v-if="data" v-model:page="page" v-model:per-page="perPage" :current="data.records.current_page" :last="data.records.last_page" :loading="status === 'pending'" />
    </section>
  </div>
</template>
