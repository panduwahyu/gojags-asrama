<script setup lang="ts">
defineProps<{
  residentName?: string
}>()
</script>

<template>
  <section
    v-if="residentName !== undefined"
    class="relative overflow-hidden rounded-2xl border border-indigo-100/80
      bg-gradient-to-br from-indigo-50/70 via-slate-50 to-indigo-100/50 p-8 md:p-10
      dark:border-indigo-950/60 dark:from-[#0d1424] dark:via-slate-900 dark:to-[#0b101d]"
  >
    <div class="relative z-10 max-w-xl">
      <h1 class="text-3xl font-black leading-tight tracking-tight sm:text-4xl text-slate-900 dark:text-white">
        Selamat datang kembali,<br>
        <span class="text-indigo-600 dark:text-indigo-400">
          {{ residentName }}
        </span>
      </h1>
    </div>
    <div
      class="absolute -bottom-6 -right-6 hidden size-60 items-center justify-center sm:flex"
      aria-hidden="true"
    >
      <div class="relative flex size-full items-center justify-center">
        <div
          class="absolute inset-4 rounded-full bg-gradient-to-tr from-cyan-500/20 via-emerald-500/15
            to-indigo-500/20 blur-2xl"
        />
        <img
          src="/images/gojags.png"
          alt=""
          class="relative size-36 object-contain opacity-85 drop-shadow-xl"
        >
      </div>
    </div>
  </section>

  <section
    v-else
    class="relative overflow-hidden rounded-2xl border border-indigo-100/80
      bg-gradient-to-br from-indigo-50/70 via-slate-50 to-indigo-100/50 p-7 md:p-10
      dark:border-indigo-950/60 dark:from-[#0d1424] dark:via-slate-900 dark:to-[#0b101d]"
  >
    <div class="relative z-10 max-w-xl">
      <h1 class="my-4 text-3xl font-black leading-tight tracking-tight sm:text-5xl text-slate-900 dark:text-white">
        Selamat datang di<br>
        <span class="text-indigo-600 dark:text-indigo-400">
          Gojags Asrama.
        </span>
      </h1>
      <NuxtLink
        to="/admin/rooms"
        class="btn mt-4"
      >
        Jelajahi kamar
        <AppIcon name="arrow" />
      </NuxtLink>
    </div>
    <div
      class="absolute -bottom-8 -right-8 hidden size-72 items-center justify-center lg:flex"
      aria-hidden="true"
    >
      <div class="relative flex size-full items-center justify-center">
        <div
          class="absolute inset-4 rounded-full bg-gradient-to-tr from-cyan-500/20 via-emerald-500/15
            to-indigo-500/20 blur-3xl dark:from-cyan-500/30 dark:via-emerald-500/20 dark:to-indigo-500/30"
        />
        <img
          src="/images/gojags.png"
          alt=""
          class="relative size-44 object-contain opacity-90 drop-shadow-2xl"
        >
      </div>
    </div>
  </section>
</template>
