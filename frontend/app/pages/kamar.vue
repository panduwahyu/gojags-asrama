<script setup lang="ts">
type Resident = { id: number; name: string; angkatan: string | null; gender: string | null; agama: string | null; kamar_id: number | null; batch_id: number | null }
type Room = { id: number; name: string; lantai: number; status: string; penghuni_count: number; checked_in_count: number; penghuni: { id: number; name: string; checked_in_at: string | null }[] }
type TrainingBatch = { id: number; name: string; participants_count: number; synced_at: string }
type RoomsData = { batches: TrainingBatch[]; monitoring: Record<string, number>; counts: { kamar: number }; records: { data: Room[]; current_page: number; last_page: number }; allocation_users: Resident[] }
type AllocationResult = { message: string; allocated: { name: string; kamar: string; lantai: number }[]; waiting: { name: string }[]; incomplete: { name: string }[] }
definePageMeta({ role: 'admin' })
useHead({ title: 'Kamar · Gojags Asrama' })
const route = useRoute()
const section = computed(() => ['perizinan', 'keluhan'].includes(String(route.query.section)) ? String(route.query.section) : 'alokasi')
const { mutate, errorMessage } = useAsrama()
const { search, query, page, perPage, parameters, find, sortBy } = useTableSearch()
const { data, status, error: loadError, refresh } = await useFetch<RoomsData>('/backend/api/asrama/kamar', { query: parameters })
const profiles = computed(() => data.value?.allocation_users ?? [])
const selectedBatch = ref<number | null>(null)
const syncing = ref(false)
const selectedBatchData = computed(() => data.value?.batches.find(batch => batch.id === selectedBatch.value))
watch(data, value => { if (!value?.batches.some(batch => batch.id === selectedBatch.value)) selectedBatch.value = null })
const busy = ref(false)
const error = ref('')
const success = ref('')
const result = ref<AllocationResult | null>(null)
let poll: ReturnType<typeof setInterval> | undefined
onMounted(() => { poll = setInterval(() => { if (!document.hidden && !syncing.value && !busy.value && status.value !== 'pending') refresh() }, 15000) })
onBeforeUnmount(() => clearInterval(poll))
function checkInTime(value: string) {
  return new Intl.DateTimeFormat('id-ID', { dateStyle: 'short', timeStyle: 'medium', timeZone: 'Asia/Jakarta' }).format(new Date(value.replace(' ', 'T') + 'Z')) + ' WIB'
}
async function syncParticipants() {
  syncing.value = true; error.value = ''; success.value = ''; result.value = null
  try {
    const response = await mutate('kamar/sync') as { message: string }
    success.value = response.message
    await refresh()
  } catch (e) { error.value = errorMessage(e) }
  finally { syncing.value = false }
}
async function allocate() {
  busy.value = true; error.value = ''; success.value = ''; result.value = null
  try {
    result.value = await mutate('kamar/allocate', { batch_id: selectedBatch.value }) as AllocationResult
    success.value = result.value.message
    await refresh()
  } catch (e) { error.value = errorMessage(e) }
  finally { busy.value = false }
}
</script>

