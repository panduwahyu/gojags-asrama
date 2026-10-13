<script setup lang="ts">
import type { Stay } from '~/types/room'

defineProps<{
  stays: Stay[]
}>()
</script>

<template>
  <section
    aria-label="Riwayat check-in dan check-out"
    class="card mt-6 p-6"
  >
    <h2 class="font-semibold">
      Riwayat check-in dan check-out
    </h2>
    <p
      v-if="!stays.length"
      class="muted mt-4 text-sm"
    >
      Belum ada riwayat.
    </p>
    <ul
      v-else
      class="mt-4 space-y-4"
    >
      <li
        v-for="stay in stays"
        :key="stay.id"
        class="rounded-lg border border-slate-200 p-4 text-sm dark:border-slate-700"
      >
        <h3 class="font-semibold">
          Kamar {{ stay.room_number }} · Lantai {{ stay.floor }}
        </h3>
        <p class="mt-2">
          Check-in:
          <time :datetime="stay.checked_in_at">{{ formatWib(stay.checked_in_at, 'medium') }}</time>
        </p>
        <p class="mt-2">
          Check-out:
          <time
            v-if="stay.checked_out_at"
            :datetime="stay.checked_out_at"
          >
            {{ formatWib(stay.checked_out_at, 'medium') }}
          </time>
          <span v-else>Belum check-out</span>
        </p>
      </li>
    </ul>
  </section>
</template>
