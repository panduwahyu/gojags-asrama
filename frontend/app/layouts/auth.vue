<script setup lang="ts">
const wrapperClass = [
  'min-h-dvh bg-gradient-to-br from-orange-50 via-slate-50 to-orange-100/50',
  'dark:from-slate-950 dark:via-slate-950 dark:to-slate-900',
].join(' ')
</script>

<template>
  <div :class="wrapperClass">
    <div class="absolute right-5 top-5">
      <ThemeToggle />
    </div>
    <main class="flex min-h-dvh items-center justify-center px-5 py-24">
      <slot />
    </main>
  </div>
</template>
