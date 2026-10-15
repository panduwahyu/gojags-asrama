<script setup lang="ts">
import type { LaundryOrder, LaundryOrderInput, LaundryStatusInput } from '~/types/laundry'

const { isAdmin } = useSession()
const api = useApi()
const action = useAction()
const table = useTable<LaundryOrder>('laundry-orders')
const formKey = ref(0)

usePolling(table.refresh, {
  pausedWhen: () => isAdmin.value || action.busy.value || table.loading.value,
})

async function submit(input: LaundryOrderInput) {
  const saved = await action.run(() => api.post('laundry-orders', input))
  if (saved) {
    formKey.value += 1
    table.goToFirstPage()
  }
  await table.refresh()
}

async function updateStatus(order: LaundryOrder, input: LaundryStatusInput) {
  await action.run(() => api.patch(`laundry-orders/${order.id}`, input))
  await table.refresh()
}
</script>

<template>
  <div>
    <PageHeader
      overline="LAYANAN ASRAMA"
      title="Laundry"
    />
    <AlertMessage
      :error="action.error.value"
      :success="action.success.value"
    />
    <LaundryForm
      v-if="!isAdmin"
      :key="formKey"
      :busy="action.busy.value"
      @submit="submit"
    />
    <LaundryTable
      :table="table"
      :busy="action.busy.value"
      :admin="isAdmin"
      @save="updateStatus"
    />
  </div>
</template>
