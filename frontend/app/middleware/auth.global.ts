export default defineNuxtRouteMiddleware(async (to) => {
  const { user, load } = useSession()

  try {
    await load()
  } catch {
    if (to.path === '/login') {
      return
    }
    return navigateTo('/login', { replace: true })
  }

  const home = user.value?.role === 'admin' ? '/admin' : '/resident'

  if (to.path === '/' || to.path === '/login') {
    if (user.value) {
      return navigateTo(home, { replace: true })
    }
    if (to.path !== '/login') {
      return navigateTo('/login', { replace: true })
    }
    return
  }

  if (!user.value) {
    return navigateTo('/login', { replace: true })
  }

  if (to.meta.role && to.meta.role !== user.value.role) {
    return navigateTo(home, { replace: true })
  }
})
