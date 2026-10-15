<script setup lang="ts">
import type { CateringMenu, CateringMenuInput, MealTime } from '~/types/catering'

const props = defineProps<{
  menu?: CateringMenu | null
  busy: boolean
}>()

const emit = defineEmits<{
  submit: [input: CateringMenuInput]
  cancel: []
}>()

const form = reactive({
  menu_number: '',
  dish_name: '',
  meal_time: 'lunch' as MealTime,
  provider: '',
  served_at: '',
})

watch(
  () => props.menu,
  (menu) => {
    form.menu_number = menu ? String(menu.menu_number) : ''
    form.dish_name = menu?.dish_name ?? ''
    form.meal_time = menu?.meal_time ?? 'lunch'
    form.provider = menu?.provider ?? ''
    form.served_at = menu ? isoToWibInput(menu.served_at) : ''
  },
  { immediate: true },
)

function handleSubmit() {
  emit('submit', {
    menu_number: Number(form.menu_number),
    dish_name: form.dish_name,
    meal_time: form.meal_time,
    provider: form.provider,
    served_at: wibInputToIso(form.served_at),
  })
}
</script>

<template>
  <section class="card mb-6 p-6 md:p-8">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3.5">
        <div
          class="flex size-11 items-center justify-center rounded-xl bg-green-50 text-green-600
            dark:bg-green-950/50 dark:text-green-400"
        >
          <AppIcon name="catering" class="size-6" />
        </div>
        <h2 class="text-xl font-bold text-slate-900 dark:text-white">
          {{ menu ? 'Edit menu catering' : 'Tambah menu catering' }}
        </h2>
      </div>
      <span v-if="menu" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400">
        Mode Penyuntingan
      </span>
    </div>

    <form
      class="mt-6 space-y-4"
      @submit.prevent="handleSubmit"
    >
      <div class="grid gap-4 sm:grid-cols-3">
        <FormField label="Nomor menu" for="catering-menu-number">
          <input
            id="catering-menu-number"
            v-model="form.menu_number"
            type="number"
            min="1"
            max="10000"
            step="1"
            required
            :disabled="busy"
          >
        </FormField>
        <FormField label="Nama makanan" for="catering-dish-name">
          <input
            id="catering-dish-name"
            v-model="form.dish_name"
            maxlength="255"
            required
            :disabled="busy"
            placeholder="Contoh: Ayam Bakar Madu"
          >
        </FormField>
        <FormField label="Waktu makan" for="catering-meal-time">
          <select
            id="catering-meal-time"
            v-model="form.meal_time"
            required
            :disabled="busy"
          >
            <option value="breakfast">Pagi</option>
            <option value="lunch">Siang</option>
            <option value="dinner">Malam</option>
          </select>
        </FormField>
      </div>

      <div class="grid gap-4 sm:grid-cols-2">
        <FormField label="Penyedia" for="catering-provider">
          <input
            id="catering-provider"
            v-model="form.provider"
            maxlength="255"
            required
            :disabled="busy"
          >
        </FormField>
        <FormField label="Waktu catering (WIB)" for="catering-served-at">
          <input
            id="catering-served-at"
            v-model="form.served_at"
            type="datetime-local"
            required
            :disabled="busy"
          >
        </FormField>
      </div>

      <div class="flex items-center gap-3 pt-2">
        <button
          class="btn"
          :disabled="busy"
        >
          {{ busy ? 'Memproses...' : (menu ? 'Simpan perubahan' : 'Tambah menu') }}
        </button>
        <button
          v-if="menu"
          type="button"
          class="btn-text px-3 py-2"
          :disabled="busy"
          @click="emit('cancel')"
        >
          Batal edit
        </button>
      </div>
    </form>
  </section>
</template>
