<script setup lang="ts">
defineProps<{
  label: string
  for: string
}>()
</script>

<template>
  <div>
    <label
      :for="$props.for"
      class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200"
    >
      {{ label }}
    </label>
    <slot />
  </div>
</template>
