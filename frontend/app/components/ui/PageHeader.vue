<script setup lang="ts">
withDefaults(
  defineProps<{
    title: string
    description?: string
    overline?: string
  }>(),
  {
    overline: 'LAYANAN ASRAMA',
    description: '',
  },
)
</script>

<template>
  <section class="card relative overflow-hidden p-6 md:p-8">
    <div class="relative z-10">
      <span class="overline">{{ overline }}</span>
      <h1 class="mt-2 text-2xl font-black tracking-tight text-slate-900 md:text-3xl dark:text-white">
        {{ title }}
      </h1>
      <p
        v-if="description"
        class="muted mt-2 max-w-3xl text-sm"
      >
        {{ description }}
      </p>
    </div>
    <div
      class="pointer-events-none absolute -bottom-6 -right-6 size-36 rounded-full
        bg-radial from-indigo-500/10 to-transparent blur-2xl"
      aria-hidden="true"
    />
  </section>
</template>
