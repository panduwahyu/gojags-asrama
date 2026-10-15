<script setup lang="ts">
import type { LaundryOrderInput } from '~/types/laundry'

defineProps<{
  busy: boolean
}>()

const emit = defineEmits<{
  submit: [input: LaundryOrderInput]
}>()

const form = reactive({
  items: '',
  set_count: '',
})

function handleSubmit() {
  emit('submit', {
    items: form.items,
    set_count: Number(form.set_count),
  })
}
</script>

<template>
  <section class="card mb-6 p-6 md:p-8">
    <div class="flex items-center gap-3.5">
      <div
        class="flex size-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600
          dark:bg-indigo-950/50 dark:text-indigo-400"
      >
        <AppIcon name="laundry" class="size-6" />
      </div>
      <div>
        <h2 class="text-xl font-bold text-slate-900 dark:text-white">
          Ajukan laundry
        </h2>
      </div>
    </div>

    <form
      class="mt-6 space-y-4"
      @submit.prevent="handleSubmit"
    >
      <label>
        Pakaian / barang yang dilaundry
        <textarea
          v-model="form.items"
          required
          maxlength="255"
          rows="3"
          :disabled="busy"
          placeholder="Contoh: kemeja, celana, dan seragam"
          class="rounded-xl border border-slate-200 bg-white p-3 outline-none
            focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 dark:border-slate-800
            dark:bg-slate-900/90 dark:text-slate-100 dark:focus:border-indigo-500"
        />
      </label>
      <label>
        Jumlah stel
        <input
          v-model="form.set_count"
          type="number"
          min="1"
          max="1000"
          step="1"
          required
          :disabled="busy"
        >
      </label>
      <button
        class="btn mt-2"
        :disabled="busy"
      >
        {{ busy ? 'Memproses...' : 'Ajukan laundry' }}
      </button>
    </form>
  </section>
</template>
