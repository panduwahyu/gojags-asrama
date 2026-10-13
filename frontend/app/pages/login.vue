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
  'absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-lg',
  'text-slate-500 hover:text-orange-600 focus-visible:outline-2 focus-visible:outline-orange-400',
  'dark:text-slate-400 dark:hover:text-orange-400',
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
    const home = user.value?.role === 'admin' ? '/admin' : '/resident'
    await navigateTo(home)
  }
}
</script>

<template>
  <section class="card w-full max-w-md p-8 shadow-xl shadow-orange-900/5 dark:shadow-black/20">
    <div class="flex items-center gap-3">
      <img
        src="/images/gojags.png"
        alt="Logo Gojags"
        class="size-12 object-contain"
      >
      <span class="text-xl font-extrabold tracking-tight">Gojags Asrama</span>
    </div>
    <h1 class="mt-5 text-3xl font-bold tracking-tight">
      Selamat datang kembali
    </h1>
    <p class="muted mt-3">
      Masuk ke akun Anda untuk melanjutkan.
    </p>
    <AlertMessage :error="action.error.value" />
    <p
      v-if="user"
      class="muted mt-5"
    >
      Anda sudah login sebagai {{ user.name }}.
      <NuxtLink
        to="/"
        class="text-orange-600 dark:text-orange-400"
      >
        Ke Beranda →
      </NuxtLink>
    </p>
    <form
      v-else
      class="mt-6 space-y-5"
      @submit.prevent="login"
    >
      <label>
        Email
        <input
          v-model="email"
          type="email"
          autocomplete="username"
          required
          placeholder="nama@example.id"
        >
      </label>
      <div>
        <label for="login-password">Kata sandi</label>
        <div class="relative mt-2">
          <input
            id="login-password"
            v-model="password"
            :type="showPassword ? 'text' : 'password'"
            autocomplete="current-password"
            required
            placeholder="Masukkan kata sandi"
            class="pr-12"
          >
          <button
            type="button"
            :class="toggleBtnClass"
            :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
            aria-controls="login-password"
            :aria-pressed="showPassword"
            @click="showPassword = !showPassword"
          >
            <AppIcon :name="showPassword ? 'eye-off' : 'eye'" />
          </button>
        </div>
      </div>
      <button
        class="btn w-full"
        :disabled="action.busy.value"
      >
        {{ action.busy.value ? 'Memproses…' : 'Login' }}
        <AppIcon name="arrow" />
      </button>
    </form>
  </section>
</template>
