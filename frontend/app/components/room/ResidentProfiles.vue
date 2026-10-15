<script setup lang="ts">
import type { Resident } from '~/types/room'

const props = defineProps<{
  reloadTrigger: number
}>()

const api = useApi()
const action = useAction()
const profiles = ref<Resident[]>([])

const RELIGION_OPTIONS = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu']

async function loadResidents() {
  try {
    const response = await api.get<{ data: Resident[] }>('residents')
    profiles.value = response.data.map((resident) => {
      const existing = profiles.value.find(item => item.id === resident.id)
      if (existing && existing.room_id === resident.room_id) {
        return existing
      }
      return {
        ...resident,
        cohort: resident.cohort ?? '',
        religion: resident.religion ?? '',
      }
    })
  } catch {
    action.error.value = 'Gagal memuat daftar profil penghuni.'
  }
}

async function saveProfile(resident: Resident) {
  await action.run(async () => {
    await api.patch(`residents/${resident.id}`, {
      cohort: resident.cohort ?? '',
      gender: resident.gender,
      religion: resident.religion ?? '',
    })
    return { message: `Profil ${resident.name} berhasil disimpan.` }
  })
}

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

    <datalist id="agama-options">
      <option
        v-for="option in RELIGION_OPTIONS"
        :key="option"
        :value="option"
      />
    </datalist>
  </details>
</template>
