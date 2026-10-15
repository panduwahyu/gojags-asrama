<script setup lang="ts">
const buttonClass = [
  'inline-flex shrink-0 items-center gap-2 rounded-full border border-slate-200 bg-slate-50',
  'px-3 py-2 text-xs font-medium text-slate-600 transition hover:bg-indigo-50 hover:text-indigo-600',
  'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500',
  'dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-200 dark:hover:bg-slate-800',
].join(' ')

const sunPath = [
  'M12 2v2m0 16v2M2 12h2m16 0h2',
  'M5 5l1.4 1.4m11.2 11.2L19 19M5 19l1.4-1.4M17.6 6.4 19 5',
].join('')

onMounted(() => {
  window.dispatchEvent(new Event('asrama:sync-theme'))
})
</script>

<template>
  <button
    type="button"
    data-theme-toggle
    aria-label="Aktifkan mode malam"
    aria-pressed="false"
    title="Aktifkan mode malam"
    :class="buttonClass"
  >
    <svg
      class="size-4 dark:hidden"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="1.7"
      aria-hidden="true"
    >
      <path d="M20.9 13A9 9 0 0 1 11 3.1 9 9 0 1 0 20.9 13Z" />
    </svg>
    <svg
      class="hidden size-4 dark:block"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="1.7"
      stroke-linecap="round"
      aria-hidden="true"
    >
      <circle
        cx="12"
        cy="12"
        r="4"
      />
      <path :d="sunPath" />
    </svg>
    <span class="max-sm:sr-only dark:hidden">Mode malam</span>
    <span class="hidden max-sm:sr-only dark:inline">Mode siang</span>
  </button>
</template>
