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
      // Pertahankan suntingan yang belum disimpan pada penghuni lain bila status kamarnya belum berubah.
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
  <details class="card mt-6 p-5">
    <summary class="font-semibold text-orange-600 dark:text-orange-400">
      Profil user untuk alokasi ({{ profiles.length }})
    </summary>
    <p class="muted mt-3 text-sm">
      Isi angkatan, gender, dan agama setiap user. Profil penghuni yang telah dialokasikan dikunci.
    </p>

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

    <form
      v-for="resident in profiles"
      :key="resident.id"
      class="mt-5 border-t border-slate-200 pt-5 dark:border-slate-700"
      @submit.prevent="saveProfile(resident)"
    >
      <h3 class="mb-3 text-sm font-semibold">
        {{ resident.name }}
        <span
          v-if="resident.room_id"
          class="muted font-normal"
        >
          · Sudah dialokasikan
        </span>
      </h3>
      <div class="grid gap-4 sm:grid-cols-3">
        <label>
          Angkatan
          <input
            v-model="resident.cohort"
            maxlength="100"
            required
            :aria-label="`Angkatan ${resident.name}`"
            :disabled="action.busy.value || Boolean(resident.room_id)"
          >
        </label>
        <label>
          Gender
          <select
            v-model="resident.gender"
            required
            :aria-label="`Gender ${resident.name}`"
            :disabled="action.busy.value || Boolean(resident.room_id)"
          >
            <option
              :value="null"
              disabled
            >
              Pilih gender
            </option>
            <option value="male">
              Laki-laki
            </option>
            <option value="female">
              Perempuan
            </option>
          </select>
        </label>
        <label>
          Agama
          <input
            v-model="resident.religion"
            maxlength="50"
            required
            list="agama-options"
            :aria-label="`Agama ${resident.name}`"
            :disabled="action.busy.value || Boolean(resident.room_id)"
          >
        </label>
      </div>
      <button
        v-if="!resident.room_id"
        class="btn mt-3"
        :disabled="action.busy.value"
      >
        Simpan profil {{ resident.name }}
      </button>
    </form>

    <datalist id="agama-options">
      <option
        v-for="option in RELIGION_OPTIONS"
        :key="option"
        :value="option"
      />
    </datalist>
  </details>
</template>
