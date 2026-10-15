<script setup lang="ts">
const wrapperClass = [
  'relative flex min-h-dvh flex-col justify-between overflow-x-hidden',
  'bg-slate-50 text-slate-800 antialiased',
  'dark:bg-[#070b14] dark:text-slate-100',
].join(' ')
</script>

<template>
  <div :class="wrapperClass">
    <!-- Starfield & Ambient Constellation Background (Dark mode) -->
    <div
      class="pointer-events-none absolute inset-0 hidden overflow-hidden dark:block"
      aria-hidden="true"
    >
      <div
        class="absolute -top-40 left-1/2 h-[600px] w-[900px] -translate-x-1/2 rounded-full
          bg-radial from-indigo-600/15 via-sky-600/5 to-transparent blur-3xl"
      />
      <div
        class="absolute -bottom-40 right-10 h-[500px] w-[500px] rounded-full
          bg-radial from-emerald-600/10 via-teal-600/5 to-transparent blur-3xl"
      />
      <!-- Constellation star points and faint connecting lines matching GOJAGS Training -->
      <svg
        class="absolute inset-0 size-full opacity-35"
        xmlns="http://www.w3.org/2000/svg"
      >
        <line x1="8%" y1="18%" x2="22%" y2="34%" stroke="#38bdf8" stroke-width="0.8" opacity="0.35" />
        <line x1="22%" y1="34%" x2="42%" y2="22%" stroke="#818cf8" stroke-width="0.8" opacity="0.35" />
        <line x1="42%" y1="22%" x2="68%" y2="19%" stroke="#ffffff" stroke-width="0.8" opacity="0.25" />
        <line x1="68%" y1="19%" x2="84%" y2="38%" stroke="#38bdf8" stroke-width="0.8" opacity="0.35" />
        <line x1="14%" y1="72%" x2="32%" y2="82%" stroke="#818cf8" stroke-width="0.8" opacity="0.25" />
        <line x1="32%" y1="82%" x2="62%" y2="78%" stroke="#34d399" stroke-width="0.8" opacity="0.3" />
        <line x1="62%" y1="78%" x2="88%" y2="82%" stroke="#f59e0b" stroke-width="0.8" opacity="0.35" />

        <circle cx="8%" cy="18%" r="1.5" fill="#38bdf8" />
        <circle cx="22%" cy="34%" r="2" fill="#818cf8" />
        <circle cx="42%" cy="22%" r="2.5" fill="#ffffff" opacity="0.9" />
        <circle cx="68%" cy="19%" r="1.5" fill="#38bdf8" />
        <circle cx="84%" cy="38%" r="2" fill="#34d399" />
        <circle cx="14%" cy="72%" r="1.5" fill="#ffffff" opacity="0.7" />
        <circle cx="32%" cy="82%" r="2" fill="#818cf8" opacity="0.8" />
        <circle cx="62%" cy="78%" r="2" fill="#38bdf8" />
        <circle cx="88%" cy="82%" r="2" fill="#f59e0b" />
      </svg>
    </div>

    <!-- Top Corporate Branding Header (Matching GOJAGS Training exactly) -->
    <header class="relative z-20 flex items-center justify-between px-6 py-6 md:px-14">
      <div class="flex items-center gap-3">
        <img
          src="/images/bps.svg"
          alt="Logo Badan Pusat Statistik"
          class="h-8 w-auto object-contain md:h-10"
        >
        <div class="flex flex-col leading-tight">
          <span class="text-[11px] font-black tracking-wider text-slate-800 md:text-xs dark:text-white">
            BADAN PUSAT STATISTIK
          </span>
          <span class="text-[9px] font-semibold italic tracking-wide text-slate-500 md:text-[10px] dark:text-slate-300">
            PUSAT PENDIDIKAN DAN PELATIHAN
          </span>
        </div>
      </div>

      <div class="flex items-center gap-4">
        <img
          src="/images/corpu.png"
          alt="Logo Corporate University"
          class="size-8 object-contain md:size-10"
        >
        <div class="hidden text-left leading-tight sm:flex sm:flex-col">
          <span class="text-[11px] font-black tracking-wider text-slate-800 md:text-xs dark:text-white">
            BADAN PUSAT STATISTIK
          </span>
          <span class="text-[9px] font-black tracking-wider text-slate-700 md:text-[10px] dark:text-white">
            CORPORATE UNIVERSITY
          </span>
        </div>
        <ThemeToggle />
      </div>
    </header>

    <!-- Main Auth Content -->
    <main class="relative z-10 flex flex-1 items-center justify-center px-6 py-8 md:px-14">
      <slot />
    </main>

    <!-- Bottom Footer (Matching GOJAGS Training) -->
    <footer class="relative z-20 flex flex-wrap items-center justify-between gap-4 px-6 py-6 md:px-14">
      <p class="text-[11px] text-slate-500 dark:text-slate-400">
        © 2026 GOJAGS ASRAMA · Pusat Pendidikan dan Pelatihan Badan Pusat Statistik
      </p>
      <BerakhlakLogo />
    </footer>
  </div>
</template>
