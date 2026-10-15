<script setup lang="ts">
import type { RoomMonitoring, RoomStatus } from '~/types/room'
import { ROOM_TONE } from '~/types/room'

defineProps<{
  monitoring: RoomMonitoring
}>()

const CARDS: { status: RoomStatus; label: string }[] = [
  { status: 'empty', label: 'Kosong' },
  { status: 'assigned', label: 'Assigned' },
  { status: 'occupied', label: 'Terisi' },
  { status: 'full', label: 'Penuh' },
]
</script>

<template>
  <section
    aria-label="Monitoring kamar"
    class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4"
  >
    <div
      v-for="card in CARDS"
      :key="card.status"
      class="card p-5 transition hover:-translate-y-0.5 hover:shadow-md"
    >
      <div class="flex items-center justify-between">
        <StatusBadge :tone="ROOM_TONE[card.status]">
          {{ card.label }}
        </StatusBadge>
      </div>
      <p class="mt-4 text-3xl font-black tracking-tight text-slate-900 dark:text-white">
        {{ monitoring[card.status] ?? 0 }}
      </p>
    </div>
  </section>
</template>
