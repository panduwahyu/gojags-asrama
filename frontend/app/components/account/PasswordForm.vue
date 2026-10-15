<script setup lang="ts">
const api = useApi()
const action = useAction()
const { user } = useSession()

const form = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
})

async function submit() {
  const ok = await action.run(() => api.put('password', { ...form }))
  if (ok) {
    form.current_password = ''
    form.password = ''
    form.password_confirmation = ''
  }
}
</script>

<template>
  <section
    aria-label="Ubah kata sandi"
    class="card mt-6 p-6 md:p-8"
  >
    <h2 class="section-title">
      Ubah kata sandi
    </h2>
    <p class="muted mt-1 text-xs">
      Masuk sebagai {{ user?.email }}. Kata sandi baru minimal 8 karakter. Lupa kata sandi? Minta petugas asrama
      untuk mengatur ulang.
    </p>

    <AlertMessage
      :error="action.error.value"
      :success="action.success.value"
    />

    <form
      class="mt-4 max-w-md space-y-4"
      @submit.prevent="submit"
    >
      <FormField label="Kata sandi saat ini" for="password-current">
        <input
          id="password-current"
          v-model="form.current_password"
          type="password"
          autocomplete="current-password"
          required
          :disabled="action.busy.value"
        >
      </FormField>
      <FormField label="Kata sandi baru" for="password-new">
        <input
          id="password-new"
          v-model="form.password"
          type="password"
          autocomplete="new-password"
          minlength="8"
          required
          :disabled="action.busy.value"
        >
      </FormField>
      <FormField label="Ulangi kata sandi baru" for="password-confirmation">
        <input
          id="password-confirmation"
          v-model="form.password_confirmation"
          type="password"
          autocomplete="new-password"
          minlength="8"
          required
          :disabled="action.busy.value"
        >
      </FormField>
      <button
        class="btn"
        :disabled="action.busy.value"
      >
        {{ action.busy.value ? 'Memproses…' : 'Simpan kata sandi' }}
      </button>
    </form>
  </section>
</template>
