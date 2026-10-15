<script setup lang="ts">
const props = withDefaults(defineProps<{
  label: string
  confirmLabel?: string
  busy?: boolean
  /** Nama yang dibacakan pembaca layar, mis. "Hapus menu 3". */
  describedAs?: string
}>(), {
  confirmLabel: 'Ya',
  busy: false,
  describedAs: undefined,
})

const emit = defineEmits<{
  confirm: []
}>()

const confirming = ref(false)

function confirm() {
  confirming.value = false
  emit('confirm')
}
</script>

<template>
  <span class="inline-flex items-center gap-2">
    <button
      v-if="!confirming"
      type="button"
      class="btn-danger btn-sm"
      :disabled="busy"
      :aria-label="props.describedAs"
      @click="confirming = true"
    >
      {{ label }}
    </button>
    <template v-else>
      <button
        type="button"
        class="btn-danger btn-sm"
        :disabled="busy"
        @click="confirm"
      >
        {{ confirmLabel }}
      </button>
      <button
        type="button"
        class="btn-text py-1! px-2! text-xs"
        :disabled="busy"
        @click="confirming = false"
      >
        Batal
      </button>
    </template>
  </span>
</template>
