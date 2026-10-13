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
  'fixed right-4 top-4 z-50 rounded-lg border border-orange-200 bg-orange-50',
  'px-3 py-2 text-orange-700 lg:hidden',
  'dark:border-orange-800 dark:bg-orange-950/40 dark:text-orange-300',
].join(' ')

const asideBaseClass = [
  'fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-slate-200',
  'bg-white px-5 py-8 transition-transform lg:translate-x-0',
  'dark:border-slate-700 dark:bg-slate-900',
].join(' ')

function linkClass(path: string): string {
  if (route.path === path) {
    return 'bg-orange-50 text-orange-600 dark:bg-orange-950/40 dark:text-orange-400'
  }
  return 'text-slate-500 hover:bg-slate-50 dark:text-slate-400 dark:hover:bg-slate-800'
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
        class="flex items-center gap-3"
      >
        <img
          src="/images/gojags.png"
          alt="Logo Gojags"
          class="size-12 object-contain"
        >
        <span class="text-2xl font-extrabold tracking-tight">
          Asrama
          <small class="mt-1 block text-[8px] font-semibold tracking-widest text-slate-400">
            GOJAGS · PUSDIKLAT BPS
          </small>
        </span>
      </NuxtLink>
      <p class="mb-4 mt-12 px-4 text-[10px] font-semibold tracking-widest text-slate-400">
        MENU UTAMA
      </p>
      <nav
        aria-label="Navigasi utama"
        class="space-y-2"
      >
        <NuxtLink
          v-for="menu in menus"
          :key="menu.to"
          :to="menu.to"
          :aria-current="route.path === menu.to ? 'page' : undefined"
          class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium transition"
          :class="linkClass(menu.to)"
        >
          <AppIcon :name="menu.icon" />
          {{ menu.label }}
          <span class="ml-auto opacity-40">›</span>
        </NuxtLink>
      </nav>
      <div class="mt-auto pt-10">
        <button
          class="btn w-full"
          :disabled="action.busy.value"
          @click="logout"
        >
          <AppIcon name="login" />
          Logout
        </button>
      </div>
    </aside>
  </div>
</template>
