<script setup lang="ts">
import type { TableColumn } from '~/components/ui/DataTable.vue'

const props = defineProps<{
  columns: TableColumn[]
  sort: string
  direction: 'asc' | 'desc'
  loading: boolean
}>()

const emit = defineEmits<{
  sort: [key: string]
}>()

function ariaSort(column: string): 'ascending' | 'descending' | 'none' {
  if (props.sort !== column) {
    return 'none'
  }
  return props.direction === 'asc' ? 'ascending' : 'descending'
}

function sortIndicator(column: string): string {
  if (props.sort !== column) {
    return '↕'
  }
  return props.direction === 'asc' ? '▲' : '▼'
}
</script>

<template>
  <thead>
    <tr
      class="border-b border-slate-200 bg-slate-50/80 text-left text-xs font-semibold uppercase
        tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-400"
    >
      <th
        v-for="col in columns"
        :key="col.key"
        scope="col"
        class="px-4 py-3.5"
        :aria-sort="col.sortable ? ariaSort(col.key) : undefined"
      >
        <button
          v-if="col.sortable"
          type="button"
          class="flex items-center gap-2 text-left font-semibold hover:text-indigo-600 dark:hover:text-indigo-400"
          :disabled="loading"
          @click="emit('sort', col.key)"
        >
          <span>{{ col.label }}</span>
          <span aria-hidden="true" class="text-[10px] text-slate-400">
            {{ sortIndicator(col.key) }}
          </span>
        </button>
        <span v-else>{{ col.label }}</span>
      </th>
    </tr>
  </thead>
</template>
