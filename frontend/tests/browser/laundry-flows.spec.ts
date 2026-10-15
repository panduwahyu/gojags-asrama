import { expect, test } from '../support/fixtures'
import { MESSAGES } from '../support/messages'
import { mockTable } from '../support/mock-api'
import { sampleLaundryOrder } from '../support/samples'

test('penghuni membatalkan pesanan laundry yang masih Diajukan', async ({ asResident: page }) => {
  const order = sampleLaundryOrder({ id: 70, items: 'Jaket tebal' })
  await mockTable(page, 'laundry-orders', () => [order])
  await page.route('**/api/laundry-orders/70/cancel', async (route) => {
    expect(route.request().method()).toBe('PATCH')
    order.status = 'cancelled'
    order.status_label = 'Dibatalkan'
    await route.fulfill({ json: { message: MESSAGES.LAUNDRY_CANCELLED } })
  })

  await page.goto('/resident/laundry')
  const row = page.getByRole('region', { name: 'Pengajuan laundry' }).getByRole('row', { name: /Jaket tebal/ })

  await row.getByRole('button', { name: /Batalkan laundry/ }).click()
  await row.getByRole('button', { name: 'Ya, batalkan' }).click()

  await expect(page.getByRole('status').filter({ hasText: MESSAGES.LAUNDRY_CANCELLED })).toBeVisible()
  await expect(page.getByRole('row', { name: /Jaket tebal/ })).toContainText('Dibatalkan')
  await expect(page.getByRole('row', { name: /Jaket tebal/ }).getByRole('button')).toHaveCount(0)
})

test('pesanan Selesai atau Dibatalkan tidak lagi bisa dibatalkan penghuni', async ({ asResident: page }) => {
  await mockTable(page, 'laundry-orders', () => [
    sampleLaundryOrder({ id: 71, items: 'Seragam', status: 'done' }),
    sampleLaundryOrder({ id: 72, items: 'Selimut', status: 'cancelled' }),
  ])

  await page.goto('/resident/laundry')
  const section = page.getByRole('region', { name: 'Pengajuan laundry' })

  await expect(section.getByRole('row', { name: /Seragam/ })).toContainText('Sudah selesai')
  await expect(section.getByRole('row', { name: /Selimut/ })).toContainText('Dibatalkan')
  await expect(section.getByRole('button', { name: /Batalkan laundry/ })).toHaveCount(0)
})

test('admin: pesanan Selesai tidak bisa kembali ke Diajukan dan pesanan batal tidak bisa diubah', async ({ asAdmin: page }) => {
  await mockTable(page, 'laundry-orders', () => [
    sampleLaundryOrder({ id: 73, user_name: 'Rina Wati', status: 'done' }),
    sampleLaundryOrder({ id: 74, user_name: 'Tono', status: 'cancelled' }),
  ])

  await page.goto('/admin/laundry')
  const section = page.getByRole('region', { name: 'Pengajuan laundry' })

  await expect(section.getByLabel('Status Rina Wati').locator('option[value="pending"]')).toBeDisabled()
  await expect(section.getByRole('row', { name: /Tono/ })).toContainText('Dibatalkan penghuni')
  await expect(section.getByRole('row', { name: /Tono/ }).getByRole('button', { name: 'Simpan' })).toHaveCount(0)
})

test('biaya laundry hanya wajib saat status Selesai', async ({ asAdmin: page }) => {
  await mockTable(page, 'laundry-orders', () => [sampleLaundryOrder({ id: 75, user_name: 'Rina Wati' })])

  await page.goto('/admin/laundry')
  const section = page.getByRole('region', { name: 'Pengajuan laundry' })
  const cost = section.getByLabel('Biaya laundry Rina Wati')

  expect(await cost.evaluate(element => (element as HTMLInputElement).required)).toBe(false)
  await section.getByLabel('Status Rina Wati').selectOption('done')
  expect(await cost.evaluate(element => (element as HTMLInputElement).required)).toBe(true)
})

test('menyimpan satu baris tidak menghapus isian baris lain', async ({ asAdmin: page }) => {
  const orders = [
    sampleLaundryOrder({ id: 76, user_name: 'Rina Wati', status: 'pending' }),
    sampleLaundryOrder({ id: 77, user_name: 'Tono Wijaya', status: 'pending' }),
  ]
  await mockTable(page, 'laundry-orders', () => orders.map(order => ({ ...order })))
  await page.route('**/api/laundry-orders/76', async (route) => {
    orders[0] = { ...orders[0]!, status: 'done', status_label: 'Selesai', cost: '20000.00' }
    await route.fulfill({ json: { message: MESSAGES.LAUNDRY_STATUS_UPDATED } })
  })

  await page.goto('/admin/laundry')
  const section = page.getByRole('region', { name: 'Pengajuan laundry' })

  await section.getByLabel('Biaya laundry Tono Wijaya').fill('35000')
  await section.getByLabel('Status Rina Wati').selectOption('done')
  await section.getByLabel('Biaya laundry Rina Wati').fill('20000')
  await section.getByRole('row', { name: /Rina Wati/ }).getByRole('button', { name: 'Simpan' }).click()

  await expect(page.getByRole('status').filter({ hasText: MESSAGES.LAUNDRY_STATUS_UPDATED })).toBeVisible()
  // Setelah tabel dimuat ulang, ketikan yang belum disimpan pada baris lain tetap ada.
  await expect(section.getByLabel('Biaya laundry Tono Wijaya')).toHaveValue('35000')
})
