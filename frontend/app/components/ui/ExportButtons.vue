<script setup lang="ts">
const props = defineProps<{
  /** Path API tanpa awalan, mis. "laundry-orders/export". */
  path: string
  /** Filter tabel (q, sort, direction) agar unduhan sama dengan yang tampil. */
  query?: Record<string, string | undefined>
  /** Nama dasar berkas bila peladen tidak memberi nama. */
  name: string
}>()

const api = useApi()
const action = useAction()

async function download(format: 'xlsx' | 'pdf') {
  await action.run(async () => {
    await api.download(props.path, { ...props.query, format }, `${props.name}.${format}`)
  })
}
</script>

<template>
  <div class="flex flex-wrap items-center gap-2">
    <button
      type="button"
      class="btn-secondary btn-sm"
      :disabled="action.busy.value"
      @click="download('xlsx')"
    >
      <AppIcon name="download" class="size-3.5" />
      Unduh Excel
    </button>
    <button
      type="button"
      class="btn-secondary btn-sm"
      :disabled="action.busy.value"
      @click="download('pdf')"
    >
      <AppIcon name="download" class="size-3.5" />
      Unduh PDF
    </button>
    <span
      v-if="action.error.value"
      role="alert"
      class="text-xs text-rose-600 dark:text-rose-400"
    >
      {{ action.error.value }}
    </span>
  </div>
</template>
