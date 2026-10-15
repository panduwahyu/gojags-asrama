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
  committedQuery: Ref<string>
  sort: Ref<string>
  direction: Ref<'asc' | 'desc'>
  page: Ref<number>
  perPage: WritableComputedRef<number>
  sortBy: (column: string) => void
  applySearch: () => void
  resetSearch: () => void
  refresh: () => Promise<void>
}

defineProps<{
  table: TableState<T>
  columns: TableColumn[]
  searchLabel: string
  emptyText: string
  /** Path API ekspor (mis. "laundry-orders/export"); bila diisi, tombol unduh Excel/PDF tampil. */
  exportPath?: string
}>()

function cellText(row: T, key: string): string {
  const record: Record<string, unknown> = row
  const value = record[key]
  return value === null || value === undefined ? '' : String(value)
}
</script>

<template>
  <div class="mt-6">
    <TableSearch
      v-model:search="table.search.value"
      :search-label="searchLabel"
      :loading="table.loading.value"
      @submit="table.applySearch()"
      @reset="table.resetSearch()"
    >
      <ExportButtons
        v-if="exportPath"
        :path="exportPath"
        :name="searchLabel"
        :query="{
          q: table.committedQuery.value || undefined,
          sort: table.sort.value,
          direction: table.direction.value,
        }"
      />
    </TableSearch>

    <AlertMessage
      v-if="table.loadError.value"
      error="Gagal memuat data."
      retryable
      @retry="table.refresh()"
    />

    <div class="card mt-4 overflow-hidden overflow-x-auto shadow-xs">
      <table class="w-full border-collapse text-sm">
        <SortableHeader
          :columns="columns"
          :sort="table.sort.value"
          :direction="table.direction.value"
          :loading="table.loading.value"
          @sort="table.sortBy($event)"
        />
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
          <tr
            v-for="row in table.rows.value"
            :key="row.id"
            class="transition-colors hover:bg-indigo-50/30 dark:hover:bg-slate-800/40"
          >
            <td
              v-for="col in columns"
              :key="col.key"
              class="px-4 py-3.5 text-slate-700 dark:text-slate-200"
            >
              <slot
                :name="col.key"
                :row="row"
              >
                {{ cellText(row, col.key) }}
              </slot>
            </td>
          </tr>
          <template v-if="!table.rows.value.length && table.loading.value">
            <tr
              v-for="n in 3"
              :key="`skeleton-${n}`"
              class="animate-pulse"
            >
              <td
                v-for="col in columns"
                :key="col.key"
                class="px-4 py-3.5"
              >
                <div class="h-4 w-3/4 rounded bg-slate-200 dark:bg-slate-700/60" />
              </td>
            </tr>
          </template>
          <tr v-else-if="!table.rows.value.length">
            <td
              :colspan="columns.length"
              class="p-8 text-center"
            >
              <p class="muted text-sm">
                {{ emptyText }}
              </p>
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
