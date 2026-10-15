<script setup lang="ts">
import type { Resident, Room, WarningsResult } from '~/types/room'

const props = defineProps<{
  residents: Resident[]
  rooms: Room[]
}>()

const emit = defineEmits<{
  changed: []
}>()

const api = useApi()
const action = useAction()
const residentId = ref<number | null>(null)
const roomId = ref<number | null>(null)
const pendingWarnings = ref<string[]>([])

const selected = computed(() => props.residents.find(item => item.id === residentId.value) ?? null)
const hasRoom = computed(() => Boolean(selected.value?.room_id))
const openRooms = computed(() => props.rooms.filter(room => room.is_active && room.assigned_count < room.capacity))

function describe(resident: Resident): string {
  const place = resident.room_number ? `kamar ${resident.room_number}` : 'belum berkamar'
  return `${resident.name} — ${place}`
}

async function place(confirm: boolean) {
  const resident = selected.value
  if (!resident || roomId.value === null) {
    return
  }
  const verb = resident.room_id ? 'move' : 'assign'

  const ok = await action.run(async () => {
    try {
      return await api.post<WarningsResult>(`rooms/${roomId.value}/${verb}`, {
        user_id: resident.id,
        confirm,
      })
    } catch (error) {
      // 409: menyimpang dari yang ideal; petugas diminta mengonfirmasi, bukan galat.
      if (statusOf(error) === 409) {
        const data = (error as { data?: { warnings?: string[] } }).data
        pendingWarnings.value = data?.warnings ?? []
        return undefined
      }
      throw error
    }
  })

  if (ok && pendingWarnings.value.length === 0) {
    residentId.value = null
    roomId.value = null
    emit('changed')
  }
}

function submit() {
  pendingWarnings.value = []
  place(false)
}

async function confirmPlacement() {
  pendingWarnings.value = []
  await place(true)
}
</script>

<template>
  <form
    class="mt-4 space-y-4"
    @submit.prevent="submit"
  >
    <p class="muted text-xs">
      Pilih penghuni lalu kamar tujuan. Laki-laki dan perempuan tidak boleh sekamar. Bila agama atau angkatan
      berbeda dengan teman sekamar, atau kamar di luar lantai rombongan, Anda akan diminta mengonfirmasi.
      Penghuni yang sudah check-in tidak dapat dipindahkan.
    </p>

    <AlertMessage
      :error="action.error.value"
      :success="action.success.value"
    />

    <div class="grid gap-4 sm:grid-cols-2">
      <FormField label="Penghuni" for="placement-resident">
        <select
          id="placement-resident"
          v-model="residentId"
          required
          :disabled="action.busy.value"
          @change="pendingWarnings = []"
        >
          <option :value="null" disabled>
            Pilih penghuni
          </option>
          <option
            v-for="resident in residents"
            :key="resident.id"
            :value="resident.id"
            :disabled="Boolean(resident.checked_in_at)"
          >
            {{ describe(resident) }}{{ resident.checked_in_at ? ' (sudah check-in)' : '' }}
          </option>
        </select>
      </FormField>
      <FormField label="Kamar tujuan" for="placement-room">
        <select
          id="placement-room"
          v-model="roomId"
          required
          :disabled="action.busy.value"
          @change="pendingWarnings = []"
        >
          <option :value="null" disabled>
            Pilih kamar
          </option>
          <option
            v-for="room in openRooms"
            :key="room.id"
            :value="room.id"
          >
            Kamar {{ room.number }} · lantai {{ room.floor }} ({{ room.assigned_count }}/{{ room.capacity }})
          </option>
        </select>
      </FormField>
    </div>

    <div
      v-if="pendingWarnings.length"
      role="alert"
      class="rounded-xl border border-amber-300 bg-amber-50/90 p-4 text-sm text-amber-900
        dark:border-amber-700/60 dark:bg-amber-950/40 dark:text-amber-200"
    >
      <p class="font-bold">
        Penempatan ini menyimpang dari yang ideal:
      </p>
      <ul class="mt-1 list-inside list-disc text-xs">
        <li
          v-for="warning in pendingWarnings"
          :key="warning"
        >
          {{ warning }}
        </li>
      </ul>
      <div class="mt-3 flex gap-3">
        <button
          type="button"
          class="btn btn-sm"
          :disabled="action.busy.value"
          @click="confirmPlacement"
        >
          Tetap lanjutkan
        </button>
        <button
          type="button"
          class="btn-text py-1! px-2! text-xs"
          :disabled="action.busy.value"
          @click="pendingWarnings = []"
        >
          Batal
        </button>
      </div>
    </div>

    <button
      class="btn"
      :disabled="action.busy.value || !selected || roomId === null"
    >
      {{ hasRoom ? 'Pindahkan penghuni' : 'Tempatkan penghuni' }}
    </button>
  </form>
</template>
