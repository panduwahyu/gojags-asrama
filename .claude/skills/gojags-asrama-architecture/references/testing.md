# Strategi pengujian

## Isi

1. Empat lapis dan tugasnya · 2. Apa diuji di mana · 3. PHPUnit · 4. Vitest (utilitas) ·
5. Playwright: dua mode · 6. Playwright tiruan (`tests/browser`) · 7. Playwright E2E (`tests/e2e`) ·
8. Aturan menulis tes Playwright · 9. Perintah · 10. Minimum per fitur

## 1. Empat lapis dan tugasnya

Setiap aturan diuji **satu kali, di lapis termurah yang bisa membuktikannya**. Menguji aturan yang sama
di tiga lapis membuat perubahan kecil merusak banyak tes tanpa menambah keyakinan.

| Lapis | Alat | Lokasi | Membuktikan | Kecepatan |
|---|---|---|---|---|
| Unit PHP | PHPUnit | `tests/Unit/` | Logika murni tanpa basis data (`RoomStatus::fromCounts`) | milidetik |
| Feature API | PHPUnit | `tests/Feature/` | Kontrak API dan **semua aturan bisnis + pesan** di `domain.md` | cepat |
| Unit TS | Vitest | `frontend/tests/unit/` | Fungsi murni di `utils/` (zona waktu, Rupiah, pesan error) | milidetik |
| UI tiruan | Playwright | `frontend/tests/browser/` | Perilaku antarmuka dengan API ditiru (`page.route`) | cepat, tanpa Laravel |
| E2E | Playwright | `frontend/tests/e2e/` | Beberapa alur utama menembus Nuxt → proxy → Laravel → basis data | lambat |

Bentuk yang dituju adalah piramida: banyak feature test, cukup banyak tes UI tiruan, dan **sedikit** E2E
(5–7 alur). E2E mahal dan rapuh; gunanya hanya membuktikan semua bagian tersambung dan tipe frontend
tidak menyimpang dari Resource backend.

## 2. Apa diuji di mana

| Yang ingin dibuktikan | Lapis |
|---|---|
| Aturan validasi dan teks pesannya | Feature API |
| Aturan status (izin hanya bisa diedit saat `pending`, resolve idempoten, dst.) | Feature API |
| Batas peran (401/403) dan kepemilikan (404) | Feature API |
| Algoritma alokasi kamar, urutan kunci, check-in/out | Feature API |
| Parameter tabel di server (`sort` di-whitelist, `per_page` 10/50/100) | Feature API (`TableQueryTest`) |
| Konversi WIB ↔ UTC, format Rupiah, pemetaan pesan error | Unit TS |
| Debounce cari, balik arah urut, kembali ke halaman 1, paginasi | UI tiruan |
| Form ter-reset, pesan sukses/gagal tampil, tombol nonaktif saat sibuk | UI tiruan |
| Tampilan berbeda per peran, pengalihan middleware, tema terang/gelap | UI tiruan |
| Header `X-CSRF-TOKEN` terkirim; ulang sekali saat 419 | UI tiruan |
| Polling 15 detik berhenti saat tab tersembunyi/sibuk | UI tiruan (`page.clock`) |
| Login sungguhan, cookie sesi, proxy, CSRF asli | E2E |
| Alur lintas peran (penghuni mengajukan → admin menyetujui → penghuni melihat hasil) | E2E |

Tes UI **tidak** mengulang tiap aturan validasi; cukup satu kasus yang membuktikan pesan error dari
server ditampilkan.

## 3. PHPUnit

Pola dan contoh ada di `backend.md` § 13. Tambahan:

- Tes berjalan terhadap basis data yang dikonfigurasi di `phpunit.xml` (saat ini MySQL
  `gojags_asrama_testing`) dengan `RefreshDatabase`. Jangan menulis SQL yang hanya jalan di satu mesin
  basis data; satu-satunya SQL mentah (`Room::scopeOrderByStatus`) harus lulus di MySQL dan SQLite.
- Data disiapkan lewat factory dan state-nya, bukan `DB::table()->insert()`.
- Nama method berupa kalimat perilaku: `test_resident_cannot_edit_processed_permit`.
- Satu method menguji satu aturan. Bila butuh banyak variasi input, pakai *data provider*.
- Waktu dibekukan dengan `$this->travelTo(...)` bila hasil bergantung pada jam.
- Tes yang butuh kamar memanggil `$this->seed(RoomSeeder::class)`; untuk skenario "kamar habis", kecilkan
  inventaris lewat `config(['asrama.rooms_per_floor' => 1])` sebelum seeding.

