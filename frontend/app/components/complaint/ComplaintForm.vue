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
    class="mt-6 space-y-4 rounded-2xl border border-slate-100 bg-slate-50/70 p-5
      dark:border-slate-800 dark:bg-slate-900/40"
    @submit.prevent="send"
  >
    <div class="border-b border-slate-200/80 pb-3 dark:border-slate-800">
      <h3 class="text-sm font-bold text-slate-900 dark:text-white">
        Kirim keluhan baru
      </h3>
    </div>

    <label>
      Judul keluhan
      <input
        v-model="form.title"
        maxlength="150"
        required
        placeholder="Contoh: AC kurang dingin / kran bocor"
        :disabled="busy"
      >
    </label>
    <label>
      Isi keluhan
      <textarea
        v-model="form.body"
        rows="3"
        maxlength="2000"
        required
        placeholder="Jelaskan keluhan dan lokasi detail yang perlu ditangani."
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
