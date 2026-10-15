<script setup lang="ts">
import type { LaundryOrder, LaundryStatusInput } from '~/types/laundry'

const props = defineProps<{
  order: LaundryOrder
  busy: boolean
}>()

const emit = defineEmits<{
  save: [order: LaundryOrder, input: LaundryStatusInput]
}>()

// Admin hanya memilih Diajukan atau Selesai; pesanan dibatalkan oleh penghuni.
const status = ref<LaundryStatusInput['status']>(props.order.status === 'done' ? 'done' : 'pending')
const cost = ref(props.order.cost ?? '')

// Isian hanya di-reset bila data baris ini sendiri berubah di peladen; ketikan pada baris lain tidak hilang
// saat tabel dimuat ulang (objek baris diganti, nilainya tidak).
watch(
  () => [props.order.status, props.order.cost],
  () => {
    status.value = props.order.status === 'done' ? 'done' : 'pending'
    cost.value = props.order.cost ?? ''
  },
)

function handleSubmit() {
  emit('save', props.order, {
    status: status.value,
    cost: cost.value === '' ? null : Number(cost.value),
  })
}
</script>

<template>
  <p
    v-if="order.status === 'cancelled'"
    class="muted text-xs"
  >
    Dibatalkan penghuni
  </p>

  <form
    v-else
    class="space-y-3"
    @submit.prevent="handleSubmit"
  >
    <label>
      Status
      <select
        v-model="status"
        :disabled="busy"
        :aria-label="`Status ${order.user_name}`"
      >
        <!-- Selesai bersifat final: tidak bisa kembali ke Diajukan. -->
        <option
          value="pending"
          :disabled="order.status === 'done'"
        >
          Diajukan
        </option>
        <option value="done">Selesai</option>
      </select>
    </label>
    <label>
      Biaya laundry (Rp)
      <input
        v-model="cost"
        type="number"
        min="0"
        max="9999999999.99"
        step="0.01"
        :required="status === 'done'"
        :disabled="busy"
        placeholder="Wajib bila status Selesai"
        :aria-label="`Biaya laundry ${order.user_name}`"
      >
    </label>
    <button
      class="btn py-2! text-xs"
      :disabled="busy"
    >
      Simpan
    </button>
  </form>
</template>
