// Run against a running Nuxt server and a running Laravel backend.
import assert from 'node:assert/strict'

const base = process.env.ASRAMA_URL || 'http://127.0.0.1:3000'
const jar = new Map()

async function request(path, options = {}) {
  const response = await fetch(base + path, {
    ...options,
    headers: {
      accept: 'application/json',
      cookie: [...jar].map(([k, v]) => `${k}=${v}`).join('; '),
      ...options.headers,
    },
  })
  for (const cookie of response.headers.getSetCookie()) {
    const [pair] = cookie.split(';')
    const split = pair.indexOf('=')
    jar.set(pair.slice(0, split), pair.slice(split + 1))
  }
  return response
}

for (const path of [
  '/',
  '/admin/rooms',
  '/admin/laundry',
  '/admin/catering',
  '/resident/room',
  '/resident/laundry',
  '/resident/catering',
  '/login',
  '/images/gojags.png',
]) {
  assert.equal((await request(path)).status, 200, path)
}

const session = await request('/api/session')
assert.equal(session.status, 200)
const { csrfToken, user } = await session.json()
assert.ok(csrfToken)
assert.equal(user, null)
assert.ok(jar.size, 'Session cookie must be forwarded')

assert.equal((await request('/api/dashboard')).status, 401)
assert.equal((await request('/api/rooms')).status, 401)

const credentials = JSON.stringify({ email: 'invalid@example.com', password: 'invalid-password' })
assert.equal(
  (
    await request('/api/login', {
      method: 'POST',
      headers: { 'content-type': 'application/json' },
      body: credentials,
    })
  ).status,
  419,
)
assert.equal(
  (
    await request('/api/login', {
      method: 'POST',
      headers: { 'content-type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: credentials,
    })
  ).status,
  422,
)
assert.equal(
  (
    await request('/api/laundry-orders', {
      method: 'POST',
      headers: { 'content-type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: '{}',
    })
  ).status,
  401,
)
assert.equal((await request('/api/not_allowed!')).status, 404)

console.log('PASS: Nuxt pages, logo, Laravel proxy, session cookies, CSRF, validation, and authentication.')
