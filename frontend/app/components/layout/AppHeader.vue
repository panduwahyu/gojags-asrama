<script setup lang="ts">
const { user } = useSession()

const headerClass = [
  'flex h-19 items-center justify-between border-b border-slate-200',
  'bg-white px-6 md:px-10 dark:border-slate-700 dark:bg-slate-900',
].join(' ')
</script>

<template>
  <header :class="headerClass">
    <span class="text-sm text-slate-400">
      Gojags
      <b class="font-semibold text-slate-700 dark:text-slate-200">Asrama</b>
      <span class="ml-3 text-xs">
        {{ user?.role === 'admin' ? 'Admin' : 'Pengguna' }}
      </span>
    </span>
    <div class="mr-14 flex items-center gap-4 lg:mr-0">
      <span class="hidden text-[11px] text-slate-500 md:block lg:mr-0 dark:text-slate-400">
        <span class="mr-2 text-orange-500">●</span>
        Pusat Pendidikan dan Pelatihan BPS
      </span>
      <ThemeToggle />
    </div>
  </header>
</template>
