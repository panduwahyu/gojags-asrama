<script setup lang="ts">
defineProps<{
  residentName?: string
}>()
</script>

<template>
  <section
    v-if="residentName !== undefined"
    class="rounded-2xl border border-orange-100 bg-gradient-to-br from-orange-50
      to-orange-100/70 p-8 md:p-12 dark:border-slate-700 dark:from-slate-900
      dark:to-slate-800"
  >
    <p class="overline">
      GOJAGS ASRAMA
    </p>
    <h1 class="mt-5 text-3xl font-extrabold leading-tight tracking-tight md:text-5xl">
      Selamat datang di<br>Gojags <span class="text-brand">Asrama.</span>
    </h1>
    <p class="mt-6 text-xl font-semibold text-slate-700 dark:text-slate-200">
      {{ residentName }}
    </p>
  </section>

  <section
    v-else
    class="relative overflow-hidden rounded-2xl border border-orange-100
      bg-gradient-to-br from-orange-50 to-orange-100/70 p-7 md:p-10
      dark:border-slate-700 dark:from-slate-900 dark:to-slate-800"
  >
    <div class="relative z-10 max-w-xl">
      <p class="overline">
        ● NYAMAN TINGGAL, FOKUS BELAJAR
      </p>
      <h1 class="my-5 text-4xl font-extrabold leading-tight tracking-tight md:text-5xl">
        Selamat datang di<br>Gojags <span class="text-brand">Asrama.</span>
      </h1>
      <p class="max-w-md text-sm leading-7 text-amber-900/60 dark:text-slate-300">
        Kelola kebutuhan asrama dengan lebih mudah.<br>
        Mulai dari kamar, laundry, hingga catering dalam satu tempat.
      </p>
      <NuxtLink
        to="/admin/rooms"
        class="btn mt-6"
      >
        Jelajahi kamar
        <AppIcon name="arrow" />
      </NuxtLink>
      <p class="mt-4 text-[10px] text-amber-900/40 dark:text-slate-400">
        Hunian nyaman untuk perjalanan belajar Anda
      </p>
    </div>
    <div
      class="absolute -right-10 top-10 hidden size-80 items-center justify-center
        rounded-full border border-orange-200 text-orange-300 xl:flex
        dark:border-orange-800"
      aria-hidden="true"
    >
      <AppIcon
        name="beranda"
        class="size-48! stroke-1"
      />
    </div>
  </section>
</template>
