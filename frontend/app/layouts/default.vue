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
        <div class="mb-6 flex items-center gap-2 text-xs text-slate-400">
          <span>Asrama</span>
          <span class="text-slate-300 dark:text-slate-600">›</span>
          <span class="font-semibold text-slate-700 dark:text-slate-200">{{ currentLabel }}</span>
        </div>
        <AlertMessage :error="error" />
        <slot />
      </main>
      <AppFooter />
    </div>
  </div>
</template>
