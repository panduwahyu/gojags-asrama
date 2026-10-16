<script setup lang="ts">
type Stay = { id: number; kamar: string; lantai: number; checked_in_at: string; checked_out_at: string | null }
type RoomData = { batch: { id: number; name: string } | null; room: { name: string; lantai: number; checked_in_at: string | null } | null; history: Stay[] }
definePageMeta({ role: 'user' })
useHead({ title: 'Kamar saya · Gojags Asrama' })
const route = useRoute()
const section = computed(() => ['perizinan', 'keluhan'].includes(String(route.query.section)) ? String(route.query.section) : 'alokasi')
const { mutate, errorMessage } = useAsrama()
const { data, error: loadError, refresh } = await useFetch<RoomData>('/backend/api/asrama/user/kamar')
const busy = ref(false)
const error = ref('')
const success = ref('')
function time(value: string | null) {
  return value ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'medium', timeZone: 'Asia/Jakarta' }).format(new Date(value)) + ' WIB' : 'Belum check-out'
}
async function scan(qrToken: string) {
  busy.value = true; error.value = ''; success.value = ''
  try {
    const response = await mutate('user/kamar/scan', { qr_token: qrToken }) as { message: string }
    success.value = response.message
    await refresh()
  } catch (e) { error.value = errorMessage(e) }
  finally { busy.value = false }
}
</script>

<template>
  <div>
    <template v-if="section === 'alokasi'">
    <p class="overline">LAYANAN ASRAMA</p><h1 class="mb-6 mt-3 text-3xl font-bold">Check-in/Check-out</h1>
    <FeedbackNotice :message="loadError ? 'Data tidak dapat dimuat. Coba lagi atau muat ulang halaman.' : ''" /><button v-if="loadError" class="muted mb-4 underline" @click="refresh()">Coba lagi</button>

    <section v-if="data?.room" class="card p-6">
      <h2 class="text-xl font-semibold">Kamar {{ data.room.name }}</h2><p class="muted mt-2">Lantai {{ data.room.lantai }}</p>
      <p class="mt-4 text-sm">{{ data.room.checked_in_at ? 'Sudah check-in' : 'Assigned · Menunggu check-in' }}</p>
      <p v-if="data.room.checked_in_at" class="muted mt-2 text-sm">Waktu check-in: <time :datetime="data.room.checked_in_at">{{ time(data.room.checked_in_at) }}</time></p>
      <p class="muted mt-4 text-sm">Check-out akan melepaskan alokasi kamar Anda dan menyimpan waktu keluar pada riwayat.</p>
      <p v-if="data.batch" class="muted mt-3 text-sm">{{ data.batch.name }}</p>
      <RoomQrScanner v-if="data.batch" :disabled="busy" @scanned="scan" />
      <p v-else class="mt-4 text-sm text-amber-700 dark:text-amber-400">Akun Anda belum terhubung ke batch aktif. Hubungi admin untuk sinkronisasi peserta.</p>
      <p v-if="busy" role="status" class="muted mt-3 text-sm">Memvalidasi QR dan mencatat kehadiran...</p>
    </section>
    <section v-else-if="data" class="card p-6"><h2 class="font-semibold">Belum ada alokasi kamar</h2><p class="muted mt-2 text-sm">Hubungi admin untuk penempatan kamar.</p></section>
    <section v-if="data" class="card mt-6 p-6">
      <h2 class="font-semibold">Riwayat check-in dan check-out</h2>
      <p v-if="!data.history.length" class="muted mt-4 text-sm">Belum ada riwayat.</p>
      <ul class="mt-4 space-y-4"><li v-for="stay in data.history" :key="stay.id" class="rounded-lg border border-slate-200 p-4 text-sm dark:border-slate-700"><h3 class="font-semibold">Kamar {{ stay.kamar }} · Lantai {{ stay.lantai }}</h3><p class="mt-2">Check-in: <time :datetime="stay.checked_in_at">{{ time(stay.checked_in_at) }}</time></p><p class="mt-2">Check-out: <time v-if="stay.checked_out_at" :datetime="stay.checked_out_at">{{ time(stay.checked_out_at) }}</time><span v-else>Belum check-out</span></p></li></ul>
    </section>
    </template>
    <template v-if="section === 'perizinan'"><h1 class="mb-6 text-3xl font-bold">Perizinan</h1><LeavePermissions /></template>
    <template v-if="section === 'keluhan'"><h1 class="mb-6 text-3xl font-bold">Keluhan Asrama</h1><RoomComplaints /></template>
  </div>
</template>
