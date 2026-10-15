<script setup lang="ts">
import type { User } from '~/types/session'

defineProps<{
  user: User
}>()
</script>

<template>
  <div
    class="mt-8 rounded-2xl border border-indigo-200/80 bg-white/80 p-6 shadow-sm
      dark:border-indigo-900/50 dark:bg-slate-900/80"
  >
    <p class="text-sm text-slate-600 dark:text-slate-300">
      Anda sudah login sebagai <b class="font-bold text-slate-900 dark:text-white">{{ user.name }}</b>
      ({{ user.role === 'admin' ? 'Pengelola Asrama' : 'Penghuni Asrama' }}).
    </p>
    <NuxtLink
      :to="homePathFor(user.role)"
      class="mt-4 inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-6 py-3 text-sm font-semibold
        text-white shadow-[0_0_24px_rgba(79,70,229,0.5)] hover:bg-indigo-500"
    >
      Masuk ke Dashboard
      <AppIcon name="arrow" />
    </NuxtLink>
  </div>
</template>
