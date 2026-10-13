<script setup lang="ts">
defineProps<{
  current: number
  last: number
  loading: boolean
}>()

const page = defineModel<number>('page', { required: true })
const perPage = defineModel<number>('perPage', { required: true })
</script>

<template>
  <nav
    aria-label="Navigasi halaman tabel"
    class="muted mt-5 flex flex-wrap items-center justify-center gap-4 text-xs"
  >
    <label class="flex items-center gap-2">
      Baris per halaman
      <select
        v-model.number="perPage"
        aria-label="Baris per halaman"
        class="w-auto!"
        :disabled="loading"
      >
        <option :value="10">
          10
        </option>
        <option :value="50">
          50
        </option>
        <option :value="100">
          100
        </option>
      </select>
    </label>
    <button
      type="button"
      :disabled="current <= 1 || loading"
      @click="page = 1"
    >
      Awal
    </button>
    <button
      type="button"
      :disabled="current <= 1 || loading"
      @click="page = current - 1"
    >
      Sebelumnya
    </button>
    <span
      role="status"
      aria-live="polite"
      :aria-label="`Halaman ${current} dari ${last}`"
    >
      {{ current }}/{{ last }}
    </span>
    <button
      type="button"
      :disabled="current >= last || loading"
      @click="page = current + 1"
    >
      Berikutnya
    </button>
    <button
      type="button"
      :disabled="current >= last || loading"
      @click="page = last"
    >
      Akhir
    </button>
  </nav>
</template>
