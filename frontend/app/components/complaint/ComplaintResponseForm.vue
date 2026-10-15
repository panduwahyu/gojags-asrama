<script setup lang="ts">
import type { Complaint } from '~/types/complaint'

const props = defineProps<{
  complaint: Complaint
  busy: boolean
}>()

const emit = defineEmits<{
  progress: [complaint: Complaint, response: string]
  resolve: [complaint: Complaint, response: string]
}>()

const response = ref(props.complaint.response ?? '')

// Hanya ikut data peladen bila tanggapan baris ini sendiri yang berubah; ketikan belum disimpan tidak hilang.
watch(
  () => props.complaint.response,
  (value) => {
    response.value = value ?? ''
  },
)
</script>

<template>
  <form
    class="min-w-52 space-y-2"
    @submit.prevent
  >
    <label :for="`complaint-response-${complaint.id}`">Tanggapan petugas</label>
    <textarea
      :id="`complaint-response-${complaint.id}`"
      v-model="response"
      rows="2"
      maxlength="2000"
      :disabled="busy"
      placeholder="Tulis balasan untuk pelapor"
    />
    <div class="flex flex-wrap gap-2">
      <button
        v-if="complaint.status === 'pending'"
        type="button"
        class="btn-secondary btn-sm whitespace-nowrap"
        :disabled="busy"
        @click="emit('progress', complaint, response)"
      >
        Tandai sedang ditangani
      </button>
      <button
        type="button"
        class="btn btn-sm whitespace-nowrap"
        :disabled="busy"
        @click="emit('resolve', complaint, response)"
      >
        Tandai Resolved
      </button>
    </div>
  </form>
</template>
