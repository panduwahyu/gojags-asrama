import { expect, test } from '../support/fixtures'
import { mockTable } from '../support/mock-api'
import { sampleCateringMenu, sampleComplaint, sampleLaundryOrder, sampleRoom } from '../support/samples'

const EXCEL = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'

test.describe('tombol unduh Excel dan PDF', () => {
  test('penghuni: laundry, catering, dan keluhan memanggil endpoint ekspor masing-masing', async ({ asResident: page }) => {
    await mockTable(page, 'laundry-orders', () => [sampleLaundryOrder()])
    await mockTable(page, 'catering-menus', () => [sampleCateringMenu()])
    await mockTable(page, 'complaints', () => [sampleComplaint()])
    const requested: string[] = []
    for (const path of ['laundry-orders', 'catering-menus', 'complaints']) {
      await page.route(`**/api/${path}/export?*`, async (route) => {
        requested.push(`${path}:${new URL(route.request().url()).searchParams.get('format')}`)
        await route.fulfill({ headers: { 'content-type': EXCEL }, body: Buffer.from('PK') })
      })
    }

    await page.goto('/resident/laundry')
    await Promise.all([page.waitForEvent('download'), page.getByRole('button', { name: 'Unduh Excel' }).click()])

    await page.goto('/resident/catering')
    await Promise.all([page.waitForEvent('download'), page.getByRole('button', { name: 'Unduh PDF' }).click()])

    await page.goto('/resident/room')
    const complaints = page.getByRole('region', { name: 'Keluhan asrama' })
    await Promise.all([page.waitForEvent('download'), complaints.getByRole('button', { name: 'Unduh Excel' }).click()])

    expect(requested).toEqual(['laundry-orders:xlsx', 'catering-menus:pdf', 'complaints:xlsx'])
  })

  test('admin: daftar kamar dan daftar penghuni dapat diunduh', async ({ asAdmin: page }) => {
    await page.route('**/api/rooms?*', route =>
      route.fulfill({
        json: {
          data: [sampleRoom({ id: 1, number: '501' })],
          meta: { current_page: 1, last_page: 1, total: 1 },
          monitoring: { empty: 1, assigned: 0, occupied: 0, full: 0, inactive: 0 },
        },
      }),
    )
    const requested: string[] = []
    for (const path of ['rooms', 'residents']) {
      await page.route(`**/api/${path}/export?*`, async (route) => {
        requested.push(`${path}:${new URL(route.request().url()).searchParams.get('format')}`)
        await route.fulfill({ headers: { 'content-type': EXCEL }, body: Buffer.from('PK') })
      })
    }

    await page.goto('/admin/rooms')
    const rooms = page.getByRole('region', { name: 'Daftar kamar' })
    await Promise.all([page.waitForEvent('download'), rooms.getByRole('button', { name: 'Unduh Excel' }).click()])

    await page.goto('/admin/residents')
    const residents = page.getByRole('region', { name: 'Daftar penghuni' })
    await Promise.all([page.waitForEvent('download'), residents.getByRole('button', { name: 'Unduh PDF' }).click()])

    expect(requested).toEqual(['rooms:xlsx', 'residents:pdf'])
  })
})
