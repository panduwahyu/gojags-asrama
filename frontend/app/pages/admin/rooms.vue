<script setup lang="ts">
import type { Room, RoomsResponse } from '~/types/room'

useHead({ title: 'Kamar · Gojags Asrama' })

const table = useTable<Room, RoomsResponse>('rooms')
const profileReloadCounter = ref(0)

usePolling(table.refresh, { pausedWhen: () => table.loading.value })

async function onAllocated() {
  profileReloadCounter.value += 1
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
    <RoomTable :table="table" />
    <LeavePermitSection />
    <ComplaintSection />
  </div>
</template>
