import type { Page } from '@playwright/test'
import type { Paginated } from '~/types/api'
import type { Role } from '~/types/session'

export const CSRF_TOKEN = 'test-token'

export async function mockSession(page: Page, role: Role) {
  const user = { id: 9999, name: 'User uji', email: 'uji@example.id', role }
  await page.route('**/api/session', route =>
    route.fulfill({ json: { csrfToken: CSRF_TOKEN, user } }),
  )
}

/** Meniru GET daftar berpaginasi; `rows` dibaca tiap permintaan sehingga tes bisa mengubahnya. */
export async function mockTable<T>(page: Page, path: string, rows: () => T[]) {
  await page.route(`**/api/${path}?*`, (route) => {
    const body: Paginated<T> = {
      data: rows(),
      meta: { current_page: 1, last_page: 1, total: rows().length },
    }
    return route.fulfill({ json: body })
  })
}

export async function mockEmptyTables(page: Page) {
  await mockTable(page, 'leave-permits', () => [])
  await mockTable(page, 'complaints', () => [])
  await mockTable(page, 'laundry-orders', () => [])
  await mockTable(page, 'catering-menus', () => [])
  await page.route('**/api/rooms?*', route =>
    route.fulfill({
      json: {
        data: [],
        meta: { current_page: 1, last_page: 1, total: 0 },
        monitoring: { empty: 0, assigned: 0, occupied: 0, full: 0 },
      },
    }),
  )
  await page.route('**/api/residents', route => route.fulfill({ json: { data: [] } }))
  await page.route('**/api/my-room', route =>
    route.fulfill({ json: { data: { room: null, stays: [] } } }),
  )
  await page.route('**/api/dashboard', route =>
    route.fulfill({ json: { data: { rooms: 0, laundry_orders: 0, catering_menus: 0 } } }),
  )
}
