<script setup lang="ts">
import type { Credential } from '~/types/room'

const props = defineProps<{
  credentials: Credential[]
}>()

const emit = defineEmits<{
  dismiss: []
}>()

function quote(value: string): string {
  return `"${value.replaceAll('"', '""')}"`
}

function downloadCsv() {
  const lines = ['Nama,Email,Sandi awal', ...props.credentials.map(
    item => [item.name, item.email, item.password].map(quote).join(','),
  )]
  const url = URL.createObjectURL(new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8' }))
  const link = document.createElement('a')
  link.href = url
  link.download = 'akun-penghuni.csv'
  link.click()
  URL.revokeObjectURL(url)
}
</script>

<template>
  <section
    aria-label="Sandi awal akun penghuni"
    role="status"
    class="mt-6 rounded-2xl border border-amber-200 bg-amber-50/80 p-5 text-amber-950
      dark:border-amber-800/60 dark:bg-amber-950/40 dark:text-amber-100"
  >
    <h2 class="text-base font-bold">
      Sandi awal akun penghuni
    </h2>
    <p class="mt-1 text-xs">
      Sandi hanya ditampilkan sekali. Catat atau unduh sekarang, lalu berikan kepada penghuni; mereka dapat
      menggantinya lewat menu Akun.
    </p>

    <div class="mt-3 overflow-x-auto">
      <table class="w-full border-collapse text-sm">
        <thead>
          <tr class="text-left text-xs uppercase tracking-wide">
            <th class="px-2 py-1.5">
              Nama
            </th>
            <th class="px-2 py-1.5">
              Email
            </th>
            <th class="px-2 py-1.5">
              Sandi awal
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="item in credentials"
            :key="item.email"
            class="border-t border-amber-200/70 dark:border-amber-800/50"
          >
            <td class="px-2 py-1.5">
              {{ item.name }}
            </td>
            <td class="px-2 py-1.5">
              {{ item.email }}
            </td>
            <td class="px-2 py-1.5 font-mono font-semibold">
              {{ item.password }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="mt-4 flex flex-wrap gap-3">
      <button
        type="button"
        class="btn-secondary btn-sm"
        @click="downloadCsv"
      >
        <AppIcon name="download" class="size-3.5" />
        Unduh CSV
      </button>
      <button
        type="button"
        class="btn-text py-1! px-2! text-xs"
        @click="emit('dismiss')"
      >
        Sembunyikan
      </button>
    </div>
  </section>
</template>
