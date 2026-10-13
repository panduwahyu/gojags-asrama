<script setup lang="ts">
const route = useRoute()
const { user } = useSession()
const error = ref('')

const currentLabel = computed(() => {
  const menus = menusFor(user.value?.role)
  return menus.find(item => item.to === route.path)?.label ?? 'Login'
})
</script>

<template>
  <div>
    <AppSidebar @error="error = $event" />
    <div class="lg:ml-64">
      <AppHeader />
      <main class="mx-auto max-w-7xl px-5 py-7 md:px-10">
        <p class="mb-6 text-xs text-slate-400">
          Asrama
          <span class="px-3">›</span>
          {{ currentLabel }}
        </p>
        <AlertMessage :error="error" />
        <slot />
      </main>
      <AppFooter />
    </div>
  </div>
</template>
