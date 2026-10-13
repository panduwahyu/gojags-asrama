<script setup lang="ts" generic="T extends { id: number }">
export type TableColumn = {
  key: string
  label: string
  sortable?: boolean
}

type TableState<Row> = {
  rows: Ref<Row[]>
  meta: Ref<{ current_page: number; last_page: number; total: number } | undefined>
  loading: Ref<boolean>
  loadError: Ref<boolean>
  search: Ref<string>
  sort: Ref<string>
  direction: Ref<'asc' | 'desc'>
  page: Ref<number>
  perPage: WritableComputedRef<number>
  sortBy: (column: string) => void
  applySearch: () => void
  resetSearch: () => void
  refresh: () => Promise<void>
}

const props = defineProps<{
  table: TableState<T>
  columns: TableColumn[]
  searchLabel: string
  emptyText: string
}>()

function ariaSort(column: string): 'ascending' | 'descending' | 'none' {
  if (props.table.sort.value !== column) {
    return 'none'
  }
  return props.table.direction.value === 'asc' ? 'ascending' : 'descending'
}

function sortIndicator(column: string): string {
  if (props.table.sort.value !== column) {
    return '↕'
  }
  return props.table.direction.value === 'asc' ? '▲' : '▼'
}

function cellText(row: T, key: string): string {
  const record: Record<string, unknown> = row
  const value = record[key]
  return value === null || value === undefined ? '' : String(value)
}
</script>

<template>
  <div class="mt-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <form
        class="flex flex-wrap gap-2"
        @submit.prevent="table.applySearch()"
      >
        <input
          v-model="table.search.value"
          type="search"
          class="sm:w-64!"
          :aria-label="searchLabel"
          :placeholder="`${searchLabel}…`"
        >
        <button
          class="btn py-2.5!"
          :disabled="table.loading.value"
        >
          Cari
        </button>
        <button
          v-if="table.search.value"
          type="button"
          class="btn-text px-2"
          @click="table.resetSearch()"
        >
          Reset
        </button>
      </form>
      <span
        v-if="table.loading.value"
        role="status"
        aria-live="polite"
        class="muted text-xs"
      >
        Memuat…
      </span>
    </div>

    <AlertMessage
      v-if="table.loadError.value"
      error="Gagal memuat data."
      retryable
      @retry="table.refresh()"
    />

    <div class="mt-4 overflow-x-auto">
      <table class="w-full border-collapse text-sm">
        <thead>
          <tr
            class="border-b border-slate-200 text-left text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400"
          >
            <th
              v-for="col in columns"
              :key="col.key"
              scope="col"
              class="p-3"
              :aria-sort="col.sortable ? ariaSort(col.key) : undefined"
            >
              <button
                v-if="col.sortable"
                type="button"
                class="flex items-center gap-2 text-left"
                :disabled="table.loading.value"
                @click="table.sortBy(col.key)"
              >
                {{ col.label }}
                <span aria-hidden="true">{{ sortIndicator(col.key) }}</span>
              </button>
              <span v-else>{{ col.label }}</span>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="row in table.rows.value"
            :key="row.id"
            class="border-b border-slate-100 dark:border-slate-800/70"
          >
            <td
              v-for="col in columns"
              :key="col.key"
              class="p-3"
            >
              <slot
                :name="col.key"
                :row="row"
              >
                {{ cellText(row, col.key) }}
              </slot>
            </td>
          </tr>
          <tr v-if="!table.rows.value.length && !table.loading.value">
            <td
              :colspan="columns.length"
              class="muted p-3"
            >
              {{ emptyText }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <TablePagination
      v-if="table.meta.value"
      v-model:page="table.page.value"
      v-model:per-page="table.perPage.value"
      :current="table.meta.value.current_page"
      :last="table.meta.value.last_page"
      :loading="table.loading.value"
    />
  </div>
</template>
