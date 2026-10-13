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
      class="card p-5"
    >
      <StatusBadge :tone="ROOM_TONE[card.status]">
        {{ card.label }}
      </StatusBadge>
      <p class="mt-4 text-3xl font-bold">
        {{ monitoring[card.status] ?? 0 }}
      </p>
      <p class="muted mt-1 text-xs">
        kamar
      </p>
    </div>
  </section>
</template>
