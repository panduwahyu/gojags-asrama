<script setup lang="ts">
import type { Complaint, ComplaintInput } from '~/types/complaint'

const { isAdmin } = useSession()
const api = useApi()
const action = useAction()
const table = useTable<Complaint>('complaints')
const formKey = ref(0)

usePolling(table.refresh, { pausedWhen: () => action.busy.value || table.loading.value })

async function submit(input: ComplaintInput) {
  const saved = await action.run(() => api.post('complaints', input))
  if (saved) {
    formKey.value += 1
    table.goToFirstPage()
  }
  await table.refresh()
}

async function resolve(complaint: Complaint) {
  await action.run(() => api.patch(`complaints/${complaint.id}/resolve`))
  await table.refresh()
}
</script>

<template>
  <section
    aria-label="Keluhan asrama"
    class="card mt-6 p-4 md:p-6"
  >
    <h2 class="section-title">
      Keluhan asrama
    </h2>

    <AlertMessage
      :error="action.error.value"
      :success="action.success.value"
    />

    <ComplaintForm
      v-if="!isAdmin"
      :key="formKey"
      :busy="action.busy.value"
      @submit="submit"
    />

    <ComplaintTable
      :table="table"
      :busy="action.busy.value"
      :admin="isAdmin"
      @resolve="resolve"
    />
  </section>
</template>
