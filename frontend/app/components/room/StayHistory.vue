<script setup lang="ts">
import type { Stay } from '~/types/room'

defineProps<{
  stays: Stay[]
}>()
</script>

<template>
  <section
    aria-label="Riwayat check-in dan check-out"
    class="card mt-6 p-6 md:p-8"
  >
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-lg font-bold text-slate-900 dark:text-white">
          Riwayat check-in dan check-out
        </h2>
      </div>
      <span
        class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-bold text-indigo-700
          dark:bg-indigo-950/50 dark:text-indigo-300"
      >
        {{ stays.length }} riwayat
      </span>
    </div>

    <div
      v-if="!stays.length"
      class="mt-6 rounded-xl border border-dashed border-slate-200 p-8 text-center dark:border-slate-800"
    >
      <div
        class="mx-auto flex size-12 items-center justify-center rounded-xl bg-slate-100 text-slate-400
          dark:bg-slate-800 dark:text-slate-500"
      >
        <AppIcon name="kamar" class="size-6" />
      </div>
      <p class="muted mt-3 text-sm">
        Belum ada riwayat.
      </p>
    </div>

    <ul
      v-else
      class="mt-6 space-y-3"
    >
      <li
        v-for="stay in stays"
        :key="stay.id"
        class="rounded-xl border border-slate-200/80 bg-slate-50/50 p-4 transition-all
          hover:border-indigo-200 dark:border-slate-800 dark:bg-slate-900/50 dark:hover:border-indigo-900"
      >
        <div class="flex flex-wrap items-center justify-between gap-2">
          <h3 class="text-sm font-bold text-slate-800 dark:text-white">
            Kamar {{ stay.room_number }} · Lantai {{ stay.floor }}
          </h3>
          <StatusBadge :tone="stay.checked_out_at ? 'neutral' : 'success'">
            {{ stay.checked_out_at ? 'Selesai' : 'Sedang Menginap' }}
          </StatusBadge>
        </div>

        <div class="mt-3 grid gap-2 text-xs sm:grid-cols-2">
          <div class="rounded-lg bg-white p-2.5 shadow-2xs dark:bg-slate-800/80">
            <span class="text-slate-400">Check-in: </span>
            <time
              :datetime="stay.checked_in_at"
              class="font-medium text-slate-700 dark:text-slate-200"
            >
              {{ formatWib(stay.checked_in_at, 'medium') }}
            </time>
          </div>
          <div class="rounded-lg bg-white p-2.5 shadow-2xs dark:bg-slate-800/80">
            <span class="text-slate-400">Check-out: </span>
            <time
              v-if="stay.checked_out_at"
              :datetime="stay.checked_out_at"
              class="font-medium text-slate-700 dark:text-slate-200"
            >
              {{ formatWib(stay.checked_out_at, 'medium') }}
            </time>
            <span
              v-else
              class="font-medium text-emerald-600 dark:text-emerald-400"
            >
              Belum check-out
            </span>
          </div>
        </div>
      </li>
    </ul>
  </section>
</template>
