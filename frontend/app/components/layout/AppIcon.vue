<script setup lang="ts">
defineProps<{
  name: string
}>()

const paths: Record<string, string> = {
  beranda: 'm3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1Z',
  kamar: 'M3 18V6m18 12V9M3 15h18M3 9h7v6m0-6h8a3 3 0 0 1 3 3M3 18v3m18-3v3',
  laundry: [
    'M6 2h12a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Z',
    'M7 6h1m3 0h6m0 8a5 5 0 1 1-10 0 5 5 0 0 1 10 0Zm-9 0c3-3 5 3 8 0',
  ].join(''),
  catering: 'M4 3v6a3 3 0 0 0 6 0V3M7 3v18M20 21V3c-5 2-5 10 0 10',
  arrow: 'M4 12h16m-6-6 6 6-6 6',
  eye: 'M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Zm13 0a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
  'eye-off': [
    'M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z',
    'm13 0a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM3 3l18 18',
  ].join(''),
  login: 'M13 3h7v18h-7M3 12h12m-5-5 5 5-5 5',
}
</script>

<template>
  <svg
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.7"
    stroke-linecap="round"
    stroke-linejoin="round"
    class="size-5 shrink-0"
    aria-hidden="true"
  >
    <path :d="paths[name] || paths.beranda" />
  </svg>
</template>
