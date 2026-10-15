<script setup lang="ts">
import type { Session } from '~/types/session'

definePageMeta({ layout: 'auth' })
useHead({ title: 'Login · Gojags Asrama' })

const { user, apply } = useSession()
const api = useApi()
const action = useAction()

const email = ref('')
const password = ref('')
const showPassword = ref(false)

const toggleBtnClass = [
  'absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-xl',
  'text-slate-400 hover:text-indigo-400 focus-visible:outline-2 focus-visible:outline-indigo-500',
].join(' ')

async function login() {
  showPassword.value = false
  let nextSession: Session | null = null

  const ok = await action.run(async () => {
    nextSession = await api.post<Session>('login', {
      email: email.value,
      password: password.value,
    })
  })

  if (ok && nextSession) {
    apply(nextSession)
    password.value = ''
    await navigateTo(homePathFor(user.value?.role))
  }
}
</script>

<template>
  <div class="mx-auto flex w-full max-w-7xl flex-col items-center justify-between gap-12 lg:flex-row lg:gap-16">
    <!-- Left Hero Column (Exact GOJAGS Training Typography & Auth Action) -->
    <div class="w-full max-w-xl text-center lg:text-left">
      <div class="space-y-3">
        <div class="flex items-center justify-center gap-3 lg:justify-start">
          <h1 class="text-5xl font-black tracking-tight sm:text-6xl lg:text-7xl text-slate-900 dark:text-white">
            GOJAGS
          </h1>
          <span
            class="rounded-lg border border-indigo-200 bg-indigo-50/90 px-2.5 py-1 text-xs font-bold
              tracking-wider text-indigo-700 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-300"
          >
            ASRAMA
          </span>
        </div>

        <p class="text-base font-medium italic text-slate-600 sm:text-lg dark:text-slate-300">
          General Online Job Assistant for Great Service
        </p>

        <p class="text-sm leading-relaxed text-slate-500 sm:text-base dark:text-slate-300/90">
          Platform layanan teknologi informasi terintegrasi milik Pusat Pendidikan dan Pelatihan BPS
          yang siap membantu Anda dalam mengelola hunian asrama, perizinan, dan fasilitas secara cepat dan aman!
        </p>
      </div>

      <!-- Logged-in State Card -->
      <AuthLoggedInCard
        v-if="user"
        :user="user"
      />

      <!-- Login Form Matching GOJAGS Training Action -->
      <form
        v-else
        class="mt-8 max-w-md space-y-4 text-left"
        @submit.prevent="login"
      >
        <AlertMessage :error="action.error.value" />

        <div>
          <label for="login-email" class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300">
            Email
          </label>
          <input
            id="login-email"
            v-model="email"
            type="email"
            autocomplete="username"
            required
            placeholder="nama@example.id"
            class="w-full rounded-xl border border-slate-300/80 bg-white/90 px-4 py-3 text-sm text-slate-900
              placeholder-slate-400 backdrop-blur-sm transition focus:border-indigo-500 focus:ring-2
              focus:ring-indigo-500/20 dark:border-slate-700/80 dark:bg-slate-900/80 dark:text-white
              dark:placeholder-slate-500"
          >
        </div>

        <div>
          <label for="login-password" class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300">
            Kata sandi
          </label>
          <div class="relative">
            <input
              id="login-password"
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              autocomplete="current-password"
              required
              placeholder="Masukkan kata sandi"
              class="w-full rounded-xl border border-slate-300/80 bg-white/90 px-4 py-3 pr-12 text-sm text-slate-900
                placeholder-slate-400 backdrop-blur-sm transition focus:border-indigo-500 focus:ring-2
                focus:ring-indigo-500/20 dark:border-slate-700/80 dark:bg-slate-900/80 dark:text-white
                dark:placeholder-slate-500"
            >
            <button
              type="button"
              :class="toggleBtnClass"
              :aria-label="showPassword ? 'Sembunyikan sandi' : 'Tampilkan sandi'"
              aria-controls="login-password"
              :aria-pressed="showPassword"
              @click="showPassword = !showPassword"
            >
              <AppIcon :name="showPassword ? 'eye-off' : 'eye'" />
            </button>
          </div>
        </div>

        <button
          type="submit"
          class="mt-3 inline-flex items-center justify-center gap-2.5 rounded-2xl bg-indigo-600 px-7 py-3.5
            text-sm font-semibold text-white shadow-[0_0_28px_rgba(79,70,229,0.55)] transition-all
            hover:bg-indigo-500 hover:shadow-[0_0_36px_rgba(79,70,229,0.7)] active:scale-[0.99]
            disabled:opacity-50"
          :disabled="action.busy.value"
        >
          <AppIcon name="login" class="size-4" />
          <span>{{ action.busy.value ? 'Memproses…' : 'Masuk Aplikasi' }}</span>
        </button>
      </form>
    </div>

    <!-- Right Hero Column: Giant Glowing GOJAGS Ring Logo -->
    <AuthHeroGraphic />
  </div>
</template>
