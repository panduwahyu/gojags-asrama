<script setup lang="ts">
const { user } = useSession()

const headerClass = [
  'flex h-19 items-center justify-between border-b border-slate-200/80',
  'bg-white px-6 md:px-10 dark:border-slate-800/80 dark:bg-[#0d1424]',
].join(' ')
</script>

<template>
  <header :class="headerClass">
    <div class="flex items-center gap-3">
      <span class="text-sm text-slate-400">
        Gojags
        <b class="font-semibold text-slate-700 dark:text-slate-200">Asrama</b>
      </span>
      <span
        class="rounded-full border border-indigo-200 bg-indigo-50 px-2.5 py-0.5 text-[10px] font-semibold
          text-indigo-700 dark:border-indigo-800/60 dark:bg-indigo-950/50 dark:text-indigo-300"
      >
        {{ user?.role === 'admin' ? 'Admin' : 'Penghuni' }}
      </span>
    </div>

    <div class="mr-14 flex items-center gap-4 lg:mr-0">
      <div class="hidden items-center gap-2.5 text-[11px] text-slate-500 md:flex dark:text-slate-400">
        <img
          src="/images/corpu.png"
          alt="BPS Corporate University"
          class="size-6 object-contain"
        >
        <span>Pusdiklat BPS Corporate University</span>
      </div>

      <ThemeToggle />

      <div
        v-if="user"
        class="flex items-center gap-2.5 border-l border-slate-200 pl-3 dark:border-slate-800"
      >
        <div
          class="flex size-8 items-center justify-center rounded-full bg-gradient-to-tr from-indigo-600 to-sky-500
            text-xs font-bold text-white shadow-xs"
        >
          {{ user.name.charAt(0).toUpperCase() }}
        </div>
        <div class="hidden flex-col text-left text-xs leading-tight sm:flex">
          <span class="font-bold text-slate-800 dark:text-white">
            {{ user.role === 'admin' ? 'Admin Asrama' : 'Warga Asrama' }}
          </span>
          <span class="text-[10px] text-slate-400">
            {{ user.email }}
          </span>
        </div>
      </div>
    </div>
  </header>
</template>
