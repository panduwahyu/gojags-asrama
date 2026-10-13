<script setup lang="ts">
import type { Room, RoomsResponse } from '~/types/room'

definePageMeta({ role: 'admin' })
useHead({ title: 'Kamar · Gojags Asrama' })

const table = useTable<Room, RoomsResponse>('rooms')
const profileReloadCounter = ref(0)

usePolling(table.refresh, { pausedWhen: () => table.loading.value })

const description = computed(() => {
  const total = table.meta.value?.total ?? 78
  return `${total} kamar di lantai 3, 4, dan 5. Kapasitas dua penghuni per kamar.`
})

async function onAllocated() {
  profileReloadCounter.value += 1
  await table.refresh()
}
</script>

<template>
  <div>
    <PageHeader
      title="Kamar"
      :description="description"
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
