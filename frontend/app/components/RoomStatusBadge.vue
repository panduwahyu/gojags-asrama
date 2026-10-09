<script setup lang="ts">
defineProps<{ status: string }>()
const colors: Record<string, string> = {
  Kosong: 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300',
  Assigned: 'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300',
  Terisi: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-950 dark:text-yellow-300',
  Penuh: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300',
}
</script>

<template><span class="inline-block rounded-full px-3 py-1 text-xs font-semibold" :class="colors[status]">{{ status }}</span></template>
