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
const editingMenu = ref<CateringMenu | null>(null)

usePolling(table.refresh, {
  pausedWhen: () => action.busy.value || table.loading.value,
})

async function saveMenu(input: CateringMenuInput) {
  if (editingMenu.value) {
    const updated = await action.run(() => api.patch(`catering-menus/${editingMenu.value!.id}`, input))
    if (updated) {
      editingMenu.value = null
    }
  } else {
    const saved = await action.run(() => api.post('catering-menus', input))
    if (saved) {
      formKey.value += 1
      table.goToFirstPage()
    }
  }
  await table.refresh()
}

function startEdit(menu: CateringMenu) {
  editingMenu.value = menu
  if (typeof window !== 'undefined') {
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

function cancelEdit() {
  editingMenu.value = null
}

async function deleteMenu(menu: CateringMenu) {
  if (editingMenu.value?.id === menu.id) {
    editingMenu.value = null
  }
  await action.run(() => api.delete(`catering-menus/${menu.id}`))
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
      :menu="editingMenu"
      :busy="action.busy.value"
      @submit="saveMenu"
      @cancel="cancelEdit"
    />
    <CateringTable
      :table="table"
      :busy="action.busy.value"
      :admin="adminMode"
      @rate="rate"
      @edit="startEdit"
      @delete="deleteMenu"
    />
  </div>
</template>
