<script setup lang="ts">
import type { Credential } from '~/types/room'

const emit = defineEmits<{
  imported: [credentials: Credential[]]
}>()

const api = useApi()
const action = useAction()
const file = ref<File | null>(null)
const fileKey = ref(0)
const rowErrors = ref<string[]>([])

function pick(event: Event) {
  file.value = (event.target as HTMLInputElement).files?.[0] ?? null
}

async function upload() {
  if (!file.value) {
    return
  }
  const body = new FormData()
  body.append('file', file.value)
  rowErrors.value = []

  const ok = await action.run(async () => {
    try {
      const result = await api.post<{ message: string; data: Credential[] }>('residents/import', body)
      emit('imported', result.data)
      return result
    } catch (error) {
      rowErrors.value = validationMessages(error, 'file')
      throw error
    }
  })

  if (ok) {
    file.value = null
    fileKey.value += 1
  }
}
</script>

<template>
  <form
    class="mt-4 space-y-3"
    @submit.prevent="upload"
  >
    <p class="muted text-xs">
      Berkas Excel (.xlsx, .xls) atau CSV. Baris pertama berisi judul kolom:
      <b>Nama, Email, Angkatan, Jenis Kelamin, Agama</b> (L/P; agama: Islam, Kristen, Katolik, Hindu, Buddha,
      Konghucu). Maksimal 500 penghuni; bila ada satu baris yang tidak valid, tidak ada akun yang dibuat.
    </p>

    <FormField label="Berkas penghuni" for="import-file">
      <input
        id="import-file"
        :key="fileKey"
        type="file"
        accept=".xlsx,.xls,.csv,text/csv"
        :disabled="action.busy.value"
        @change="pick"
      >
    </FormField>

    <AlertMessage
      :error="rowErrors.length > 1 ? '' : action.error.value"
      :success="action.success.value"
    />

    <div
      v-if="rowErrors.length > 1"
      role="alert"
      class="rounded-xl border border-rose-200 bg-rose-50/80 p-3.5 text-sm text-rose-900
        dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-200"
    >
      <p class="font-bold">
        Impor dibatalkan. Perbaiki baris berikut lalu unggah ulang:
      </p>
      <ul class="mt-1 max-h-48 list-inside list-disc overflow-y-auto text-xs">
        <li
          v-for="message in rowErrors"
          :key="message"
        >
          {{ message }}
        </li>
      </ul>
    </div>

    <button
      class="btn"
      :disabled="action.busy.value || !file"
    >
      {{ action.busy.value ? 'Mengimpor…' : 'Impor penghuni' }}
    </button>
  </form>
</template>
