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
  <section class="card mb-6 p-6">
    <h2 class="text-xl font-semibold">
      Ajukan laundry
    </h2>
    <p class="muted mt-2 text-sm">
      Tuliskan pakaian atau barang yang akan dilaundry dan total jumlah stel.
    </p>
    <form
      class="mt-5 space-y-4"
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
          class="rounded-lg border border-slate-200 bg-white p-3 outline-none
            focus:border-orange-400 dark:border-slate-700 dark:bg-slate-800"
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
        class="btn"
        :disabled="busy"
      >
        {{ busy ? 'Memproses...' : 'Ajukan laundry' }}
      </button>
    </form>
  </section>
</template>