<template>
  <div>
    <template v-if="section === 'alokasi'">
    <div class="mb-7"><p class="overline">LAYANAN ASRAMA</p><h1 class="mt-3 text-3xl font-bold">Kamar</h1><p class="muted mt-2">{{ data?.counts.kamar ?? 78 }} kamar di lantai 3, 4, dan 5. Kapasitas dua penghuni per kamar.</p></div>
    <FeedbackNotice :message="loadError ? 'Data tidak dapat dimuat. Coba lagi atau muat ulang halaman.' : ''" /><button v-if="loadError" class="muted mb-4 underline" @click="refresh()">Coba lagi</button>

    <section v-if="data?.monitoring" aria-label="Monitoring kamar" class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4"><div v-for="(total, state) in data.monitoring" :key="state" class="card p-5"><RoomStatusBadge :status="String(state)" /><p class="mt-4 text-3xl font-bold">{{ total }}</p><p class="muted mt-1 text-xs">kamar</p></div></section>
    <section class="card mb-6 p-5">
      <h2 class="font-semibold">Alokasi kamar otomatis</h2>
      <p class="muted mt-2 text-sm">Angkatan yang sama diprioritaskan pada satu lantai, mulai lantai 5 lalu turun ke 4 dan 3. Teman sekamar harus memiliki gender dan agama yang sama. Penghuni yang sudah dialokasikan tetap di kamarnya.</p>
      <p class="muted mt-2 text-sm">Sinkronkan data dari api-gojags, lalu pilih batch peserta yang akan dialokasikan. Peserta batch lain tidak ikut diproses.</p>
      <button class="btn mt-4" :disabled="syncing || busy" @click="syncParticipants">{{ syncing ? 'Menyinkronkan...' : 'Sinkronisasi data peserta' }}</button>
      <label class="mt-5 block text-sm font-semibold">Batch peserta diklat
        <select v-model="selectedBatch" class="mt-2" :disabled="syncing || busy || !data?.batches.length">
          <option :value="null" disabled>Pilih batch peserta</option>
          <option v-for="batch in data?.batches" :key="batch.id" :value="batch.id" :disabled="!Number(batch.participants_count)">{{ batch.name }} ({{ batch.participants_count }} peserta)</option>
        </select>
      </label>
      <p v-if="!data?.batches.length" class="muted mt-2 text-sm">Belum ada batch. Klik Sinkronisasi data peserta terlebih dahulu.</p>
      <p v-if="selectedBatchData" class="muted mt-2 text-sm">{{ selectedBatchData.participants_count }} peserta | Sinkronisasi terakhir: {{ selectedBatchData.synced_at }}</p>
      <button class="btn mt-4" :disabled="busy || syncing || !!loadError || !selectedBatch || !Number(selectedBatchData?.participants_count)" @click="allocate">{{ busy ? 'Memproses...' : 'Alokasikan kamar otomatis' }}</button>
      <div v-if="result" class="mt-4 space-y-3 text-sm">
        <p v-if="result.incomplete.length"><b>Profil belum lengkap:</b> {{ result.incomplete.map(user => user.name).join(', ') }}</p>
        <p v-if="result.waiting.length"><b>Menunggu kamar yang sesuai:</b> {{ result.waiting.map(user => user.name).join(', ') }}</p>
        <details v-if="result.allocated.length"><summary>Hasil penempatan ({{ result.allocated.length }})</summary><ul class="mt-2 list-inside list-disc"><li v-for="user in result.allocated" :key="user.name + user.kamar">{{ user.name }} — kamar {{ user.kamar }}, lantai {{ user.lantai }}</li></ul></details>
      </div>
    </section>
    <BatchRoomQr :batch-id="selectedBatch" :disabled="busy || syncing || !Number(selectedBatchData?.participants_count)" />
    <details v-if="selectedBatch" class="card mb-6 p-5">
      <summary class="font-semibold text-orange-600 dark:text-orange-400">Peserta batch terpilih ({{ profiles.filter(user => user.batch_id === selectedBatch).length }})</summary>
      <div class="mt-4 overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr><th class="p-2">Nama</th><th class="p-2">Angkatan</th><th class="p-2">Gender</th><th class="p-2">Agama</th><th class="p-2">Status alokasi</th></tr></thead><tbody><tr v-for="user in profiles.filter(user => user.batch_id === selectedBatch)" :key="user.id"><td class="p-2">{{ user.name }}</td><td class="p-2">{{ user.angkatan }}</td><td class="p-2">{{ user.gender }}</td><td class="p-2">{{ user.agama }}</td><td class="p-2">{{ user.kamar_id ? 'Sudah dialokasikan' : 'Belum dialokasikan' }}</td></tr></tbody></table></div>
    </details>
    <section class="card p-4 md:p-6">
      <form class="mb-6 flex gap-3" @submit.prevent="find"><input v-model="search" placeholder="Cari nomor kamar atau lantai…" aria-label="Cari kamar"><button class="btn" :disabled="status === 'pending'">Cari</button><button v-if="query" type="button" class="muted text-xs" @click="search = ''; find()">Reset</button></form>
      <p v-if="status === 'pending'" role="status" class="muted py-10 text-center">Memuat kamar…</p>
      <div v-if="data" class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs text-slate-500 dark:bg-slate-800 dark:text-slate-400"><tr><SortableHeader column="name" label="Kamar" :sort="parameters.sort" :direction="parameters.direction" :loading="status === 'pending'" @sort="sortBy" /><SortableHeader column="lantai" label="Lantai" :sort="parameters.sort" :direction="parameters.direction" :loading="status === 'pending'" @sort="sortBy" /><SortableHeader column="penghuni" label="Penghuni" :sort="parameters.sort" :direction="parameters.direction" :loading="status === 'pending'" @sort="sortBy" /><SortableHeader column="penghuni_count" label="Kapasitas" :sort="parameters.sort" :direction="parameters.direction" :loading="status === 'pending'" @sort="sortBy" /><SortableHeader column="status" label="Status" :sort="parameters.sort" :direction="parameters.direction" :loading="status === 'pending'" @sort="sortBy" /></tr></thead><tbody>
        <tr v-for="room in data?.records.data" :key="room.id" class="border-b border-slate-100 dark:border-slate-800"><td class="p-3 font-semibold">{{ room.name }}</td><td class="p-3">{{ room.lantai }}</td><td class="p-3"><div v-for="resident in room.penghuni" :key="resident.id" class="resident-name">{{ resident.name }}<small class="muted mt-1 block text-xs">{{ resident.checked_in_at ? `Check-in: ${checkInTime(resident.checked_in_at)}` : 'Belum check-in' }}</small></div><span v-if="!room.penghuni.length">Belum ada penghuni</span></td><td class="p-3">{{ room.penghuni_count }} / 2</td><td class="p-3"><RoomStatusBadge :status="room.status" /></td></tr>
        <tr v-if="!data?.records.data.length"><td colspan="5" class="muted py-10 text-center">Kamar tidak ditemukan.</td></tr>
      </tbody></table></div>
      <TablePagination v-if="data" v-model:page="page" v-model:per-page="perPage" :current="data.records.current_page" :last="data.records.last_page" :loading="status === 'pending'" />
    </section>
    </template>
    <template v-if="section === 'perizinan'"><h1 class="mb-6 text-3xl font-bold">Perizinan</h1><LeavePermissions admin /></template>
    <template v-if="section === 'keluhan'"><h1 class="mb-6 text-3xl font-bold">Keluhan Asrama</h1><RoomComplaints admin /></template>
  </div>
</template>
