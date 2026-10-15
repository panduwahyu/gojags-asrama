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
        <nav aria-label="Breadcrumb" class="mb-6 flex items-center gap-2 text-xs text-slate-400">
          <NuxtLink
            :to="homePathFor(user?.role)"
            class="transition hover:text-indigo-600 dark:hover:text-indigo-400"
          >
            Asrama
          </NuxtLink>
          <span class="text-slate-300 dark:text-slate-600" aria-hidden="true">›</span>
          <span class="font-semibold text-slate-700 dark:text-slate-200" aria-current="page">
            {{ currentLabel }}
          </span>
        </nav>
        <AlertMessage :error="error" />
        <slot />
      </main>
      <AppFooter />
    </div>
  </div>
</template>
