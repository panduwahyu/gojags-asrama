<script setup lang="ts">
const route = useRoute()
const { user, session, mutate, errorMessage } = useAsrama()
const open = ref(false)
const error = ref('')
const busy = ref(false)
const menus = computed(() => user.value?.role === 'admin' ? [
  { label: 'Beranda', icon: 'beranda', to: '/admin' },
  { label: 'Kamar', icon: 'kamar', to: '/kamar' },
  { label: 'Laundry', icon: 'laundry', to: '/laundry' },
  { label: 'Catering', icon: 'catering', to: '/catering' },
] : [
  { label: 'Beranda', icon: 'beranda', to: '/user' },
  { label: 'Kamar', icon: 'kamar', to: '/user/kamar' },
  { label: 'Laundry', icon: 'laundry', to: '/user/laundry' },
  { label: 'Catering', icon: 'catering', to: '/user/catering' },
])
watch(() => route.path, () => { open.value = false; error.value = '' })
async function logout() {
  busy.value = true
  try { await mutate('logout'); await navigateTo('/login') } catch (e) { error.value = errorMessage(e) }
  finally { busy.value = false }
}
</script>

<template>
  <div>
    <button class="fixed right-4 top-4 z-50 rounded-lg border border-orange-200 dark:border-orange-800 bg-orange-50 dark:bg-orange-950/40 px-3 py-2 text-orange-700 dark:text-orange-300 lg:hidden" :aria-expanded="open" aria-controls="sidebar" aria-label="Buka navigasi" @click="open = !open">☰</button>
    <button v-if="open" class="fixed inset-0 z-30 bg-slate-900/30 lg:hidden" aria-label="Tutup navigasi" @click="open = false" />
    <aside id="sidebar" :class="open ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-5 py-8 transition-transform lg:translate-x-0">
      <NuxtLink to="/" class="flex items-center gap-3"><img src="/images/gojags.png" alt="Logo Gojags" class="size-12 object-contain"><span class="text-2xl font-extrabold tracking-tight">Asrama<small class="mt-1 block text-[8px] font-semibold tracking-widest text-slate-400 dark:text-slate-400">GOJAGS · PUSDIKLAT BPS</small></span></NuxtLink>
      <p class="mb-4 mt-12 px-4 text-[10px] font-semibold tracking-widest text-slate-400 dark:text-slate-400">MENU UTAMA</p>
      <nav aria-label="Navigasi utama" class="space-y-2"><NuxtLink v-for="menu in menus" :key="menu.to" :to="menu.to" :aria-current="route.path === menu.to ? 'page' : undefined" class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium transition" :class="route.path === menu.to ? 'bg-orange-50 dark:bg-orange-950/40 text-orange-600 dark:text-orange-400' : 'text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800'"><AsramaIcon :name="menu.icon" />{{ menu.label }}<span class="ml-auto opacity-40">›</span></NuxtLink></nav>
      <div class="mt-auto pt-10"><button class="btn w-full" :disabled="busy" @click="logout"><AsramaIcon name="login" />Logout</button></div>
    </aside>
    <div class="lg:ml-64"><header class="flex h-19 items-center justify-between border-b border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-6 md:px-10"><span class="text-sm text-slate-400 dark:text-slate-400">Gojags <b class="font-semibold text-slate-700 dark:text-slate-200">Asrama</b><span class="ml-3 text-xs">{{ user?.role === 'admin' ? 'Admin' : 'Pengguna' }}</span></span><div class="mr-14 flex items-center gap-4 lg:mr-0"><span class="hidden text-[11px] text-slate-500 dark:text-slate-400 md:block lg:mr-0"><span class="mr-2 text-orange-500">●</span>Pusat Pendidikan dan Pelatihan BPS</span><ThemeToggle /></div></header>
      <main class="mx-auto max-w-7xl px-5 py-7 md:px-10"><p class="mb-6 text-xs text-slate-400 dark:text-slate-400">Asrama <span class="px-3">›</span>{{ menus.find(m => m.to === route.path)?.label || 'Login' }}</p><slot /></main>
      <footer class="mt-6 border-t border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-6 pt-9 md:px-10"><div class="mx-auto grid max-w-7xl gap-8 pb-8 sm:grid-cols-[2fr_1fr_1.3fr]"><div><NuxtLink to="/" class="flex items-center gap-2 text-lg font-bold"><img src="/images/gojags.png" alt="Logo Gojags" class="size-9 object-contain">Gojags Asrama</NuxtLink><p class="mt-4 text-xs leading-6 text-slate-400 dark:text-slate-400">Sistem pengelolaan asrama terintegrasi.<br>Mendukung kenyamanan tinggal dan belajar<br>di Pusat Pendidikan dan Pelatihan BPS.</p></div><div><h2 class="mb-4 text-xs font-bold">Jelajahi</h2><NuxtLink v-for="menu in menus" :key="menu.to" :to="menu.to" class="mb-2 block text-xs text-slate-500 dark:text-slate-400 hover:text-orange-600 dark:hover:text-orange-300">{{ menu.label }}</NuxtLink></div><div><h2 class="mb-4 text-xs font-bold">Gojags Asrama</h2><p class="text-xs leading-6 text-slate-400 dark:text-slate-400">Pusat Pendidikan dan Pelatihan<br>Badan Pusat Statistik</p></div></div><div class="flex flex-wrap justify-between gap-2 border-t border-slate-100 dark:border-slate-800 py-5 text-[10px] text-slate-400 dark:text-slate-400"><span>© {{ new Date().getFullYear() }} Gojags Asrama. Pusdiklat BPS.</span><span>Dibuat untuk kenyamanan bersama.</span></div></footer>
    </div>
  </div>
</template>
