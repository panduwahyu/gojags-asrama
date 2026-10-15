<script setup lang="ts">
defineProps<{
  to: string
  icon: string
  overline: string
  title: string
  description: string
  linkLabel: string
  color: string
  index: number
}>()
</script>

<template>
  <NuxtLink
    :to="to"
    class="card overflow-hidden transition hover:-translate-y-1 hover:shadow-lg
      hover:shadow-slate-200/40 dark:hover:shadow-black/30"
  >
    <div
      class="relative flex h-36 items-center justify-center"
      :class="color"
    >
      <div class="absolute size-24 rounded-full border border-current opacity-20" />
      <AppIcon
        :name="icon"
        class="size-14! stroke-1"
      />
      <span class="absolute right-4 top-3 text-xs opacity-60">0{{ index + 1 }}</span>
    </div>
    <div class="p-6">
      <p class="overline">
        {{ overline }}
      </p>
      <h2 class="mt-2 text-sm font-bold text-slate-900 dark:text-white">
        {{ title }}
      </h2>
      <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">
        {{ description }}
      </p>
      <div
        class="mt-5 flex items-center justify-between border-t border-slate-100
          pt-4 text-xs font-semibold text-indigo-600 dark:border-slate-800
          dark:text-indigo-400"
      >
        {{ linkLabel }}
        <AppIcon name="arrow" />
      </div>
    </div>
  </NuxtLink>
</template>
