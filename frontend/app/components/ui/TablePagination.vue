<script setup lang="ts">
defineProps<{
  current: number
  last: number
  loading: boolean
}>()

const page = defineModel<number>('page', { required: true })
const perPage = defineModel<number>('perPage', { required: true })

const navBtnClass = [
  'inline-flex min-h-[34px] sm:min-h-0 items-center justify-center rounded-lg border border-slate-200',
  'bg-white px-3 py-1.5 sm:py-1 text-xs font-semibold text-slate-700 transition hover:bg-slate-50',
  'focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500',
  'disabled:pointer-events-none disabled:opacity-40 dark:border-slate-800 dark:bg-slate-900',
  'dark:text-slate-200 dark:hover:bg-slate-800',
].join(' ')
</script>

<template>
  <nav
    aria-label="Navigasi halaman tabel"
    class="mt-5 flex flex-wrap items-center justify-between gap-4 text-xs text-slate-500 dark:text-slate-400"
  >
    <div class="flex items-center gap-2">
      <label class="flex flex-row items-center gap-2 text-xs font-medium text-slate-600 dark:text-slate-300">
        Baris per halaman:
        <select
          v-model.number="perPage"
          aria-label="Baris per halaman"
          class="w-auto! py-1! px-2! text-xs!"
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
    </div>

    <div class="flex flex-wrap items-center gap-1.5">
      <button
        type="button"
        :class="navBtnClass"
        :disabled="current <= 1 || loading"
        @click="page = 1"
      >
        Awal
      </button>
      <button
        type="button"
        :class="navBtnClass"
        :disabled="current <= 1 || loading"
        @click="page = current - 1"
      >
        Sebelumnya
      </button>
      <span
        :aria-label="`Halaman ${current} dari ${last}`"
        class="px-2 font-semibold text-slate-800 dark:text-slate-100"
      >
        {{ current }} / {{ last }}
      </span>
      <button
        type="button"
        :class="navBtnClass"
        :disabled="current >= last || loading"
        @click="page = current + 1"
      >
        Berikutnya
      </button>
      <button
        type="button"
        :class="navBtnClass"
        :disabled="current >= last || loading"
        @click="page = last"
      >
        Akhir
      </button>
    </div>
  </nav>
</template>
