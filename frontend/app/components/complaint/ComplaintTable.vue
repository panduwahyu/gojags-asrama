<script setup lang="ts">
import type { Complaint } from '~/types/complaint'
import { COMPLAINT_TONE } from '~/types/complaint'

const props = defineProps<{
  table: ReturnType<typeof useTable<Complaint>>
  busy: boolean
  admin: boolean
}>()

const emit = defineEmits<{
  progress: [complaint: Complaint, response: string]
  resolve: [complaint: Complaint, response: string]
  reopen: [complaint: Complaint]
}>()

const columns = computed(() => [
  ...(props.admin ? [{ key: 'user_name', label: 'Nama user', sortable: true }] : []),
  { key: 'title', label: 'Judul', sortable: true },
  { key: 'body', label: 'Isi keluhan', sortable: true },
  { key: 'created_at', label: 'Waktu diajukan', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'response', label: 'Tanggapan petugas' },
  { key: 'resolved_at', label: 'Waktu resolved', sortable: true },
  { key: 'actions', label: 'Aksi' },
])
</script>

<template>
  <DataTable
    :table="table"
    :columns="columns"
    search-label="Cari keluhan asrama"
    empty-text="Belum ada keluhan asrama."
    export-path="complaints/export"
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

    <template #response="{ row }">
      <span
        v-if="row.response"
        class="min-w-40 whitespace-pre-wrap break-words"
      >
        {{ row.response }}
      </span>
      <span
        v-else
        class="muted text-xs"
      >
        Belum ada tanggapan
      </span>
    </template>

    <template #resolved_at="{ row }">
      <span class="whitespace-nowrap">
        {{ row.resolved_at ? formatWib(row.resolved_at) : 'Belum diselesaikan' }}
      </span>
    </template>

    <template #actions="{ row }">
      <button
        v-if="row.status === 'resolved'"
        type="button"
        class="btn-secondary btn-sm whitespace-nowrap"
        :disabled="busy"
        @click="emit('reopen', row)"
      >
        Buka kembali
      </button>
      <ComplaintResponseForm
        v-else-if="admin"
        :complaint="row"
        :busy="busy"
        @progress="(complaint, response) => emit('progress', complaint, response)"
        @resolve="(complaint, response) => emit('resolve', complaint, response)"
      />
      <span
        v-else
        class="muted text-xs"
      >
        Menunggu petugas
      </span>
    </template>
  </DataTable>
</template>
