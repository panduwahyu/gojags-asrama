<script setup lang="ts">
defineProps<{
  error?: string
  success?: string
  retryable?: boolean
}>()

const emit = defineEmits<{
  retry: []
}>()

const errorClass = [
  'mt-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700',
  'dark:border-red-900/60 dark:bg-red-950/60 dark:text-red-200',
].join(' ')

const successClass = [
  'mt-4 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800',
  'dark:border-green-900/60 dark:bg-green-950/60 dark:text-green-200',
].join(' ')
</script>

<template>
  <div
    v-if="error"
    role="alert"
    :class="errorClass"
  >
    <span>{{ error }}</span>
    <button
      v-if="retryable"
      type="button"
      class="ml-3 font-semibold underline"
      @click="emit('retry')"
    >
      Coba lagi
    </button>
  </div>
  <div
    v-else-if="success"
    role="status"
    :class="successClass"
  >
    {{ success }}
  </div>
</template>
