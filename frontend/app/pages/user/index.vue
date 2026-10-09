<script setup lang="ts">
import type { User } from '~/composables/useAsrama'
definePageMeta({ role: 'user' })
useHead({ title: 'Beranda · Gojags Asrama' })
const { data } = await useFetch<{ user: User }>('/backend/api/asrama/welcome')
</script>

<template>
  <section class="rounded-2xl border border-orange-100 bg-gradient-to-br from-orange-50 to-orange-100/70 p-8 md:p-12 dark:border-slate-700 dark:from-slate-900 dark:to-slate-800">
    <p class="overline">GOJAGS ASRAMA</p>
    <h1 class="mt-5 text-3xl font-extrabold leading-tight tracking-tight md:text-5xl">Selamat datang di<br>Gojags <span class="text-brand">Asrama.</span></h1>
    <p class="mt-6 text-xl font-semibold text-slate-700 dark:text-slate-200">{{ data?.user.name }}</p>
  </section>
</template>
