<script setup lang="ts">
definePageMeta({ layout: 'auth' })
useHead({ title: 'Login · Gojags Asrama' })
const { user, mutate, errorMessage } = useAsrama()
const email = ref('')
const password = ref('')
const showPassword = ref(false)
const error = ref('')
const busy = ref(false)
async function login() {
  busy.value = true
  showPassword.value = false
  error.value = ''
  try { await mutate('login', { email: email.value, password: password.value }); password.value = ''; await navigateTo(user.value?.role === 'admin' ? '/admin' : '/user') }
  catch (e) { error.value = errorMessage(e) }
  finally { busy.value = false }
}
</script>

<template>
  <section class="card w-full max-w-md p-8 shadow-xl shadow-orange-900/5 dark:shadow-black/20"><div class="flex items-center gap-3"><img src="/images/gojags.png" alt="Logo Gojags" class="size-12 object-contain"><span class="text-xl font-extrabold tracking-tight">Gojags Asrama</span></div><h1 class="mt-5 text-3xl font-bold tracking-tight">Selamat datang kembali</h1><p class="muted mt-3">Masuk ke akun Anda untuk melanjutkan.</p><p v-if="error" role="alert" class="mt-5 rounded-lg bg-red-50 dark:bg-red-950/50 p-3 text-sm text-red-700 dark:text-red-300">{{ error }}</p><p v-if="user" class="muted mt-5">Anda sudah login sebagai {{ user.name }}. <NuxtLink to="/" class="text-orange-600 dark:text-orange-400">Ke Beranda →</NuxtLink></p><form v-else class="mt-6 space-y-5" @submit.prevent="login"><label>Email<input v-model="email" type="email" autocomplete="username" required placeholder="nama@example.id"></label><div><label for="login-password">Kata sandi</label><div class="relative mt-2"><input id="login-password" v-model="password" :type="showPassword ? 'text' : 'password'" autocomplete="current-password" required placeholder="Masukkan kata sandi" class="pr-12"><button type="button" class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-lg text-slate-500 hover:text-orange-600 focus-visible:outline-2 focus-visible:outline-orange-400 dark:text-slate-400 dark:hover:text-orange-400" :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'" aria-controls="login-password" :aria-pressed="showPassword" @click="showPassword = !showPassword"><AsramaIcon :name="showPassword ? 'eye-off' : 'eye'" /></button></div></div><button class="btn w-full" :disabled="busy">{{ busy ? 'Memproses…' : 'Login' }}<AsramaIcon name="arrow" /></button></form></section>
</template>
