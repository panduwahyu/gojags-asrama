<script setup lang="ts">
const props = defineProps<{ batchId: number | null; disabled?: boolean }>()
type Session = { batch_id: number; batch_name: string; action: string; expires_at: string; qr_image: string }
const { mutate } = useAsrama()
const action = ref('check-in')
const qr = ref<Session | null>(null)
const busy = ref(false)
const now = ref(Date.now())
let timer: ReturnType<typeof setInterval> | undefined
const expired = computed(() => !!qr.value && now.value >= Date.parse(qr.value.expires_at))
onMounted(() => { timer = setInterval(() => { now.value = Date.now() }, 1000) })
onBeforeUnmount(() => clearInterval(timer))
watch(() => props.batchId, () => { qr.value = null })
watch(action, () => { qr.value = null })
async function generate() {
  if (!props.batchId) return
  busy.value = true; qr.value = null
  try {
    const batchId = props.batchId, kind = action.value
    const result = await mutate('kamar/qr', { batch_id: batchId, action: kind }) as unknown as Session
    if (props.batchId === batchId && action.value === kind) qr.value = result
    now.value = Date.now()
  } catch { /* The shared feedback dialog displays the error. */ }
  finally { busy.value = false }
}
function expiry(value: string) { return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short', timeZone: 'Asia/Jakarta' }).format(new Date(value)) + ' WIB' }
</script>
<template>
  <section class="card mb-6 p-5">
    <h2 class="font-semibold">QR check-in / check-out angkatan</h2>
    <p class="muted mt-2 text-sm">Pilih batch pada dropdown di atas. QR berlaku 15 menit dan dapat dipakai peserta batch tersebut setelah login. QR baru menggantikan QR sebelumnya untuk aksi yang sama.</p>
    <label class="mt-4 block text-sm font-semibold">Jenis QR<select v-model="action" class="mt-2" :disabled="busy"><option value="check-in">Check-in</option><option value="check-out">Check-out</option></select></label>
    <button class="btn mt-4" :disabled="!batchId || disabled || busy" @click="generate">{{ busy ? 'Membuat QR...' : 'Generate QR angkatan' }}</button>
    <div v-if="qr" class="mt-5">
      <p class="font-semibold">{{ qr.batch_name }} - {{ qr.action }}</p>
      <img v-if="!expired" :src="qr.qr_image" :alt="`QR ${qr.action} ${qr.batch_name}`" width="320" height="320" class="mt-4 max-w-full rounded-lg bg-white p-3">
      <p role="status" class="mt-3 text-sm" :class="expired ? 'text-red-600 dark:text-red-400' : 'muted'">{{ expired ? 'QR kedaluwarsa. Generate QR baru.' : `Berlaku sampai ${expiry(qr.expires_at)}` }}</p>
    </div>
  </section>
</template>
