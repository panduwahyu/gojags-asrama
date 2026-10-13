<script setup lang="ts">
import type { LeavePermit, LeavePermitInput } from '~/types/leave-permit'

const props = defineProps<{
  permit: LeavePermit | null
  busy: boolean
}>()

const emit = defineEmits<{
  save: [input: LeavePermitInput]
  cancel: []
}>()

const form = reactive({ leavesAt: '', returnsAt: '', reason: '' })

watch(() => props.permit, (permit) => {
  form.leavesAt = permit ? isoToWibInput(permit.leaves_at) : ''
  form.returnsAt = permit ? isoToWibInput(permit.returns_at) : ''
  form.reason = permit?.reason ?? ''
}, { immediate: true })

function submit() {
  emit('save', {
    leaves_at: wibInputToIso(form.leavesAt),
    returns_at: wibInputToIso(form.returnsAt),
    reason: form.reason,
  })
}
</script>

<template>
  <form
    class="mt-5 space-y-4"
    @submit.prevent="submit"
  >
    <h3 class="font-semibold">
      {{ permit ? 'Edit pengajuan izin' : 'Ajukan izin keluar' }}
    </h3>

    <div class="grid gap-4 sm:grid-cols-2">
      <label>
        Waktu keluar (WIB)
        <input
          v-model="form.leavesAt"
          type="datetime-local"
          required
          :disabled="busy"
        >
      </label>
      <label>
        Waktu kembali (WIB)
        <input
          v-model="form.returnsAt"
          type="datetime-local"
          required
          :min="form.leavesAt || undefined"
          :disabled="busy"
        >
      </label>
    </div>

    <label>
      Alasan
      <textarea
        v-model="form.reason"
        rows="3"
        maxlength="2000"
        required
        :disabled="busy"
      />
    </label>

    <div class="flex gap-3">
      <button
        class="btn"
        :disabled="busy"
      >
        {{ permit ? 'Simpan perubahan izin' : 'Ajukan izin' }}
      </button>
      <button
        v-if="permit"
        type="button"
        class="btn-text"
        :disabled="busy"
        @click="emit('cancel')"
      >
        Batal edit
      </button>
    </div>
  </form>
</template>
