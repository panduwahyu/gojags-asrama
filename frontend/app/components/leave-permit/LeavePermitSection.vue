<script setup lang="ts">
import type { LeavePermit, LeavePermitInput, PermitStatus } from '~/types/leave-permit'

const { isAdmin } = useSession()
const api = useApi()
const action = useAction()
const table = useTable<LeavePermit>('leave-permits')
const editing = ref<LeavePermit | null>(null)

usePolling(table.refresh, { pausedWhen: () => action.busy.value || table.loading.value })

async function save(input: LeavePermitInput) {
  const permit = editing.value
  const saved = await action.run(() =>
    permit ? api.patch(`leave-permits/${permit.id}`, input) : api.post('leave-permits', input),
  )
  if (saved) {
    editing.value = null
    table.goToFirstPage()
  }
  await table.refresh()
}

async function review(permit: LeavePermit, status: PermitStatus) {
  await action.run(() => api.patch(`leave-permits/${permit.id}/review`, { status }))
  await table.refresh()
}

// Admin bisa memproses izin saat penghuni sedang mengeditnya.
watch(table.rows, (rows) => {
  const current = rows.find(row => row.id === editing.value?.id)
  if (current && current.status !== 'pending') {
    editing.value = null
    action.error.value = 'Pengajuan sudah diproses admin dan tidak dapat diedit.'
  }
})
</script>

<template>
  <section
    aria-label="Izin keluar"
    class="card mt-6 p-4 md:p-6"
  >
    <h2 class="section-title">
      Izin keluar
    </h2>
    <p class="muted mt-2">
      {{ isAdmin ? 'Tinjau pengajuan izin keluar penghuni.' : 'Pengajuan dapat diedit selama berstatus Diajukan.' }}
      Semua waktu menggunakan WIB.
    </p>

    <AlertMessage
      :error="action.error.value"
      :success="action.success.value"
    />

    <LeavePermitForm
      v-if="!isAdmin"
      :permit="editing"
      :busy="action.busy.value"
      @save="save"
      @cancel="editing = null"
    />

    <LeavePermitTable
      :table="table"
      :busy="action.busy.value"
      :admin="isAdmin"
      @edit="editing = $event"
      @review="review"
    />
  </section>
</template>
