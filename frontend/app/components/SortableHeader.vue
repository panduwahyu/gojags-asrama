<script setup lang="ts">
defineProps<{ column: string; label: string; sort: string; direction: string; loading: boolean }>()
defineEmits<{ sort: [column: string] }>()
</script>

<template>
  <th class="p-3" scope="col" :aria-sort="sort === column ? (direction === 'asc' ? 'ascending' : 'descending') : 'none'">
    <button type="button" class="flex items-center gap-2 text-left" :disabled="loading" @click="$emit('sort', column)">{{ label }}<span aria-hidden="true">{{ sort === column ? (direction === 'asc' ? '▲' : '▼') : '↕' }}</span></button>
  </th>
</template>
