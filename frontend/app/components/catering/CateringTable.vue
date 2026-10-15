<script setup lang="ts">
import type { CateringMenu } from '~/types/catering'

const props = defineProps<{
  table: ReturnType<typeof useTable<CateringMenu>>
  busy: boolean
  admin: boolean
}>()

const emit = defineEmits<{
  rate: [menu: CateringMenu, rating: number]
  edit: [menu: CateringMenu]
  delete: [menu: CateringMenu]
}>()

const confirmingDeleteId = ref<number | null>(null)

const columns = computed(() => [
  { key: 'menu_number', label: 'Menu', sortable: true },
  { key: 'dish_name', label: 'Nama makanan', sortable: true },
  { key: 'meal_time', label: 'Waktu makan', sortable: true },
  { key: 'provider', label: 'Penyedia', sortable: true },
  { key: 'served_at', label: 'Waktu catering', sortable: true },
  { key: 'rating_average', label: 'Rata-rata rating', sortable: true },
  { key: 'rating_count', label: 'Jumlah penilai', sortable: true },
  ...(!props.admin
    ? [{ key: 'my_rating', label: 'Rating saya', sortable: true }, { key: 'actions', label: 'Beri rating' }]
    : [{ key: 'actions', label: 'Aksi' }]),
])

function onRate(menu: CateringMenu, rating: number) {
  emit('rate', menu, rating)
}

function onDelete(menu: CateringMenu) {
  confirmingDeleteId.value = null
  emit('delete', menu)
}
</script>

<template>
  <section
    aria-label="Menu catering"
    class="card p-4 md:p-6"
  >
    <h2 class="text-xl font-semibold">
      Menu dan rating catering
    </h2>
    <p class="muted mb-5 mt-2 text-sm">
      Rating menggunakan skala 1–5 bintang. Hasil menampilkan rata-rata dan jumlah penilai.
    </p>
    <DataTable
      :table="table"
      :columns="columns"
      search-label="Cari catering"
      empty-text="Belum ada menu catering."
    >
      <template #menu_number="{ row }">
        <span class="whitespace-nowrap font-semibold">Menu {{ row.menu_number }}</span>
      </template>

      <template #dish_name="{ row }">
        <span class="font-medium text-slate-900 dark:text-white">{{ row.dish_name }}</span>
      </template>

      <template #meal_time="{ row }">
        <span class="whitespace-nowrap">{{ row.meal_time_label }}</span>
      </template>

      <template #served_at="{ row }">
        <span class="whitespace-nowrap">{{ formatWib(row.served_at) }}</span>
      </template>

      <template #rating_average="{ row }">
        <span
          v-if="row.rating_average !== null"
          class="whitespace-nowrap font-semibold text-amber-500 dark:text-amber-400"
        >
          {{ row.rating_average.toLocaleString('id-ID', { maximumFractionDigits: 2 }) }} / 5 ★
        </span>
        <span
          v-else
          class="muted whitespace-nowrap"
        >
          Belum ada rating
        </span>
      </template>

      <template #my_rating="{ row }">
        <span
          class="whitespace-nowrap"
          :class="row.my_rating ? 'font-semibold text-amber-500 dark:text-amber-400' : ''"
        >
          {{ row.my_rating ? `${row.my_rating} / 5 ★` : 'Belum menilai' }}
        </span>
      </template>

      <template #actions="{ row }">
        <div v-if="admin" class="flex items-center gap-2">
          <template v-if="confirmingDeleteId !== row.id">
            <button
              type="button"
              class="btn-secondary py-1! px-2.5! text-xs"
              :disabled="busy"
              @click="emit('edit', row)"
            >
              Edit
            </button>
            <button
              type="button"
              class="btn-danger py-1! px-2.5! text-xs"
              :disabled="busy"
              @click="confirmingDeleteId = row.id"
            >
              Hapus
            </button>
          </template>
          <template v-else>
            <button
              type="button"
              class="btn-danger py-1! px-2.5! text-xs font-semibold"
              :disabled="busy"
              @click="onDelete(row)"
            >
              Ya, hapus
            </button>
            <button
              type="button"
              class="btn-text py-1! px-2! text-xs"
              :disabled="busy"
              @click="confirmingDeleteId = null"
            >
              Batal
            </button>
          </template>
        </div>
        <div v-else class="min-w-40">
          <CateringRatingForm
            :menu="row"
            :busy="busy"
            @rate="onRate"
          />
        </div>
      </template>
    </DataTable>
  </section>
</template>
