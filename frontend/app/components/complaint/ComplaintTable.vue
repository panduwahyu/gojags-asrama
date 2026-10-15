<script setup lang="ts">
import type { Complaint } from '~/types/complaint'
import { COMPLAINT_TONE } from '~/types/complaint'

const props = defineProps<{
  table: ReturnType<typeof useTable<Complaint>>
  busy: boolean
  admin: boolean
}>()

const emit = defineEmits<{
  resolve: [complaint: Complaint]
}>()

const columns = computed(() => [
  ...(props.admin ? [{ key: 'user_name', label: 'Nama user', sortable: true }] : []),
  { key: 'title', label: 'Judul', sortable: true },
  { key: 'body', label: 'Isi keluhan', sortable: true },
  { key: 'created_at', label: 'Waktu diajukan', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'resolved_at', label: 'Waktu resolved', sortable: true },
  ...(props.admin ? [{ key: 'actions', label: 'Aksi' }] : []),
])
</script>

<template>
  <DataTable
    :table="table"
    :columns="columns"
    search-label="Cari keluhan asrama"
    empty-text="Belum ada keluhan asrama."
  >
    <template #title="{ row }">
      <span class="min-w-36 break-words font-semibold">{{ row.title }}</span>
    </template>

    <template #body="{ row }">
      <span class="min-w-48 whitespace-pre-wrap break-words">{{ row.body }}</span>
    </template>

    <template #created_at="{ row }">
      <span class="whitespace-nowrap">{{ formatWib(row.created_at) }}</span>
    </template>

    <template #status="{ row }">
      <StatusBadge :tone="COMPLAINT_TONE[row.status]">
        {{ row.status_label }}
      </StatusBadge>
    </template>

    <template #resolved_at="{ row }">
      <span class="whitespace-nowrap">
        {{ row.resolved_at ? formatWib(row.resolved_at) : 'Belum diselesaikan' }}
      </span>
    </template>

    <template #actions="{ row }">
      <button
        v-if="row.status === 'pending'"
        type="button"
        class="btn btn-sm whitespace-nowrap"
        :disabled="busy"
        @click="emit('resolve', row)"
      >
        Tandai Resolved
      </button>
      <span
        v-else
        class="muted text-xs"
      >
        Sudah ditangani
      </span>
    </template>
  </DataTable>
</template>
