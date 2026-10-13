<script setup lang="ts">
import type { CateringMenuInput } from '~/types/catering'

defineProps<{
  busy: boolean
}>()

const emit = defineEmits<{
  submit: [input: CateringMenuInput]
}>()

const form = reactive({
  menu_number: '',
  provider: '',
  served_at: '',
})

function handleSubmit() {
  emit('submit', {
    menu_number: Number(form.menu_number),
    provider: form.provider,
    served_at: wibInputToIso(form.served_at),
  })
}
</script>

<template>
  <section class="card mb-6 p-6">
    <h2 class="text-xl font-semibold">
      Tambah menu catering
    </h2>
    <form
      class="mt-5 space-y-4"
      @submit.prevent="handleSubmit"
    >
      <div class="grid gap-4 sm:grid-cols-3">
        <label>
          Nomor menu
          <input
            v-model="form.menu_number"
            type="number"
            min="1"
            max="10000"
            step="1"
            required
            :disabled="busy"
          >
        </label>
        <label>
          Penyedia
          <input
            v-model="form.provider"
            maxlength="255"
            required
            :disabled="busy"
          >
        </label>
        <label>
          Waktu catering (WIB)
          <input
            v-model="form.served_at"
            type="datetime-local"
            required
            :disabled="busy"
          >
        </label>
      </div>
      <button
        class="btn"
        :disabled="busy"
      >
        {{ busy ? 'Memproses...' : 'Tambah menu' }}
      </button>
    </form>
  </section>
</template>
