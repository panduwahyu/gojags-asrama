import { expect, test } from '../support/fixtures'
import { MESSAGES } from '../support/messages'
import { mockTable } from '../support/mock-api'
import { sampleLaundryOrder } from '../support/samples'

test('penghuni mengajukan laundry baru dan tabel diperbarui', async ({ asResident: page }) => {
  const orders = [sampleLaundryOrder({ status: 'done', status_label: 'Selesai', cost: '20000.00' })]
  await mockTable(page, 'laundry-orders', () => orders)
  await page.route('**/api/laundry-orders', async (route) => {
    expect(route.request().method()).toBe('POST')
    expect(route.request().postDataJSON()).toEqual({
      items: 'Jaket dan handuk',
      set_count: 2,
    })
    orders.unshift(sampleLaundryOrder({ items: 'Jaket dan handuk', set_count: 2 }))
    await route.fulfill({ status: 201, json: { message: MESSAGES.LAUNDRY_SENT } })
  })

  await page.goto('/resident/laundry')
  await page.getByLabel('Pakaian / barang yang dilaundry').fill('Jaket dan handuk')
  await page.getByLabel('Jumlah stel').fill('2')
  await page.getByRole('button', { name: 'Ajukan laundry' }).click()

  await expect(page.getByRole('status')).toContainText('Pengajuan laundry berhasil dikirim.')
  const tableSection = page.getByRole('region', { name: 'Pengajuan laundry' })
  await expect(tableSection.getByRole('row', { name: /Jaket dan handuk/ })).toBeVisible()
})

test('admin memperbarui status dan biaya laundry', async ({ asAdmin: page }) => {
  const order = sampleLaundryOrder({ id: 12, user_name: 'Rina Wati', status: 'pending', cost: null })
  await mockTable(page, 'laundry-orders', () => [order])
  await page.route('**/api/laundry-orders/12', async (route) => {
    expect(route.request().method()).toBe('PATCH')
    expect(route.request().postDataJSON()).toEqual({ status: 'done', cost: 25000 })
    order.status = 'done'
    order.status_label = 'Selesai'
    order.cost = '25000.00'
    await route.fulfill({ json: { message: MESSAGES.LAUNDRY_STATUS_UPDATED } })
  })

  await page.goto('/admin/laundry')
  const tableSection = page.getByRole('region', { name: 'Pengajuan laundry' })
  await tableSection.getByLabel('Status Rina Wati').selectOption('done')
  await tableSection.getByLabel('Biaya laundry Rina Wati').fill('25000')
  await tableSection.getByRole('button', { name: 'Simpan' }).click()

  await expect(page.getByRole('status')).toContainText('Status berhasil diperbarui.')
  await expect(tableSection.getByRole('row', { name: /25\.000/ })).toBeVisible()
})

test('menampilkan pesan error server saat pembaruan laundry gagal', async ({ asAdmin: page }) => {
  await mockTable(page, 'laundry-orders', () => [
    sampleLaundryOrder({ id: 15, user_name: 'Rina Wati' }),
  ])
  await page.route('**/api/laundry-orders/15', route =>
    route.fulfill({
      status: 422,
      json: { errors: { cost: ['Biaya wajib diisi bila status Selesai.'] } },
    }),
  )

  await page.goto('/admin/laundry')
  const tableSection = page.getByRole('region', { name: 'Pengajuan laundry' })
  await tableSection.getByLabel('Biaya laundry Rina Wati').fill('0')
  await tableSection.getByRole('button', { name: 'Simpan' }).click()

  await expect(page.getByRole('alert')).toContainText('Biaya wajib diisi bila status Selesai.')
})
