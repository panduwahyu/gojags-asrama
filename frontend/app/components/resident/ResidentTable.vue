<script setup lang="ts">
import type { Resident } from '~/types/room'
import { RELIGIONS } from '~/types/room'

const props = defineProps<{
  residents: Resident[]
  busy: boolean
}>()

const emit = defineEmits<{
  unassign: [resident: Resident]
  checkOut: [resident: Resident]
  cancelCheckOut: [resident: Resident]
  resetPassword: [resident: Resident]
}>()

const search = ref('')

const filtered = computed(() => {
  const term = search.value.trim().toLowerCase()
  if (!term) {
    return props.residents
  }
  return props.residents.filter(resident =>
    [resident.name, resident.email, resident.cohort ?? ''].some(value => value.toLowerCase().includes(term)),
  )
})

function religionLabel(value: Resident['religion']): string {
  return RELIGIONS.find(item => item.value === value)?.label ?? '—'
}

function genderLabel(value: Resident['gender']): string {
  return value === 'male' ? 'Laki-laki' : value === 'female' ? 'Perempuan' : '—'
}
</script>

<template>
  <div class="mt-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <input
        v-model="search"
        type="search"
        class="sm:w-72!"
        aria-label="Cari penghuni"
        placeholder="Cari nama, email, atau angkatan…"
      >
      <ExportButtons
        path="residents/export"
        name="penghuni"
      />
    </div>

    <div class="card mt-4 overflow-hidden overflow-x-auto shadow-xs">
      <table class="w-full border-collapse text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide dark:bg-slate-900/60">
          <tr>
            <th class="px-4 py-3">
              Penghuni
            </th>
            <th class="px-4 py-3">
              Angkatan
            </th>
            <th class="px-4 py-3">
              Jenis kelamin
            </th>
            <th class="px-4 py-3">
              Agama
            </th>
            <th class="px-4 py-3">
              Kamar
            </th>
            <th class="px-4 py-3">
              Aksi
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
          <tr
            v-for="resident in filtered"
            :key="resident.id"
          >
            <td class="px-4 py-3.5">
              <span class="font-semibold">{{ resident.name }}</span>
              <small class="muted block text-xs">{{ resident.email }}</small>
            </td>
            <td class="px-4 py-3.5">
              {{ resident.cohort || '—' }}
            </td>
            <td class="px-4 py-3.5">
              {{ genderLabel(resident.gender) }}
            </td>
            <td class="px-4 py-3.5">
              {{ religionLabel(resident.religion) }}
            </td>
            <td class="px-4 py-3.5">
              <template v-if="resident.room_id">
                <span class="font-semibold">Kamar {{ resident.room_number }}</span>
                <StatusBadge
                  class="ml-2"
                  :tone="resident.checked_in_at ? 'success' : 'warning'"
                >
                  {{ resident.checked_in_at ? 'Sudah check-in' : 'Menunggu check-in' }}
                </StatusBadge>
              </template>
              <span
                v-else
                class="muted text-xs"
              >
                Belum berkamar
              </span>
            </td>
            <td class="px-4 py-3.5">
              <div class="flex min-w-56 flex-wrap items-center gap-2">
                <ConfirmButton
                  v-if="resident.room_id && !resident.checked_in_at"
                  label="Cabut alokasi"
                  confirm-label="Ya, cabut"
                  :busy="busy"
                  :described-as="`Cabut alokasi ${resident.name}`"
                  @confirm="emit('unassign', resident)"
                />
                <ConfirmButton
                  v-if="resident.room_id && resident.checked_in_at"
                  label="Check-out"
                  confirm-label="Ya, check-out"
                  :busy="busy"
                  :described-as="`Check-out ${resident.name}`"
                  @confirm="emit('checkOut', resident)"
                />
                <ConfirmButton
                  v-if="resident.can_cancel_check_out"
                  label="Batalkan check-out"
                  confirm-label="Ya, batalkan"
                  :busy="busy"
                  :described-as="`Batalkan check-out ${resident.name}`"
                  @confirm="emit('cancelCheckOut', resident)"
                />
                <ConfirmButton
                  label="Reset sandi"
                  confirm-label="Ya, reset"
                  :busy="busy"
                  :described-as="`Reset sandi ${resident.name}`"
                  @confirm="emit('resetPassword', resident)"
                />
              </div>
            </td>
          </tr>
          <tr v-if="!filtered.length">
            <td
              colspan="6"
              class="p-8 text-center"
            >
              <p class="muted text-sm">
                {{ residents.length ? 'Penghuni tidak ditemukan.' : 'Belum ada akun penghuni.' }}
              </p>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
