<script setup lang="ts">
import type { Resident, WarningsResult } from '~/types/room'

const props = defineProps<{
  reloadTrigger: number
}>()

const api = useApi()
const action = useAction()
const profiles = ref<Resident[]>([])
const warnings = ref<string[]>([])

async function loadResidents() {
  try {
    const response = await api.get<{ data: Resident[] }>('residents')
    profiles.value = response.data.map((resident) => {
      const existing = profiles.value.find(item => item.id === resident.id)
      // Suntingan yang belum disimpan dipertahankan selama kamar penghuni tidak berubah.
      if (existing && existing.room_id === resident.room_id) {
        return existing
      }
      return { ...resident, cohort: resident.cohort ?? '' }
    })
  } catch {
    action.error.value = 'Gagal memuat daftar profil penghuni.'
  }
}

async function saveProfile(resident: Resident) {
  warnings.value = []
  await action.run(async () => {
    const result = await api.patch<WarningsResult>(`residents/${resident.id}`, {
      cohort: resident.cohort ?? '',
      gender: resident.gender,
      religion: resident.religion,
    })
    warnings.value = result.warnings
    return { message: `Profil ${resident.name} berhasil disimpan.` }
  })
}

// Penghuni yang check-out atau dialokasikan ikut tampil tanpa memuat ulang halaman.
usePolling(loadResidents, { pausedWhen: () => action.busy.value })

watch(() => props.reloadTrigger, () => {
  loadResidents()
})

onMounted(() => {
  loadResidents()
})
</script>

<template>
  <details class="card mt-6 p-6">
    <summary class="cursor-pointer font-semibold text-indigo-600 dark:text-indigo-400">
      Profil user untuk alokasi ({{ profiles.length }})
    </summary>

    <AlertMessage
      :error="action.error.value"
      :success="action.success.value"
    />

    <div
      v-if="warnings.length"
      role="status"
      class="mt-3 rounded-xl border border-amber-200 bg-amber-50/80 p-3.5 text-sm text-amber-900
        dark:border-amber-800/60 dark:bg-amber-950/40 dark:text-amber-200"
    >
      <p class="font-bold">
        Perhatian: profil tidak lagi cocok dengan teman sekamar (kamar tetap dipertahankan).
      </p>
      <ul class="mt-1 list-inside list-disc text-xs">
        <li
          v-for="warning in warnings"
          :key="warning"
        >
          {{ warning }}
        </li>
      </ul>
    </div>

    <p
      v-if="!profiles.length"
      class="muted mt-4 text-sm"
    >
      Belum ada akun user.
    </p>

    <div class="mt-4 space-y-6">
      <ResidentProfileForm
        v-for="resident in profiles"
        :key="resident.id"
        :resident="resident"
        :busy="action.busy.value"
        @save="saveProfile"
      />
    </div>
  </details>
</template>
