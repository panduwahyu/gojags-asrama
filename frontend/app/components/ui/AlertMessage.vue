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
  'mt-4 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50/90 p-4 text-sm text-red-700',
  'dark:border-red-900/60 dark:bg-red-950/60 dark:text-red-200',
].join(' ')

const successClass = [
  'mt-4 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50/90 p-4 text-sm text-emerald-800',
  'dark:border-emerald-900/60 dark:bg-emerald-950/60 dark:text-emerald-200',
].join(' ')
</script>

<template>
  <div
    v-if="error"
    role="alert"
    :class="errorClass"
  >
    <span class="mt-0.5 text-sm leading-none">⚠️</span>
    <div class="flex-1">
      <span>{{ error }}</span>
      <button
        v-if="retryable"
        type="button"
        class="ml-3 font-semibold underline hover:text-red-900 dark:hover:text-red-100"
        @click="emit('retry')"
      >
        Coba lagi
      </button>
    </div>
  </div>
  <div
    v-else-if="success"
    role="status"
    :class="successClass"
  >
    <span class="mt-0.5 text-sm leading-none font-bold">✓</span>
    <div class="flex-1">
      {{ success }}
    </div>
  </div>
</template>
