<script setup lang="ts">
import { startQrCamera } from '~/utils/qrCamera'
const props = defineProps<{ disabled?: boolean }>()
const emit = defineEmits<{ scanned: [token: string] }>()
const video = ref<HTMLVideoElement | null>(null)
const active = ref(false)
const message = ref('')
let controller: AbortController | undefined
function stop() { controller?.abort(); active.value = false }
async function start() {
  if (props.disabled || active.value) return
  message.value = ''; active.value = true
  controller = new AbortController()
  const current = controller
  await nextTick()
  try {
    await startQrCamera(video.value!, token => {
      stop()
      if (!/^gojags-room:v1:[a-f0-9]{64}$/.test(token)) { message.value = 'QR bukan QR check-in/check-out Gojags. Silakan scan ulang.'; return }
      emit('scanned', token)
    }, current.signal)
  } catch (error) {
    if (current.signal.aborted) return
    stop()
    const e = error as Error
    message.value = e.name === 'NotAllowedError' ? 'Izin kamera ditolak. Izinkan kamera pada pengaturan browser, lalu coba lagi.' : e.name === 'NotFoundError' ? 'Kamera tidak ditemukan pada perangkat ini.' : e.name === 'NotReadableError' ? 'Kamera sedang digunakan aplikasi lain. Tutup aplikasi tersebut dan coba lagi.' : e.message || 'Kamera tidak dapat dibuka.'
  }
}
function visibility() { if (document.hidden) stop() }
watch(() => props.disabled, value => { if (value) stop() })
onMounted(() => document.addEventListener('visibilitychange', visibility))
onBeforeUnmount(() => { stop(); document.removeEventListener('visibilitychange', visibility) })
</script>

<template>
  <div class="mt-5">
    <p class="muted text-sm">Arahkan kamera ke QR angkatan yang ditampilkan admin. Jenis QR menentukan check-in atau check-out.</p>
    <video ref="video" v-show="active" autoplay muted playsinline aria-label="Kamera pemindai QR" class="mt-4 aspect-square w-full max-w-sm rounded-xl bg-black object-cover" />
    <p v-if="active" role="status" class="muted mt-3 text-sm">Mencari QR... Pastikan kode terlihat utuh dan cukup terang.</p>
    <p v-if="message" role="alert" class="mt-3 text-sm text-red-600 dark:text-red-400">{{ message }}</p>
    <button v-if="!active" class="btn mt-4" :disabled="disabled" @click="start">Scan QR dengan kamera</button>
    <button v-else class="btn mt-4" @click="stop">Tutup kamera</button>
  </div>
</template>
