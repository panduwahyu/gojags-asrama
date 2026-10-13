<script setup lang="ts">
import type { AllocationResult } from '~/types/room'

defineProps<{
  disabled?: boolean
}>()

const emit = defineEmits<{
  allocated: []
}>()

const api = useApi()
const action = useAction()
const result = shallowRef<AllocationResult | null>(null)

const incompleteNames = computed(() => {
  return result.value ? result.value.incomplete.map(item => item.name).join(', ') : ''
})

const waitingNames = computed(() => {
  return result.value ? result.value.waiting.map(item => item.name).join(', ') : ''
})

async function allocate() {
  result.value = null
  const ok = await action.run(async () => {
    const response = await api.post<AllocationResult>('rooms/allocate')
    result.value = response
    return response
  })
  if (ok) {
    emit('allocated')
  }
}
</script>

<template>
  <section class="card mt-6 p-5">
    <h2 class="font-semibold">
      Alokasi kamar otomatis
    </h2>
    <p class="muted mt-2 text-sm">
      Angkatan yang sama diprioritaskan pada satu lantai, mulai lantai 5 lalu turun ke 4 dan 3.
      Teman sekamar harus memiliki gender dan agama yang sama. Penghuni yang sudah dialokasikan
      tetap di kamarnya.
    </p>
    <p class="muted mt-2 text-sm">
      Simpan profil user terlebih dahulu. Profil belum lengkap atau kamar yang tidak sesuai akan
      dilaporkan setelah alokasi.
    </p>

    <AlertMessage
      :error="action.error.value"
      :success="action.success.value"
    />

    <button
      type="button"
      class="btn mt-4"
      :disabled="action.busy.value || disabled"
      @click="allocate"
    >
      {{ action.busy.value ? 'Memproses…' : 'Alokasikan kamar otomatis' }}
    </button>

    <div
      v-if="result"
      class="mt-4 space-y-3 text-sm"
    >
      <p v-if="result.incomplete.length">
        <b>Profil belum lengkap:</b> {{ incompleteNames }}
      </p>
      <p v-if="result.waiting.length">
        <b>Menunggu kamar yang sesuai:</b> {{ waitingNames }}
      </p>
      <details v-if="result.allocated.length">
        <summary>Hasil penempatan ({{ result.allocated.length }})</summary>
        <ul class="mt-2 list-inside list-disc">
          <li
            v-for="user in result.allocated"
            :key="`${user.id}-${user.room}`"
          >
            {{ user.name }} — kamar {{ user.room }}, lantai {{ user.floor }}
          </li>
        </ul>
      </details>
    </div>
  </section>
</template>
