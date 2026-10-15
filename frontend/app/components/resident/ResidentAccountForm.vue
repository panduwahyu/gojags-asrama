<script setup lang="ts">
import type { Gender, Religion, ResidentInput } from '~/types/room'
import { RELIGIONS } from '~/types/room'

defineProps<{
  busy: boolean
}>()

const emit = defineEmits<{
  create: [input: ResidentInput]
}>()

const form = reactive({
  name: '',
  email: '',
  cohort: '',
  gender: null as Gender | null,
  religion: null as Religion | null,
})

function handleSubmit() {
  if (form.gender === null || form.religion === null) {
    return
  }
  emit('create', {
    name: form.name,
    email: form.email,
    cohort: form.cohort,
    gender: form.gender,
    religion: form.religion,
  })
}
</script>

<template>
  <form
    class="mt-4 space-y-4"
    @submit.prevent="handleSubmit"
  >
    <div class="grid gap-4 sm:grid-cols-2">
      <FormField label="Nama lengkap" for="account-name">
        <input
          id="account-name"
          v-model="form.name"
          maxlength="255"
          required
          autocomplete="off"
          :disabled="busy"
        >
      </FormField>
      <FormField label="Email" for="account-email">
        <input
          id="account-email"
          v-model="form.email"
          type="email"
          maxlength="255"
          required
          autocomplete="off"
          :disabled="busy"
        >
      </FormField>
    </div>
    <div class="grid gap-4 sm:grid-cols-3">
      <FormField label="Angkatan" for="account-cohort">
        <input
          id="account-cohort"
          v-model="form.cohort"
          maxlength="100"
          required
          placeholder="Contoh: 64 atau 2026 A"
          :disabled="busy"
        >
      </FormField>
      <FormField label="Jenis kelamin" for="account-gender">
        <select
          id="account-gender"
          v-model="form.gender"
          required
          :disabled="busy"
        >
          <option :value="null" disabled>
            Pilih jenis kelamin
          </option>
          <option value="male">
            Laki-laki
          </option>
          <option value="female">
            Perempuan
          </option>
        </select>
      </FormField>
      <FormField label="Agama" for="account-religion">
        <select
          id="account-religion"
          v-model="form.religion"
          required
          :disabled="busy"
        >
          <option :value="null" disabled>
            Pilih agama
          </option>
          <option
            v-for="religion in RELIGIONS"
            :key="religion.value"
            :value="religion.value"
          >
            {{ religion.label }}
          </option>
        </select>
      </FormField>
    </div>
    <button
      class="btn"
      :disabled="busy"
    >
      {{ busy ? 'Memproses…' : 'Buat akun penghuni' }}
    </button>
  </form>
</template>
