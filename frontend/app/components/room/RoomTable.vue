<script setup lang="ts">
import type { Room, RoomsResponse } from '~/types/room'
import { ROOM_TONE } from '~/types/room'

defineProps<{
  table: ReturnType<typeof useTable<Room, RoomsResponse>>
}>()

const columns = [
  { key: 'number', label: 'Kamar', sortable: true },
  { key: 'floor', label: 'Lantai', sortable: true },
  { key: 'resident', label: 'Penghuni', sortable: true },
  { key: 'assigned_count', label: 'Kapasitas', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
]
</script>

<template>
  <section
    aria-label="Daftar kamar"
    class="card mt-6 p-4 md:p-6"
  >
    <DataTable
      :table="table"
      :columns="columns"
      search-label="Cari kamar"
      empty-text="Kamar tidak ditemukan."
    >
      <template #number="{ row }">
        <span class="font-semibold">{{ row.number }}</span>
      </template>

      <template #resident="{ row }">
        <div
          v-for="person in row.residents"
          :key="person.id"
          class="resident-name"
        >
          {{ person.name }}
          <small class="muted mt-1 block text-xs">
            {{
              person.checked_in_at
                ? `Check-in: ${formatWib(person.checked_in_at, 'medium')}`
                : 'Belum check-in'
            }}
          </small>
        </div>
        <span
          v-if="!row.residents.length"
          class="muted text-xs"
        >
          Belum ada penghuni
        </span>
      </template>

      <template #assigned_count="{ row }">
        <span class="font-medium text-slate-700 dark:text-slate-300">
          {{ row.assigned_count }} / {{ row.capacity }}
        </span>
      </template>

      <template #status="{ row }">
        <StatusBadge :tone="ROOM_TONE[row.status]">
          {{ row.status_label }}
        </StatusBadge>
      </template>
    </DataTable>
  </section>
</template>
