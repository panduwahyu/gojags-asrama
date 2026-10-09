export default defineEventHandler(async (event) => {
  const path = getRouterParam(event, 'path') || ''
  // Keep the upstream fixed; never accept a destination URL from the browser.
  if (!/^api\/asrama\/[a-z0-9/-]+$/.test(path)) {
    throw createError({ statusCode: 404 })
  }
  const base = useRuntimeConfig(event).laravelBaseUrl.replace(/\/$/, '')
  const query = getRequestURL(event).search
  setResponseHeader(event, 'Cache-Control', 'no-store')
  return proxyRequest(event, `${base}/${path}${query}`, {
    headers: { accept: 'application/json' },
  })
})
