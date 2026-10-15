import { expect, test } from '../support/fixtures'
import { MESSAGES } from '../support/messages'
import { sampleStay } from '../support/samples'
import type { MyRoomData } from '~/types/room'

test('menampilkan pesan belum ada alokasi kamar bila belum ditempatkan', async ({ asResident: page }) => {
  await page.goto('/resident/room')
  const section = page.getByRole('region', { name: 'Kamar saya' })
  await expect(section.getByRole('heading', { name: 'Belum ada alokasi kamar' })).toBeVisible()
})

test('penghuni melakukan check-in lalu check-out dan riwayat diperbarui', async ({ asResident: page }) => {
  let state: MyRoomData = {
    room: { number: '501', floor: 5, checked_in_at: null },
    stays: [],
  }

  await page.route('**/api/my-room', route => route.fulfill({ json: { data: state } }))
  await page.route('**/api/my-room/check-in', async (route) => {
    expect(route.request().method()).toBe('POST')
    state = {
      room: { number: '501', floor: 5, checked_in_at: '2026-10-13T02:00:00.000000Z' },
      stays: [sampleStay({ room_number: '501', floor: 5, checked_out_at: null })],
    }
    await route.fulfill({ json: { message: MESSAGES.CHECK_IN_SUCCESS } })
  })
  await page.route('**/api/my-room/check-out', async (route) => {
    expect(route.request().method()).toBe('POST')
    state = {
      room: null,
      stays: [
        sampleStay({
          room_number: '501',
          floor: 5,
          checked_out_at: '2026-10-13T10:00:00.000000Z',
        }),
      ],
    }
    await route.fulfill({ json: { message: MESSAGES.CHECK_OUT_SUCCESS } })
  })

  await page.goto('/resident/room')
  const roomSection = page.getByRole('region', { name: 'Kamar saya' })
  await expect(roomSection.getByRole('button', { name: 'Check-in' })).toBeEnabled()
  await expect(roomSection.getByRole('button', { name: 'Check-out' })).toBeDisabled()

  await roomSection.getByRole('button', { name: 'Check-in' }).click()
  await expect(page.getByRole('status')).toContainText('Check-in berhasil.')
  await expect(roomSection.getByRole('button', { name: 'Check-out' })).toBeEnabled()

  await roomSection.getByRole('button', { name: 'Check-out' }).click()
  await expect(page.getByRole('status')).toContainText('Check-out berhasil.')
  await expect(roomSection.getByRole('heading', { name: 'Belum ada alokasi kamar' })).toBeVisible()
})

test('menampilkan pesan error server saat check-in gagal', async ({ asResident: page }) => {
  await page.route('**/api/my-room', route =>
    route.fulfill({
      json: { data: { room: { number: '501', floor: 5, checked_in_at: null }, stays: [] } },
    }),
  )
  await page.route('**/api/my-room/check-in', route =>
    route.fulfill({
      status: 422,
      json: { message: MESSAGES.ALREADY_CHECKED_IN },
    }),
  )

  await page.goto('/resident/room')
  const roomSection = page.getByRole('region', { name: 'Kamar saya' })
  await roomSection.getByRole('button', { name: 'Check-in' }).click()
  await expect(page.getByRole('alert')).toContainText('Anda sudah melakukan check-in.')
})
