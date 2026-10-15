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
    class="mt-6 space-y-4 rounded-2xl border border-slate-100 bg-slate-50/70 p-5
      dark:border-slate-800 dark:bg-slate-900/40"
    @submit.prevent="submit"
  >
    <div class="flex items-center justify-between border-b border-slate-200/80 pb-3 dark:border-slate-800">
      <h3 class="text-sm font-bold text-slate-900 dark:text-white">
        {{ permit ? 'Edit pengajuan izin' : 'Ajukan izin keluar' }}
      </h3>
      <span
        v-if="permit"
        class="text-xs font-semibold text-indigo-600 dark:text-indigo-400"
      >
        Mode Penyuntingan
      </span>
    </div>

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
        placeholder="Tuliskan keperluan keluar asrama..."
      />
    </label>

    <div class="flex items-center gap-3 pt-1">
      <button
        class="btn"
        :disabled="busy"
      >
        {{ permit ? 'Simpan perubahan izin' : 'Ajukan izin' }}
      </button>
      <button
        v-if="permit"
        type="button"
        class="btn-text px-3 py-2"
        :disabled="busy"
        @click="emit('cancel')"
      >
        Batal edit
      </button>
    </div>
  </form>
</template>
