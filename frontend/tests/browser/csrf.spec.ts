import { expect, test } from '../support/fixtures'
import { MESSAGES } from '../support/messages'
import { sampleComplaint } from '../support/samples'

test('mengirim header X-CSRF-TOKEN dan mengulang sekali saat menerima 419', async ({ asResident: page }) => {
  let currentToken = 'token-awal'
  let postAttempts = 0

  await page.route('**/api/session', (route) => {
    return route.fulfill({
      json: {
        csrfToken: currentToken,
        user: { id: 2, name: 'User uji', email: 'uji@example.id', role: 'resident' },
      },
    })
  })

  await page.route('**/api/complaints', async (route) => {
    postAttempts += 1
    const tokenHeader = route.request().headers()['x-csrf-token']
    if (postAttempts === 1) {
      expect(tokenHeader).toBe('token-awal')
      currentToken = 'token-baru'
      await route.fulfill({ status: 419, json: { message: 'CSRF token mismatch.' } })
      return
    }
    expect(tokenHeader).toBe('token-baru')
    await route.fulfill({ status: 201, json: { message: MESSAGES.COMPLAINT_SENT } })
  })

  await page.goto('/resident/room')
  const section = page.getByRole('region', { name: 'Keluhan asrama' })
  await section.getByLabel('Judul keluhan').fill(sampleComplaint().title)
  await section.getByLabel('Isi keluhan').fill(sampleComplaint().body)
  await section.getByRole('button', { name: 'Kirim keluhan' }).click()

  await expect(section.getByRole('status')).toContainText(MESSAGES.COMPLAINT_SENT)
  expect(postAttempts).toBe(2)
})
