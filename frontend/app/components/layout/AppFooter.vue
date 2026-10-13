<script setup lang="ts">
const { user } = useSession()
const menus = computed(() => menusFor(user.value?.role))
const currentYear = new Date().getFullYear()

const footerClass = [
  'mt-6 border-t border-slate-200 bg-white px-6 pt-9 md:px-10',
  'dark:border-slate-700 dark:bg-slate-900',
].join(' ')

const linkClass = [
  'mb-2 block text-xs text-slate-500 hover:text-orange-600',
  'dark:text-slate-400 dark:hover:text-orange-300',
].join(' ')

const bottomBarClass = [
  'flex flex-wrap justify-between gap-2 border-t border-slate-100 py-5',
  'text-[10px] text-slate-400 dark:border-slate-800',
].join(' ')
</script>

<template>
  <footer :class="footerClass">
    <div class="mx-auto grid max-w-7xl gap-8 pb-8 sm:grid-cols-[2fr_1fr_1.3fr]">
      <div>
        <NuxtLink
          to="/"
          class="flex items-center gap-2 text-lg font-bold"
        >
          <img
            src="/images/gojags.png"
            alt="Logo Gojags"
            class="size-9 object-contain"
          >
          Gojags Asrama
        </NuxtLink>
        <p class="mt-4 text-xs leading-6 text-slate-400">
          Sistem pengelolaan asrama terintegrasi.<br>
          Mendukung kenyamanan tinggal dan belajar<br>
          di Pusat Pendidikan dan Pelatihan BPS.
        </p>
      </div>
      <div>
        <h2 class="mb-4 text-xs font-bold">
          Jelajahi
        </h2>
        <NuxtLink
          v-for="menu in menus"
          :key="menu.to"
          :to="menu.to"
          :class="linkClass"
        >
          {{ menu.label }}
        </NuxtLink>
      </div>
      <div>
        <h2 class="mb-4 text-xs font-bold">
          Gojags Asrama
        </h2>
        <p class="text-xs leading-6 text-slate-400">
          Pusat Pendidikan dan Pelatihan<br>
          Badan Pusat Statistik
        </p>
      </div>
    </div>
    <div :class="bottomBarClass">
      <span>© {{ currentYear }} Gojags Asrama. Pusdiklat BPS.</span>
      <span>Dibuat untuk kenyamanan bersama.</span>
    </div>
  </footer>
</template>
