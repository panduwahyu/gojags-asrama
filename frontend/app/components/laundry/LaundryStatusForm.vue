<script setup lang="ts">
import type { LaundryOrder, LaundryStatus, LaundryStatusInput } from '~/types/laundry'

const props = defineProps<{
  order: LaundryOrder
  busy: boolean
}>()

const emit = defineEmits<{
  save: [order: LaundryOrder, input: LaundryStatusInput]
}>()

const status = ref<LaundryStatus>(props.order.status)
const cost = ref(props.order.cost ?? '')

watch(
  () => props.order,
  (order) => {
    status.value = order.status
    cost.value = order.cost ?? ''
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
  <form
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
        <option value="pending">Diajukan</option>
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
