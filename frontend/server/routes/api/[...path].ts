export default defineEventHandler(async (event) => {
  const path = getRouterParam(event, 'path') || ''
  // Tujuan tetap; jangan pernah menerima URL tujuan dari peramban.
  if (!/^[a-z0-9/-]+$/.test(path)) {
    throw createError({ statusCode: 404 })
  }
  const config = useRuntimeConfig(event)
  const base = config.laravelBaseUrl.replace(/\/$/, '')
  const query = getRequestURL(event).search
  // Laravel membatasi login per email + IP; IP ini menimpa X-Forwarded-For kiriman klien.
  const clientIp = getRequestIP(event, { xForwardedFor: Boolean(config.trustForwardedFor) }) || 'unknown'
  setResponseHeader(event, 'Cache-Control', 'no-store')
  return proxyRequest(event, `${base}/api/${path}${query}`, {
    headers: { 'accept': 'application/json', 'x-forwarded-for': clientIp },
  })
})