## 4. Vitest (utilitas)

Hanya untuk fungsi murni di `frontend/app/utils/`. Fungsinya diimpor eksplisit, sehingga tidak perlu
lingkungan Nuxt dan tes selesai dalam hitungan milidetik. Composable dan komponen **tidak** diuji di
sini — perilakunya dibuktikan lewat Playwright tiruan.

```ts
// frontend/tests/unit/datetime.test.ts
import { describe, expect, it } from 'vitest'
import { isoToWibInput, wibInputToIso } from '~/utils/datetime'

describe('wibInputToIso', () => {
  it('mengubah jam WIB menjadi UTC', () => {
    expect(wibInputToIso('2026-10-13T08:00')).toBe('2026-10-13T01:00:00.000Z')
  })

  it('melewati batas hari', () => {
    expect(wibInputToIso('2026-10-13T03:00')).toBe('2026-10-12T20:00:00.000Z')
  })
})

describe('isoToWibInput', () => {
  it('adalah kebalikan wibInputToIso', () => {
    expect(isoToWibInput(wibInputToIso('2026-10-13T23:59'))).toBe('2026-10-13T23:59')
  })
})
```

Wajib ada: `datetime.test.ts` (termasuk kasus lewat tengah malam), `currency.test.ts` (null → "Belum
ditentukan"), `errors.test.ts` (422 dengan `errors`, 401, 403, 419, 429, gagal jaringan).

Penyiapan: devDependency `vitest`, `frontend/vitest.config.ts` dengan alias `~` → `./app` dan
`include: ['tests/unit/**/*.test.ts']`, skrip `"test:unit": "vitest run"`.

## 5. Playwright: dua mode

| | Tiruan (`tests/browser/`) | E2E (`tests/e2e/`) |
|---|---|---|
| Konfigurasi | `playwright.config.ts` | `playwright.e2e.config.ts` |
| Butuh Laravel + basis data | tidak — semua `/api/**` ditiru | ya — basis data khusus `*_e2e` |
| Server | Nuxt dinyalakan otomatis (`webServer`) | Laravel **dan** Nuxt dinyalakan otomatis |
| Jumlah tes | banyak, satu spec per fitur | sedikit, satu spec per alur |
| Dijalankan | setiap perubahan frontend | sebelum merge / rilis |

Dua berkas konfigurasi terpisah supaya menjalankan tes tiruan tidak pernah menyalakan PHP atau menyentuh
basis data.

Opsi bersama ada di satu tempat:

```ts
// frontend/tests/support/shared.ts
import type { PlaywrightTestConfig } from '@playwright/test'

export const NUXT_PORT = 3100
export const LARAVEL_PORT = 8100
export const BASE_URL = `http://127.0.0.1:${NUXT_PORT}`

export const sharedConfig: PlaywrightTestConfig = {
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  workers: 1,
  reporter: [['list'], ['html', { open: 'never' }]],
  use: {
    baseURL: BASE_URL,
    browserName: 'chromium',
    channel: process.env.PLAYWRIGHT_CHANNEL || (process.platform === 'win32' ? 'msedge' : undefined),
    locale: 'id-ID',
    timezoneId: 'Asia/Jakarta',
    colorScheme: 'light',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
  },
}
```

Port 3100/8100 sengaja berbeda dari port pengembangan (3000/8000) agar tes tidak menabrak server yang
sedang dipakai.

## 6. Playwright tiruan (`tests/browser`)

```ts
// frontend/playwright.config.ts
import { defineConfig } from '@playwright/test'
import { BASE_URL, NUXT_PORT, sharedConfig } from './tests/support/shared'

export default defineConfig({
  ...sharedConfig,
  testDir: './tests/browser',
  webServer: {
    command: `npm run dev -- --port ${NUXT_PORT}`,
    url: BASE_URL,
    reuseExistingServer: !process.env.CI,
  },
})
```

### Pembantu bersama

Kode lama menyalin `mockSession` dan data contoh di tiap spec. Sekarang ada di `tests/support/`:

| Berkas | Isi |
|---|---|
| `tests/support/samples.ts` | Pabrik data contoh **bertipe dari `app/types`**: `sampleLeavePermit({ status: 'approved' })` |
| `tests/support/mock-api.ts` | `mockSession(page, role)`, `mockTable(page, path, rows)`, `mockEmptyTables(page)` |
| `tests/support/fixtures.ts` | `test` yang diperluas dengan fixture `asAdmin` dan `asResident` |

Data contoh diberi tipe `LeavePermit`, `Paginated<T>`, dst. dari aplikasi. Bila bentuk Resource berubah
dan tipenya diperbarui, `npm run typecheck` langsung menunjukkan tiruan mana yang ketinggalan — inilah
yang menjaga tiruan tetap jujur.

```ts
// frontend/tests/support/mock-api.ts
import type { Page } from '@playwright/test'
import type { Paginated } from '~/types/api'
import type { Role } from '~/types/session'

export const CSRF_TOKEN = 'test-token'

export async function mockSession(page: Page, role: Role) {
  const user = { id: 9999, name: 'User uji', email: 'uji@example.id', role }
  await page.route('**/api/session', route => route.fulfill({ json: { csrfToken: CSRF_TOKEN, user } }))
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
```

```ts
// frontend/tests/support/fixtures.ts
import { test as base, type Page } from '@playwright/test'
import { mockEmptyTables, mockSession } from './mock-api'

export const test = base.extend<{ asAdmin: Page, asResident: Page }>({
  asAdmin: async ({ page }, use) => {
    await mockSession(page, 'admin')
    await mockEmptyTables(page)
    await use(page)
  },
  asResident: async ({ page }, use) => {
    await mockSession(page, 'resident')
    await mockEmptyTables(page)
    await use(page)
  },
})

export { expect } from '@playwright/test'
```

`mockEmptyTables` mendaftarkan jawaban kosong untuk semua endpoint yang dimuat halaman, sehingga tiap tes
hanya meniru endpoint yang sedang diujinya. Rute yang didaftarkan belakangan menang, jadi tes cukup
memanggil `mockTable` lagi untuk fiturnya.

### Bentuk spec

```ts
// frontend/tests/browser/leave-permit.spec.ts
import { expect, test } from '../support/fixtures'
import { CSRF_TOKEN, mockTable } from '../support/mock-api'
import { sampleLeavePermit } from '../support/samples'

test('penghuni mengajukan izin dan waktu dikirim sebagai UTC', async ({ asResident: page }) => {
  const permits = [sampleLeavePermit({ status: 'approved' })]
  await mockTable(page, 'leave-permits', () => permits)
  await page.route('**/api/leave-permits', async (route) => {
    const request = route.request()
    expect(request.method()).toBe('POST')
    expect(request.headers()['x-csrf-token']).toBe(CSRF_TOKEN)
    expect(request.postDataJSON().leaves_at).toBe('2026-10-13T01:00:00.000Z')
    permits.unshift(sampleLeavePermit({ reason: 'Mengunjungi keluarga' }))
    await route.fulfill({ status: 201, json: { message: 'Izin keluar berhasil diajukan.' } })
  })

  await page.goto('/resident/room')
  const section = page.getByRole('region', { name: 'Izin keluar' })
  await section.getByLabel('Waktu keluar (WIB)').fill('2026-10-13T08:00')
  await section.getByLabel('Waktu kembali (WIB)').fill('2026-10-13T18:00')
  await section.getByLabel('Alasan').fill('Mengunjungi keluarga')
  await section.getByRole('button', { name: 'Ajukan izin' }).click()

  await expect(section.getByRole('status')).toContainText('berhasil diajukan')
  await expect(section.getByRole('row', { name: /Mengunjungi keluarga/ })).toBeVisible()
  await expect(section.getByLabel('Alasan')).toHaveValue('')
})
```

Nama tes berupa kalimat Indonesia tentang perilaku. Satu tes membuktikan satu perilaku; tes lama yang
berjudul "user submits and edits … and searches without navigation" (lima hal sekaligus) dipecah.

### Spec yang wajib ada

| Spec | Isi |
|---|---|
| `roles.spec.ts` | Tamu → `/login`; admin membuka halaman penghuni → dialihkan ke `/admin`; sebaliknya |
| `login.spec.ts` | Login berhasil pindah ke beranda sesuai peran; gagal menampilkan pesan server |
| `data-table.spec.ts` | Sekali untuk semua tabel: debounce 300 ms, balik arah urut + `aria-sort`, ganti `per_page` kembali ke halaman 1, tombol paginasi, keadaan kosong, gagal muat + "Coba lagi" |
| `csrf.spec.ts` | Header terkirim; jawaban 419 → sesi dimuat ulang → permintaan diulang sekali |
| `polling.spec.ts` | Dengan `page.clock`: muat ulang setelah 15 detik; tidak saat tab tersembunyi |
| `theme.spec.ts` | Tombol tema menukar kelas `dark` dan bertahan setelah muat ulang |
| `<fitur>.spec.ts` | `leave-permit`, `complaint`, `my-room`, `rooms`, `laundry`, `catering`, `dashboard` |

Perilaku tabel diuji **sekali** di `data-table.spec.ts`; spec fitur tidak mengulanginya.

## 7. Playwright E2E (`tests/e2e`)

### Basis data khusus dan pengaman

E2E menjalankan `migrate:fresh`, yang **menghapus seluruh isi basis data**. Karena itu:

- E2E memakai berkas lingkungan sendiri, `.env.e2e`, dengan `DB_DATABASE=gojags_asrama_e2e`.
- Reset hanya lewat `frontend/scripts/reset-e2e-db.mjs`, yang membaca `.env.e2e` dan **menolak berjalan
  bila nama basis data tidak berakhiran `_e2e`**. Jangan pernah memanggil `migrate:fresh` langsung dari
  tes atau tanpa `--env=e2e`.

```js
// frontend/scripts/reset-e2e-db.mjs
import { execFileSync } from 'node:child_process'
import { readFileSync } from 'node:fs'
import { fileURLToPath } from 'node:url'

const root = fileURLToPath(new URL('../..', import.meta.url))
const env = readFileSync(`${root}/.env.e2e`, 'utf8')
const database = env.match(/^DB_DATABASE=(.*)$/m)?.[1]?.trim() ?? ''

if (!database.endsWith('_e2e')) {
  throw new Error(`Menolak reset: DB_DATABASE di .env.e2e ("${database}") tidak berakhiran _e2e.`)
}

execFileSync('php', ['artisan', 'migrate:fresh', '--seed', '--force', '--env=e2e'], {
  cwd: root,
  stdio: 'inherit',
})
```

### Konfigurasi

```ts
// frontend/playwright.e2e.config.ts
import { defineConfig } from '@playwright/test'
import { BASE_URL, LARAVEL_PORT, NUXT_PORT, sharedConfig } from './tests/support/shared'

const LARAVEL_URL = `http://127.0.0.1:${LARAVEL_PORT}`

export default defineConfig({
  ...sharedConfig,
  testDir: './tests/e2e',
  fullyParallel: false,
  projects: [
    { name: 'setup', testMatch: 'global.setup.ts' },
    { name: 'e2e', testMatch: '*.spec.ts', dependencies: ['setup'] },
  ],
  webServer: [
    {
      command: `php artisan serve --env=e2e --port=${LARAVEL_PORT}`,
      cwd: '..',
      url: `${LARAVEL_URL}/up`,
      reuseExistingServer: false,
    },
    {
      command: `npm run dev -- --port ${NUXT_PORT}`,
      url: BASE_URL,
      env: { NUXT_LARAVEL_BASE_URL: LARAVEL_URL },
      reuseExistingServer: false,
    },
  ],
})
```

`reuseExistingServer: false` memastikan E2E tidak pernah menempel ke server pengembangan yang menunjuk
basis data sungguhan.

### Setup: reset lalu login sekali per peran

```ts
// frontend/tests/e2e/global.setup.ts
import { execFileSync } from 'node:child_process'
import { expect, test as setup } from '@playwright/test'
import { ACCOUNTS, STATE } from './accounts'

setup('reset basis data', () => {
  execFileSync('node', ['scripts/reset-e2e-db.mjs'], { stdio: 'inherit' })
})

for (const [name, account] of Object.entries(ACCOUNTS)) {
  setup(`login ${name}`, async ({ page }) => {
    await page.goto('/login')
    await page.getByLabel('Email').fill(account.email)
    await page.getByLabel('Kata sandi').fill(account.password)
    await page.getByRole('button', { name: 'Masuk' }).click()
    await expect(page).toHaveURL(account.home)
    await page.context().storageState({ path: STATE[name as keyof typeof STATE] })
  })
}
```

Spec lalu memakai sesi tersimpan (`test.use({ storageState: STATE.admin })`) atau membuka dua konteks
untuk alur lintas peran. Login lewat UI hanya terjadi di setup dan di `auth.spec.ts`. Folder
`tests/e2e/.auth/` masuk `.gitignore`.

### Isolasi antar-spec

Semua spec berbagi satu basis data dan berjalan berurutan. Agar tidak saling bergantung, **tiap spec
memiliki akun seed-nya sendiri** dan tidak menyentuh akun spec lain:

| Spec | Akun | Alur |
|---|---|---|
| `auth.spec.ts` | `admin@`, `user@` | Login/logout tiap peran; setelah logout halaman terlindung mengalihkan ke `/login` |
| `leave-permit.spec.ts` | `user@` + admin | Penghuni mengajukan → admin menyetujui → penghuni melihat "Disetujui" dan tombol Edit hilang |
| `complaint.spec.ts` | `user@` + admin | Penghuni mengirim → admin menandai Resolved → penghuni melihat waktu resolved |
| `laundry.spec.ts` | `user@` + admin | Penghuni mengajukan → admin mengisi biaya + Selesai → penghuni melihat Rupiah |
| `catering.spec.ts` | `user@` + admin | Admin menambah menu → penghuni memberi rating → rata-rata tampil di admin |
| `rooms.spec.ts` | `user1@`…`user4@` + admin | Admin melengkapi profil → alokasi → `user1` check-in → status kamar berubah → check-out → slot lepas |

Tes membuat datanya sendiri dengan teks unik (mis. alasan izin ber-`Date.now()`), lalu mencari baris itu —
tidak pernah mengandalkan "baris pertama" atau jumlah baris total.

## 8. Aturan menulis tes Playwright

- **Pilih elemen seperti pengguna melihatnya**: `getByRole`, `getByLabel`, `getByText`. Tanpa selektor
  kelas CSS atau struktur DOM (`locator('tbody tr')`), karena itu putus saat markup dirapikan. Bila
  sebuah elemen tidak bisa dipilih lewat peran/label, perbaiki aksesibilitas komponennya.
- **Batasi cakupan**: ambil `region` fitur dulu (`page.getByRole('region', { name: 'Izin keluar' })`),
  lalu cari di dalamnya. Halaman kamar memuat tiga tabel sekaligus.
- **Tanpa `waitForTimeout`**. Pakai asersi yang menunggu sendiri (`await expect(locator).toBeVisible()`).
  Untuk debounce dan polling, kendalikan waktu dengan `page.clock.install()` + `page.clock.fastForward()`.
- **Asersi pada yang dilihat pengguna** (teks, nilai input, status tombol), bukan state internal.
  Pengecualian: di mode tiruan boleh memeriksa isi permintaan (`postDataJSON`, header) karena itulah
  kontrak ke server.
- **Tiap tes mandiri**: tidak bergantung urutan atau sisa tes lain; tanpa `test.describe.serial` kecuali
  satu alur E2E yang memang berurutan.
- **Tanpa `test.only`, `test.skip` tanpa alasan, atau `try/catch` yang menelan kegagalan.**
- **Tanpa kredensial sungguhan**; hanya akun seed di `tests/e2e/accounts.ts`.
- Saat gagal, baca jejaknya (`npx playwright show-report`) sebelum mengubah tes. Tes yang kadang lulus
  kadang gagal harus diperbaiki penyebabnya, bukan diberi `retries`.

## 9. Perintah

```powershell
# Backend (root repo)
composer test
php artisan test tests/Feature/LeavePermitTest.php
php artisan test --filter=nama_method

# Frontend (frontend/)
npm run typecheck
npm run test:unit                              # Vitest
npm run test:browser                           # Playwright tiruan; menyalakan Nuxt sendiri
npx playwright test tests/browser/leave-permit.spec.ts
npx playwright test -g "judul tes"
npm run test:e2e                               # reset basis data *_e2e, nyalakan Laravel + Nuxt
npx playwright show-report                     # buka laporan + jejak kegagalan
npm run test:smoke                             # pemeriksaan cepat proxy/sesi/CSRF terhadap server berjalan
```

Skrip `package.json`:

```json
"test:unit": "vitest run",
"test:browser": "playwright test",
"test:e2e": "playwright test -c playwright.e2e.config.ts",
"test:smoke": "node scripts/smoke.mjs"
```

Urutan sebelum menyatakan pekerjaan selesai: `composer test` → `vendor/bin/pint --test` →
`npm run typecheck` → `npm run test:unit` → `npm run test:browser` → `npm run test:e2e`.

## 10. Minimum per fitur

- [ ] Feature test: jalur berhasil, tiap aturan + pesan di `domain.md`, 401, 403, 404 kepemilikan.
- [ ] Data contoh bertipe di `tests/support/samples.ts`.
- [ ] Spec tiruan: tampilan per peran, tiap aksi tulis (isi permintaan + pesan sukses + tabel
      diperbarui), satu kasus error server ditampilkan.
- [ ] Bila fitur punya alur lintas peran: satu spec E2E dengan akun miliknya sendiri.
- [ ] Fungsi baru di `utils/` punya tes Vitest.
