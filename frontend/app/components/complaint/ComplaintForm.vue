<script setup lang="ts">
import type { ComplaintInput } from '~/types/complaint'

defineProps<{
  busy: boolean
}>()

const emit = defineEmits<{
  submit: [input: ComplaintInput]
}>()

const form = reactive({
  title: '',
  body: '',
})

function send() {
  emit('submit', {
    title: form.title,
    body: form.body,
  })
}
</script>

<template>
  <form
    class="mt-5 space-y-4"
    @submit.prevent="send"
  >
    <label>
      Judul keluhan
      <input
        v-model="form.title"
        maxlength="150"
        required
        :disabled="busy"
      >
    </label>
    <label>
      Isi keluhan
      <textarea
        v-model="form.body"
        rows="4"
        maxlength="2000"
        required
        placeholder="Jelaskan keluhan dan lokasi yang perlu ditangani."
        :disabled="busy"
      />
    </label>
    <button
      class="btn"
      :disabled="busy"
    >
      {{ busy ? 'Memproses…' : 'Kirim keluhan' }}
    </button>
  </form>
</template>
