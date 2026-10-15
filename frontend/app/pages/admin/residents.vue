<script setup lang="ts">
import type { Credential, Resident, ResidentInput, Room, RoomsResponse } from '~/types/room'

useHead({ title: 'Penghuni · Gojags Asrama' })

const api = useApi()
const action = useAction()
const residents = ref<Resident[]>([])
const rooms = ref<Room[]>([])
const credentials = ref<Credential[]>([])
const accountFormKey = ref(0)
const loadError = ref(false)

async function load() {
  try {
    const [residentList, roomList] = await Promise.all([
      api.get<{ data: Resident[] }>('residents'),
      api.get<RoomsResponse>('rooms', { per_page: 100, sort: 'number', direction: 'asc' }),
    ])
    residents.value = residentList.data
    rooms.value = roomList.data
    loadError.value = false
  } catch {
    loadError.value = true
  }
}

usePolling(load, { pausedWhen: () => action.busy.value })
onMounted(load)

async function createAccount(input: ResidentInput) {
  const ok = await action.run(async () => {
    const result = await api.post<{ message: string; data: Credential }>('residents', input)
    credentials.value = [result.data]
    return result
  })
  if (ok) {
    accountFormKey.value += 1
  }
  await load()
}

async function onImported(created: Credential[]) {
  credentials.value = created
  await load()
}

async function run(task: () => Promise<{ message: string }>) {
  await action.run(task)
  await load()
}

const unassign = (resident: Resident) => run(() => api.delete(`residents/${resident.id}/room`))
const checkOut = (resident: Resident) => run(() => api.post(`residents/${resident.id}/check-out`))
const cancelCheckOut = (resident: Resident) => run(() => api.post(`residents/${resident.id}/cancel-check-out`))
const resetPassword = (resident: Resident) => run(async () => {
  const result = await api.post<{ message: string; data: Credential }>(`residents/${resident.id}/reset-password`)
  credentials.value = [result.data]
  return result
})
</script>

<template>
  <div>
    <PageHeader
      overline="MANAJEMEN HUNIAN"
      title="Penghuni"
    />

    <AlertMessage
      v-if="loadError"
      error="Data penghuni tidak dapat dimuat."
      retryable
      @retry="load()"
    />
    <AlertMessage
      :error="action.error.value"
      :success="action.success.value"
    />

    <CredentialsPanel
      v-if="credentials.length"
      :credentials="credentials"
      @dismiss="credentials = []"
    />

    <section
      aria-label="Tambah akun penghuni"
      class="card mt-6 p-6"
    >
      <h2 class="section-title">
        Tambah akun penghuni
      </h2>
      <ResidentAccountForm
        :key="accountFormKey"
        :busy="action.busy.value"
        @create="createAccount"
      />
    </section>

    <section
      aria-label="Impor akun penghuni"
      class="card mt-6 p-6"
    >
      <h2 class="section-title">
        Impor dari Excel atau CSV
      </h2>
      <ResidentImportForm @imported="onImported" />
    </section>

    <section
      aria-label="Penempatan manual"
      class="card mt-6 p-6"
    >
      <h2 class="section-title">
        Penempatan manual
      </h2>
      <PlacementPanel
        :residents="residents"
        :rooms="rooms"
        @changed="load()"
      />
    </section>

    <section
      aria-label="Daftar penghuni"
      class="card mt-6 p-6"
    >
      <h2 class="section-title">
        Daftar penghuni ({{ residents.length }})
      </h2>
      <ResidentTable
        :residents="residents"
        :busy="action.busy.value"
        @unassign="unassign"
        @check-out="checkOut"
        @cancel-check-out="cancelCheckOut"
        @reset-password="resetPassword"
      />
    </section>
  </div>
</template>
