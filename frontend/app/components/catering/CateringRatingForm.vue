<script setup lang="ts">
import type { CateringMenu } from '~/types/catering'

const props = defineProps<{
  menu: CateringMenu
  busy: boolean
}>()

const emit = defineEmits<{
  rate: [menu: CateringMenu, rating: number]
}>()

const rating = ref<number>(props.menu.my_rating ?? 0)

watch(
  () => props.menu.my_rating,
  (value) => {
    rating.value = value ?? 0
  },
)

function handleSubmit() {
  if (!rating.value) {
    return
  }
  emit('rate', props.menu, rating.value)
}
</script>

<template>
  <form
    class="space-y-3"
    @submit.prevent="handleSubmit"
  >
    <label :for="`rating-${menu.id}`">
      Rating untuk Menu {{ menu.menu_number }}
    </label>
    <select
      :id="`rating-${menu.id}`"
      v-model.number="rating"
      :disabled="busy"
      required
    >
      <option
        :value="0"
        disabled
      >
        Pilih rating
      </option>
      <option
        v-for="score in 5"
        :key="score"
        :value="score"
      >
        {{ score }} ★
      </option>
    </select>
    <button
      class="btn"
      :disabled="busy || !rating"
    >
      Simpan rating
    </button>
  </form>
</template>
