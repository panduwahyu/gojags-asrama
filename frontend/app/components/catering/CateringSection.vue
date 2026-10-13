<script setup lang="ts">
import type { CateringMenu, CateringMenuInput } from '~/types/catering'

const props = defineProps<{
  admin?: boolean
}>()

const { isAdmin } = useSession()
const adminMode = computed(() => props.admin ?? isAdmin.value)
const api = useApi()
const action = useAction()
const table = useTable<CateringMenu>('catering-menus')
const formKey = ref(0)

usePolling(table.refresh, {
  pausedWhen: () => action.busy.value || table.loading.value,
})

async function create(input: CateringMenuInput) {
  const saved = await action.run(() => api.post('catering-menus', input))
  if (saved) {
    formKey.value += 1
    table.goToFirstPage()
  }
  await table.refresh()
}

async function rate(menu: CateringMenu, rating: number) {
  await action.run(() => api.put(`catering-menus/${menu.id}/rating`, { rating }))
  await table.refresh()
}
</script>

<template>
  <div>
    <PageHeader
      overline="LAYANAN ASRAMA"
      title="Catering"
    />
    <AlertMessage
      :error="action.error.value"
      :success="action.success.value"
    />
    <CateringMenuForm
      v-if="adminMode"
      :key="formKey"
      :busy="action.busy.value"
      @submit="create"
    />
    <CateringTable
      :table="table"
      :busy="action.busy.value"
      :admin="adminMode"
      @rate="rate"
    />
  </div>
</template>
