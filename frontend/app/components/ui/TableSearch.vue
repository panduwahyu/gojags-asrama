<script setup lang="ts">
defineProps<{
  searchLabel: string
  loading: boolean
}>()

const search = defineModel<string>('search', { default: '' })

const emit = defineEmits<{
  submit: []
  reset: []
}>()
</script>

<template>
  <div class="flex flex-wrap items-center justify-between gap-3">
    <form
      class="flex flex-wrap items-center gap-2"
      @submit.prevent="emit('submit')"
    >
      <div class="relative">
        <input
          v-model="search"
          type="search"
          class="sm:w-72! pr-3"
          :aria-label="searchLabel"
          :placeholder="`${searchLabel}…`"
        >
      </div>
      <button
        class="btn py-2.5!"
        :disabled="loading"
        :aria-busy="loading"
      >
        Cari
      </button>
      <button
        v-if="search"
        type="button"
        class="btn-text rounded-lg px-2 py-1.5 focus-visible:outline-hidden focus-visible:ring-2
          focus-visible:ring-indigo-500"
        @click="emit('reset')"
      >
        Reset
      </button>
    </form>
    <span
      v-if="loading"
      aria-live="polite"
      class="muted text-xs font-medium animate-pulse"
    >
      Memuat data…
    </span>
  </div>
</template>
