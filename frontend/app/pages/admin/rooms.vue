<script setup lang="ts">
import type { Room, RoomsResponse } from '~/types/room'

useHead({ title: 'Kamar · Gojags Asrama' })

const api = useApi()
const action = useAction()
const table = useTable<Room, RoomsResponse>('rooms')
const profileReloadCounter = ref(0)

usePolling(table.refresh, { pausedWhen: () => action.busy.value || table.loading.value })

async function onAllocated() {
  profileReloadCounter.value += 1
  await table.refresh()
}

async function toggleRoom(room: Room) {
  await action.run(() => api.patch(`rooms/${room.id}`, { is_active: !room.is_active }))
  await table.refresh()
}
</script>

<template>
  <div>
    <PageHeader
      overline="MANAJEMEN HUNIAN"
      title="Kamar"
    />
    <RoomMonitoring
      v-if="table.response.value?.monitoring"
      :monitoring="table.response.value.monitoring"
    />
    <RoomAllocation
      :disabled="table.loadError.value"
      @allocated="onAllocated"
    />
    <ResidentProfiles :reload-trigger="profileReloadCounter" />
    <AlertMessage
      :error="action.error.value"
      :success="action.success.value"
    />
    <RoomTable
      :table="table"
      :busy="action.busy.value"
      @toggle="toggleRoom"
    />
    <LeavePermitSection />
    <ComplaintSection />
  </div>
</template>
