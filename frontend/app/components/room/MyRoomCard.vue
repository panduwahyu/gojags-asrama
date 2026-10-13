<script setup lang="ts">
import type { MyRoom } from '~/types/room'

defineProps<{
  room: MyRoom | null
  loaded: boolean
}>()

const emit = defineEmits<{
  changed: []
}>()

const api = useApi()
const action = useAction()

async function transition(kind: 'check-in' | 'check-out') {
  const ok = await action.run(() => api.post(`my-room/${kind}`))
  if (ok) {
    emit('changed')
  }
}
</script>

<template>
  <div>
    <AlertMessage
      :error="action.error.value"
      :success="action.success.value"
    />

    <section
      v-if="room"
      class="card mt-6 p-6"
    >
      <h2 class="text-xl font-semibold">
        Kamar {{ room.number }}
      </h2>
      <p class="muted mt-2">
        Lantai {{ room.floor }}
      </p>
      <p class="mt-4 text-sm">
        {{ room.checked_in_at ? 'Sudah check-in' : 'Assigned · Menunggu check-in' }}
      </p>
      <p
        v-if="room.checked_in_at"
        class="muted mt-2 text-sm"
      >
        Waktu check-in:
        <time :datetime="room.checked_in_at">{{ formatWib(room.checked_in_at, 'medium') }}</time>
      </p>
      <p class="muted mt-4 text-sm">
        Check-out akan melepaskan alokasi kamar Anda dan menyimpan waktu keluar pada riwayat.
      </p>
      <div class="mt-5 flex gap-3">
        <button
          type="button"
          class="btn"
          :disabled="action.busy.value || Boolean(room.checked_in_at)"
          @click="transition('check-in')"
        >
          Check-in
        </button>
        <button
          type="button"
          class="btn"
          :disabled="action.busy.value || !room.checked_in_at"
          @click="transition('check-out')"
        >
          Check-out
        </button>
      </div>
    </section>

    <section
      v-else-if="loaded"
      class="card mt-6 p-6"
    >
      <h2 class="font-semibold">
        Belum ada alokasi kamar
      </h2>
      <p class="muted mt-2 text-sm">
        Hubungi admin untuk penempatan kamar.
      </p>
    </section>
  </div>
</template>
