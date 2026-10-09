<script setup lang="ts">
type Resident = { id: number; name: string; angkatan: string | null; gender: string | null; agama: string | null; kamar_id: number | null }
type Room = { id: number; name: string; lantai: number; status: string; penghuni_count: number; checked_in_count: number; penghuni: { id: number; name: string; checked_in_at: string | null }[] }
type RoomsData = { monitoring: Record<string, number>; counts: { kamar: number }; records: { data: Room[]; current_page: number; last_page: number }; allocation_users: Resident[] }
type AllocationResult = { message: string; allocated: { name: string; kamar: string; lantai: number }[]; waiting: { name: string }[]; incomplete: { name: string }[] }
definePageMeta({ role: 'admin' })
useHead({ title: 'Kamar · Gojags Asrama' })
const { mutate, errorMessage } = useAsrama()
const { search, query, page, perPage, parameters, find, sortBy } = useTableSearch()
const { data, status, error: loadError, refresh } = await useFetch<RoomsData>('/backend/api/asrama/kamar', { query: parameters })
const profiles = ref<Resident[]>([])
watch(data, value => {
  profiles.value = (value?.allocation_users ?? []).map(user => {
    const existing = profiles.value.find(profile => profile.id === user.id)
    return existing && existing.kamar_id === user.kamar_id ? existing : { ...user }
  })
}, { immediate: true })
const busy = ref(false)
const error = ref('')
const success = ref('')
const result = ref<AllocationResult | null>(null)
let poll: ReturnType<typeof setInterval> | undefined
onMounted(() => { poll = setInterval(() => { if (!document.hidden && !busy.value && status.value !== 'pending') refresh() }, 15000) })
onBeforeUnmount(() => clearInterval(poll))
function checkInTime(value: string) {
  return new Intl.DateTimeFormat('id-ID', { dateStyle: 'short', timeStyle: 'medium', timeZone: 'Asia/Jakarta' }).format(new Date(value.replace(' ', 'T') + 'Z')) + ' WIB'
}
async function saveProfile(user: Resident) {
  busy.value = true; error.value = ''; success.value = ''; result.value = null
  try {
    await mutate(`allocation-users/${user.id}`, { angkatan: user.angkatan, gender: user.gender, agama: user.agama }, 'PATCH')
    success.value = `Profil ${user.name} berhasil disimpan.`
    // Preserve unsaved edits in other profiles.
  } catch (e) { error.value = errorMessage(e) }
  finally { busy.value = false }
}
async function allocate() {
  busy.value = true; error.value = ''; success.value = ''; result.value = null
  try {
    result.value = await mutate('kamar/allocate') as AllocationResult
    success.value = result.value.message
    await refresh()
  } catch (e) { error.value = errorMessage(e) }
  finally { busy.value = false }
}
</script>

<template>
  <div>
    <div class="mb-7"><p class="overline">LAYANAN ASRAMA</p><h1 class="mt-3 text-3xl font-bold">Kamar</h1><p class="muted mt-2">{{ data?.counts.kamar ?? 78 }} kamar di lantai 3, 4, dan 5. Kapasitas dua penghuni per kamar.</p></div>
    <p v-if="error || loadError" role="alert" class="mb-5 rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-950/50 dark:text-red-300">{{ error || 'Data kamar tidak dapat dimuat.' }} <button v-if="loadError" class="underline" @click="refresh()">Coba lagi</button></p>
    <p v-if="success" role="status" class="mb-5 rounded-lg bg-green-50 p-4 text-sm text-green-700 dark:bg-green-950/50 dark:text-green-300">{{ success }}</p>
    <section v-if="data?.monitoring" aria-label="Monitoring kamar" class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4"><div v-for="(total, state) in data.monitoring" :key="state" class="card p-5"><RoomStatusBadge :status="String(state)" /><p class="mt-4 text-3xl font-bold">{{ total }}</p><p class="muted mt-1 text-xs">kamar</p></div></section>
    <section class="card mb-6 p-5">
      <h2 class="font-semibold">Alokasi kamar otomatis</h2>
      <p class="muted mt-2 text-sm">Angkatan yang sama diprioritaskan pada satu lantai, mulai lantai 5 lalu turun ke 4 dan 3. Teman sekamar harus memiliki gender dan agama yang sama. Penghuni yang sudah dialokasikan tetap di kamarnya.</p>
      <p class="muted mt-2 text-sm">Simpan profil user terlebih dahulu. Profil belum lengkap atau kamar yang tidak sesuai akan dilaporkan setelah alokasi.</p>
      <button class="btn mt-4" :disabled="busy || !!loadError" @click="allocate">{{ busy ? 'Memproses…' : 'Alokasikan kamar otomatis' }}</button>
      <div v-if="result" class="mt-4 space-y-3 text-sm">
        <p v-if="result.incomplete.length"><b>Profil belum lengkap:</b> {{ result.incomplete.map(user => user.name).join(', ') }}</p>
        <p v-if="result.waiting.length"><b>Menunggu kamar yang sesuai:</b> {{ result.waiting.map(user => user.name).join(', ') }}</p>
        <details v-if="result.allocated.length"><summary>Hasil penempatan ({{ result.allocated.length }})</summary><ul class="mt-2 list-inside list-disc"><li v-for="user in result.allocated" :key="user.name + user.kamar">{{ user.name }} — kamar {{ user.kamar }}, lantai {{ user.lantai }}</li></ul></details>
      </div>
    </section>
    <details class="card mb-6 p-5">
      <summary class="font-semibold text-orange-600 dark:text-orange-400">Profil user untuk alokasi ({{ profiles.length }})</summary>
      <p class="muted mt-3 text-sm">Isi angkatan, gender, dan agama setiap user. Profil penghuni yang telah dialokasikan dikunci.</p>
      <p v-if="!profiles.length" class="muted mt-4 text-sm">Belum ada akun user.</p>
      <form v-for="user in profiles" :key="user.id" class="mt-5 border-t border-slate-200 pt-5 dark:border-slate-700" @submit.prevent="saveProfile(user)">
        <h3 class="mb-3 text-sm font-semibold">{{ user.name }} <span v-if="user.kamar_id" class="muted font-normal">· Sudah dialokasikan</span></h3>
        <div class="grid gap-4 sm:grid-cols-3">
          <label>Angkatan<input v-model="user.angkatan" :aria-label="`Angkatan ${user.name}`" :disabled="busy || !!user.kamar_id" required maxlength="100"></label>
          <label>Gender<select v-model="user.gender" :aria-label="`Gender ${user.name}`" :disabled="busy || !!user.kamar_id" required><option :value="null" disabled>Pilih gender</option><option>Laki-laki</option><option>Perempuan</option></select></label>
          <label>Agama<input v-model="user.agama" :aria-label="`Agama ${user.name}`" :disabled="busy || !!user.kamar_id" required maxlength="50" list="agama-options"></label>
        </div>
        <button v-if="!user.kamar_id" class="btn mt-3" :disabled="busy">Simpan profil {{ user.name }}</button>
      </form>
      <datalist id="agama-options"><option>Islam</option><option>Kristen</option><option>Katolik</option><option>Hindu</option><option>Buddha</option><option>Konghucu</option></datalist>
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
    <LeavePermissions admin />
    <RoomComplaints admin />
  </div>
</template>
