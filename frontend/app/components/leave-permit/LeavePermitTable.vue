<script setup lang="ts">
import type { LeavePermit, PermitStatus } from '~/types/leave-permit'
import { PERMIT_TONE } from '~/types/leave-permit'

const props = defineProps<{
  table: ReturnType<typeof useTable<LeavePermit>>
  busy: boolean
  admin: boolean
}>()

const emit = defineEmits<{
  edit: [permit: LeavePermit]
  review: [permit: LeavePermit, status: PermitStatus]
}>()

const columns = computed(() => [
  ...(props.admin ? [{ key: 'user_name', label: 'Nama user', sortable: true }] : []),
  { key: 'leaves_at', label: 'Waktu keluar', sortable: true },
  { key: 'returns_at', label: 'Waktu kembali', sortable: true },
  { key: 'reason', label: 'Alasan', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'actions', label: 'Aksi' },
])
</script>

<template>
  <DataTable
    :table="table"
    :columns="columns"
    search-label="Cari izin keluar"
    empty-text="Belum ada pengajuan izin keluar."
  >
    <template #leaves_at="{ row }">
      <span class="whitespace-nowrap">{{ formatWib(row.leaves_at) }}</span>
    </template>

    <template #returns_at="{ row }">
      <span class="whitespace-nowrap">{{ formatWib(row.returns_at) }}</span>
    </template>

    <template #reason="{ row }">
      <span class="min-w-48 whitespace-pre-wrap break-words">{{ row.reason }}</span>
    </template>

    <template #status="{ row }">
      <StatusBadge :tone="PERMIT_TONE[row.status]">
        {{ row.status_label }}
      </StatusBadge>
    </template>

    <template #actions="{ row }">
      <div
        v-if="row.status === 'pending'"
        class="flex gap-3"
      >
        <template v-if="admin">
          <button
            type="button"
            class="btn py-1! px-2.5! text-xs font-semibold"
            :disabled="busy"
            @click="emit('review', row, 'approved')"
          >
            Setujui
          </button>
          <button
            type="button"
            class="btn-danger py-1! px-2.5! text-xs font-semibold"
            :disabled="busy"
            @click="emit('review', row, 'rejected')"
          >
            Tolak
          </button>
        </template>
        <button
          v-else
          type="button"
          class="btn-secondary py-1! px-2.5! text-xs font-semibold"
          :disabled="busy"
          @click="emit('edit', row)"
        >
          Edit
        </button>
      </div>
      <span
        v-else
        class="muted text-xs"
      >
        Sudah diproses
      </span>
    </template>
  </DataTable>
</template>
