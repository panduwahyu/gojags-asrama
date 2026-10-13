<script setup lang="ts">
import type { LaundryOrder, LaundryStatusInput } from '~/types/laundry'
import { LAUNDRY_TONE } from '~/types/laundry'

const props = defineProps<{
  table: ReturnType<typeof useTable<LaundryOrder>>
  busy: boolean
  admin: boolean
}>()

const emit = defineEmits<{
  save: [order: LaundryOrder, input: LaundryStatusInput]
}>()

const columns = computed(() => [
  ...(props.admin ? [{ key: 'user_name', label: 'Nama user', sortable: true }] : []),
  { key: 'items', label: 'Pakaian / barang', sortable: true },
  { key: 'set_count', label: 'Jumlah stel', sortable: true },
  { key: 'created_at', label: 'Tanggal', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'cost', label: 'Biaya laundry', sortable: true },
  ...(props.admin ? [{ key: 'actions', label: 'Perbarui status' }] : []),
])

function onSave(order: LaundryOrder, input: LaundryStatusInput) {
  emit('save', order, input)
}
</script>

<template>
  <section
    aria-label="Pengajuan laundry"
    class="card p-4 md:p-6"
  >
    <h2 class="mb-5 text-xl font-semibold">
      {{ admin ? 'Pengajuan laundry user' : 'Riwayat pengajuan laundry' }}
    </h2>
    <DataTable
      :table="table"
      :columns="columns"
      search-label="Cari laundry"
      empty-text="Belum ada pengajuan laundry."
    >
      <template #items="{ row }">
        <span class="min-w-40 whitespace-pre-wrap break-words">{{ row.items }}</span>
      </template>

      <template #created_at="{ row }">
        <span class="whitespace-nowrap">{{ formatWib(row.created_at) }}</span>
      </template>

      <template #status="{ row }">
        <StatusBadge :tone="LAUNDRY_TONE[row.status]">
          {{ row.status_label }}
        </StatusBadge>
      </template>

      <template #cost="{ row }">
        <span class="whitespace-nowrap">{{ formatRupiah(row.cost) }}</span>
      </template>

      <template #actions="{ row }">
        <div class="min-w-52">
          <LaundryStatusForm
            :order="row"
            :busy="busy"
            @save="onSave"
          />
        </div>
      </template>
    </DataTable>
  </section>
</template>
