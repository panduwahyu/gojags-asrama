<script setup lang="ts">
import type { Resident } from '~/types/room'
import { RELIGIONS } from '~/types/room'

const props = defineProps<{
  resident: Resident
  busy: boolean
}>()

const emit = defineEmits<{
  save: [resident: Resident]
}>()
</script>

<template>
  <form
    class="rounded-xl border border-slate-100 bg-slate-50/50 p-4 dark:border-slate-800 dark:bg-slate-900/40"
    @submit.prevent="emit('save', props.resident)"
  >
    <div class="mb-3 flex items-center justify-between">
      <h3 class="text-sm font-bold text-slate-800 dark:text-white">
        {{ resident.name }}
      </h3>
      <span
        v-if="resident.room_id"
        class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700
          dark:bg-emerald-950/40 dark:text-emerald-300"
      >
        Sudah dialokasikan
      </span>
    </div>
    <div class="grid gap-4 sm:grid-cols-3">
      <FormField label="Angkatan" :for="`cohort-${resident.id}`">
        <input
          :id="`cohort-${resident.id}`"
          v-model="resident.cohort"
          maxlength="100"
          required
          :aria-label="`Angkatan ${resident.name}`"
          :disabled="busy"
        >
      </FormField>
      <FormField label="Gender" :for="`gender-${resident.id}`">
        <select
          :id="`gender-${resident.id}`"
          v-model="resident.gender"
          required
          :aria-label="`Gender ${resident.name}`"
          :disabled="busy"
        >
          <option :value="null" disabled>
            Pilih gender
          </option>
          <option value="male">
            Laki-laki
          </option>
          <option value="female">
            Perempuan
          </option>
        </select>
      </FormField>
      <FormField label="Agama" :for="`religion-${resident.id}`">
        <select
          :id="`religion-${resident.id}`"
          v-model="resident.religion"
          required
          :aria-label="`Agama ${resident.name}`"
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
      class="btn mt-4"
      :disabled="busy"
    >
      Simpan profil {{ resident.name }}
    </button>
  </form>
</template>
