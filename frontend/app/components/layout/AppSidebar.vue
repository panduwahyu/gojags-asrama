<script setup lang="ts">
import type { Session } from '~/types/session'

const emit = defineEmits<{
  error: [message: string]
}>()

const route = useRoute()
const { user, apply } = useSession()
const api = useApi()
const action = useAction()
const open = ref(false)

const menus = computed(() => menusFor(user.value?.role))

const toggleButtonClass = [
  'fixed right-4 top-4 z-50 rounded-xl border border-indigo-200 bg-indigo-50',
  'px-3 py-2 text-indigo-700 lg:hidden',
  'dark:border-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-300',
].join(' ')

const asideBaseClass = [
  'fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-slate-200/80',
  'bg-white px-5 py-8 transition-transform lg:translate-x-0',
  'dark:border-slate-800/80 dark:bg-[#0d1424]',
].join(' ')

function linkClass(path: string): string {
  if (route.path === path) {
    return [
      'border-l-2 border-indigo-600 bg-indigo-50/90 font-semibold text-indigo-700 shadow-xs',
      'dark:border-indigo-400 dark:bg-indigo-600 dark:text-white dark:shadow-[0_0_15px_rgba(79,70,229,0.35)]',
    ].join(' ')
  }
  return [
    'text-slate-500 hover:bg-slate-50 hover:text-slate-900',
    'dark:text-slate-400 dark:hover:bg-slate-800/60 dark:hover:text-slate-200',
  ].join(' ')
}

async function logout() {
  let nextSession: Session | null = null
  const ok = await action.run(async () => {
    nextSession = await api.post<Session>('logout')
  })
  if (ok && nextSession) {
    apply(nextSession)
    await navigateTo('/login')
  } else if (action.error.value) {
    emit('error', action.error.value)
  }
}

watch(() => route.path, () => {
  open.value = false
  emit('error', '')
})
</script>

<template>
  <div>
    <button
      :class="toggleButtonClass"
      :aria-expanded="open"
      aria-controls="sidebar"
      aria-label="Buka navigasi"
      @click="open = !open"
    >
      ☰
    </button>
    <button
      v-if="open"
      class="fixed inset-0 z-30 bg-slate-900/30 lg:hidden"
      aria-label="Tutup navigasi"
      @click="open = false"
    />
    <aside
      id="sidebar"
      :class="[asideBaseClass, open ? 'translate-x-0' : '-translate-x-full']"
    >
      <NuxtLink
        to="/"
        class="flex items-center gap-3.5"
      >
        <div class="relative flex size-11 items-center justify-center">
          <div class="absolute inset-0 rounded-full bg-gradient-to-tr from-cyan-500/20 to-indigo-500/20 blur-xs" />
          <img
            src="/images/gojags.png"
            alt="Logo Gojags"
            class="relative size-10 object-contain drop-shadow-sm"
          >
        </div>
        <div class="flex flex-col">
          <span class="text-xl font-black tracking-tight text-slate-900 dark:text-white">
            GOJAGS <span class="text-indigo-600 dark:text-indigo-400">ASRAMA</span>
          </span>
          <small class="text-[9px] font-semibold tracking-wider text-slate-400">
            PUSDIKLAT BPS
          </small>
        </div>
      </NuxtLink>
      <p class="mb-4 mt-10 px-4 text-[10px] font-semibold tracking-widest text-slate-400">
        MENU UTAMA
      </p>
      <nav
        aria-label="Navigasi utama"
        class="space-y-1.5"
      >
        <NuxtLink
          v-for="menu in menus"
          :key="menu.to"
          :to="menu.to"
          :aria-current="route.path === menu.to ? 'page' : undefined"
          class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition"
          :class="linkClass(menu.to)"
        >
          <AppIcon :name="menu.icon" />
          {{ menu.label }}
          <span class="ml-auto opacity-40">›</span>
        </NuxtLink>
      </nav>
      <div class="mt-auto pt-8">
        <button
          class="flex w-full items-center justify-center gap-2.5 rounded-xl border border-slate-200/80
            bg-slate-50 px-4 py-2.5 text-xs font-semibold text-slate-600 transition-colors
            hover:border-rose-300 hover:bg-rose-50 hover:text-rose-600
            dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-300
            dark:hover:border-rose-900/50 dark:hover:bg-rose-950/30 dark:hover:text-rose-400"
          :disabled="action.busy.value"
          @click="logout"
        >
          <AppIcon name="login" class="size-4" />
          Logout
        </button>
      </div>
    </aside>
  </div>
</template>
