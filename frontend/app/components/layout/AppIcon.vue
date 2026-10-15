<script setup lang="ts">
defineProps<{
  name: string
}>()

const paths: Record<string, string> = {
  home: 'm3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1Z',
  room: 'M3 18V6m18 12V9M3 15h18M3 9h7v6m0-6h8a3 3 0 0 1 3 3M3 18v3m18-3v3',
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
  logout: 'M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14 5-5-5-5m5 5H9',
  menu: 'M4 6h16M4 12h16M4 18h16',
  close: 'M18 6 6 18M6 6l12 12',
  warning: [
    'M12 9v4m0 4h.01',
    'M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z',
  ].join(''),
  check: 'M20 6 9 17l-5-5',
  star: 'M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z',
  external: 'M7 17 17 7m0 0H7m10 0v10',
  'sort-asc': 'M12 19V5m-5 6 5-5 5 5',
  'sort-desc': 'M12 5v14m-5-6 5 5 5-5',
  'sort-none': 'M8 9l4-4 4 4m0 6-4 4-4-4',
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
    <path :d="paths[name] || paths.home" />
  </svg>
</template>
