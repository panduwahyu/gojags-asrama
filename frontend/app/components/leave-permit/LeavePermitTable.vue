<script setup lang="ts">
import type { LeavePermit, PermitReviewInput } from '~/types/leave-permit'
import { PERMIT_TONE } from '~/types/leave-permit'

const props = defineProps<{
  table: ReturnType<typeof useTable<LeavePermit>>
  busy: boolean
  admin: boolean
}>()

const emit = defineEmits<{
  edit: [permit: LeavePermit]
  review: [permit: LeavePermit, input: PermitReviewInput]
  cancel: [permit: LeavePermit]
}>()

// Penolakan wajib beralasan: kolom alasan muncul di baris yang sedang ditolak.
const rejectingId = ref<number | null>(null)
const rejectionReason = ref('')

const columns = computed(() => [
  ...(props.admin ? [{ key: 'user_name', label: 'Nama user', sortable: true }] : []),
  { key: 'leaves_at', label: 'Waktu keluar', sortable: true },
  { key: 'returns_at', label: 'Waktu kembali', sortable: true },
  { key: 'reason', label: 'Alasan', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'actions', label: 'Aksi' },
])

function startRejecting(permit: LeavePermit) {
  rejectingId.value = permit.id
  rejectionReason.value = permit.rejection_reason ?? ''
}

function stopRejecting() {
  rejectingId.value = null
  rejectionReason.value = ''
}

function approve(permit: LeavePermit) {
  stopRejecting()
  emit('review', permit, { status: 'approved' })
}

function reject(permit: LeavePermit) {
  emit('review', permit, { status: 'rejected', rejection_reason: rejectionReason.value })
  stopRejecting()
}
</script>

<template>
  <DataTable
    :table="table"
    :columns="columns"
    search-label="Cari izin keluar"
    empty-text="Belum ada pengajuan izin keluar."
    export-path="leave-permits/export"
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
      <p
        v-if="row.status === 'rejected' && row.rejection_reason"
        class="mt-1.5 max-w-56 whitespace-pre-wrap break-words text-xs text-rose-700 dark:text-rose-300"
      >
        Alasan penolakan: {{ row.rejection_reason }}
      </p>
    </template>

    <template #actions="{ row }">
      <span
        v-if="row.status === 'cancelled'"
        class="muted text-xs"
      >
        Dibatalkan penghuni
      </span>

      <div
        v-else-if="admin"
        class="min-w-44"
      >
        <div class="flex flex-wrap gap-3">
          <button
            v-if="row.status !== 'approved'"
            type="button"
            class="btn btn-sm"
            :disabled="busy"
            @click="approve(row)"
          >
            Setujui
          </button>
          <button
            v-if="row.status !== 'rejected' && rejectingId !== row.id"
            type="button"
            class="btn-danger btn-sm"
            :disabled="busy"
            @click="startRejecting(row)"
          >
            Tolak
          </button>
        </div>
        <form
          v-if="rejectingId === row.id"
          class="mt-2 space-y-2"
          @submit.prevent="reject(row)"
        >
          <label :for="`reject-reason-${row.id}`">Alasan penolakan</label>
          <textarea
            :id="`reject-reason-${row.id}`"
            v-model="rejectionReason"
            rows="2"
            maxlength="1000"
            required
            :disabled="busy"
            placeholder="Jelaskan alasan penolakan"
          />
          <div class="flex gap-2">
            <button
              class="btn-danger btn-sm"
              :disabled="busy"
            >
              Kirim penolakan
            </button>
            <button
              type="button"
              class="btn-text py-1! px-2! text-xs"
              :disabled="busy"
              @click="stopRejecting"
            >
              Batal
            </button>
          </div>
        </form>
      </div>

      <div
        v-else-if="row.status === 'pending'"
        class="flex flex-wrap items-center gap-3"
      >
        <button
          type="button"
          class="btn-secondary btn-sm"
          :disabled="busy"
          @click="emit('edit', row)"
        >
          Edit
        </button>
        <ConfirmButton
          label="Batalkan"
          confirm-label="Ya, batalkan"
          :busy="busy"
          :described-as="`Batalkan izin keluar ${formatWib(row.leaves_at)}`"
          @confirm="emit('cancel', row)"
        />
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
