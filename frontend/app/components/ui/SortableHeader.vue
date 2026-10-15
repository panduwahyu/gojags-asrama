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

function sortIcon(column: string): string {
  if (props.sort !== column) {
    return 'sort-none'
  }
  return props.direction === 'asc' ? 'sort-asc' : 'sort-desc'
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
          class="flex items-center gap-1.5 text-left font-semibold transition hover:text-indigo-600
            focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500
            dark:hover:text-indigo-400"
          :disabled="loading"
          @click="emit('sort', col.key)"
        >
          <span>{{ col.label }}</span>
          <AppIcon
            :name="sortIcon(col.key)"
            class="size-3.5 transition-colors"
            :class="props.sort === col.key
              ? 'text-indigo-600 dark:text-indigo-400'
              : 'text-slate-400 opacity-60'"
          />
        </button>
        <span v-else>{{ col.label }}</span>
      </th>
    </tr>
  </thead>
</template>
