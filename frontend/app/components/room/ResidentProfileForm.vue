<script setup lang="ts">
import type { Resident } from '~/types/room'

const props = defineProps<{
  resident: Resident
  busy: boolean
}>()

const emit = defineEmits<{
  save: [resident: Resident]
}>()

const RELIGIONS = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu']
</script>

<template>
  <form
    class="rounded-xl border border-slate-100 bg-slate-50/50 p-4 dark:border-slate-800 dark:bg-slate-900/40"
    @submit.prevent="emit('save', resident)"
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
      <label>
        Angkatan
        <input
          v-model="resident.cohort"
          maxlength="100"
          required
          :aria-label="`Angkatan ${resident.name}`"
          :disabled="busy || Boolean(resident.room_id)"
        >
      </label>
      <label>
        Gender
        <select
          v-model="resident.gender"
          required
          :aria-label="`Gender ${resident.name}`"
          :disabled="busy || Boolean(resident.room_id)"
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
      </label>
      <label>
        Agama
        <input
          v-model="resident.religion"
          maxlength="50"
          required
          list="religion-options"
          :aria-label="`Agama ${resident.name}`"
          :disabled="busy || Boolean(resident.room_id)"
        >
      </label>
    </div>
    <button
      v-if="!resident.room_id"
      class="btn mt-4"
      :disabled="busy"
    >
      Simpan profil {{ resident.name }}
    </button>
  </form>
</template>
