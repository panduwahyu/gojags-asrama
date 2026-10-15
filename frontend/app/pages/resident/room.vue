<script setup lang="ts">
import type { MyRoomData } from '~/types/room'

useHead({ title: 'Kamar saya · Gojags Asrama' })

const api = useApi()
const data = shallowRef<MyRoomData | null>(null)
const loadError = ref(false)

async function loadRoom() {
  loadError.value = false
  try {
    const response = await api.get<{ data: MyRoomData }>('my-room')
    data.value = response.data
  } catch {
    loadError.value = true
  }
}

onMounted(() => {
  loadRoom()
})
</script>

<template>
  <div>
    <PageHeader
      overline="INFORMASI HUNIAN"
      title="Kamar saya"
    />
    <AlertMessage
      v-if="loadError"
      error="Data kamar tidak dapat dimuat."
      retryable
      @retry="loadRoom()"
    />
    <MyRoomCard
      :room="data?.room ?? null"
      :loaded="Boolean(data)"
      @changed="loadRoom()"
    />
    <StayHistory
      v-if="data"
      :stays="data.stays"
    />
    <LeavePermitSection />
    <ComplaintSection />
  </div>
</template>
