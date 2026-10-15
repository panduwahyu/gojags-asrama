<script setup lang="ts">
const { user } = useSession()
const menus = computed(() => menusFor(user.value?.role))
const currentYear = new Date().getFullYear()

const footerClass = [
  'mt-6 border-t border-slate-200/80 bg-white px-6 pt-9 md:px-10',
  'dark:border-slate-800/80 dark:bg-[#0d1424]',
].join(' ')

const linkClass = [
  'mb-2 block text-xs text-slate-500 hover:text-indigo-600 transition-colors',
  'dark:text-slate-400 dark:hover:text-indigo-400',
].join(' ')

const bottomBarClass = [
  'flex flex-wrap justify-between gap-2 border-t border-slate-100 py-5',
  'text-[10px] text-slate-400 dark:border-slate-800',
].join(' ')
</script>

<template>
  <footer :class="footerClass">
    <div class="mx-auto flex flex-wrap items-center justify-between gap-6 pb-8">
      <div>
        <NuxtLink
          to="/"
          class="flex items-center gap-2.5 text-base font-bold text-slate-900 dark:text-white"
        >
          <img
            src="/images/gojags.png"
            alt="Logo Gojags"
            class="size-8 object-contain"
          >
          <span>GOJAGS <span class="text-indigo-600 dark:text-indigo-400">ASRAMA</span></span>
        </NuxtLink>
        <p class="mt-1 text-xs text-slate-400">
          Pusat Pendidikan dan Pelatihan BPS
        </p>
      </div>
      <nav
        aria-label="Navigasi footer"
        class="flex flex-wrap items-center gap-6"
      >
        <NuxtLink
          v-for="menu in menus"
          :key="menu.to"
          :to="menu.to"
          class="text-xs text-slate-500 transition-colors hover:text-indigo-600 dark:text-slate-400
            dark:hover:text-indigo-400"
        >
          {{ menu.label }}
        </NuxtLink>
      </nav>
    </div>
    <div :class="bottomBarClass">
      <span>© {{ currentYear }} Gojags Asrama · Pusat Pendidikan dan Pelatihan BPS</span>
      <BerakhlakLogo />
    </div>
  </footer>
</template>
