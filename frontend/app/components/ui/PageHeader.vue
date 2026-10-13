<script setup lang="ts">
defineProps<{
  title: string
  description?: string
}>()
</script>

<template>
  <section class="card p-6 md:p-8">
    <span class="overline">LAYANAN ASRAMA</span>
    <h1 class="mt-2 text-2xl font-extrabold tracking-tight md:text-3xl">
      {{ title }}
    </h1>
    <p
      v-if="description"
      class="muted mt-2 max-w-3xl"
    >
      {{ description }}
    </p>
  </section>
</template>
