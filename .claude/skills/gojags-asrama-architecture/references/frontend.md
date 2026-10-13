# Frontend (Nuxt 4 SPA, Vue 3 `<script setup>` + TypeScript, Tailwind 4)

## Isi

1. Peta folder · 2. Proxy dan sesi · 3. Composable · 4. Tipe · 5. Utilitas · 6. Komponen `ui/` ·
7. Komponen fitur (contoh izin keluar) · 8. Halaman · 9. Format template · 10. Gaya (Tailwind) ·
11. Test

## 1. Peta folder

```
frontend/
  server/routes/api/[...path].ts
  app/
    pages/          login.vue
                    admin/index.vue  admin/rooms.vue  admin/laundry.vue  admin/catering.vue
                    resident/index.vue  resident/room.vue  resident/laundry.vue  resident/catering.vue
    layouts/        default.vue (kerangka + navigasi), auth.vue
    components/
      ui/           DataTable, StatusBadge, AlertMessage, PageHeader
      layout/       AppSidebar, AppHeader, AppFooter, ThemeToggle
      room/  leave-permit/  complaint/  laundry/  catering/
    composables/    useSession, useApi, useTable, useAction, usePolling
    types/          api.ts, session.ts, room.ts, leave-permit.ts, complaint.ts, laundry.ts, catering.ts
    utils/          datetime.ts, currency.ts, errors.ts
    middleware/     auth.global.ts
    assets/css/     main.css
```

Halaman dinamis generik (`[category].vue`, `user/[service].vue`) tidak ada lagi: tiap halaman adalah
berkas sendiri sehingga daftar berkas di `pages/` sama dengan daftar layar aplikasi.

## 2. Proxy dan sesi

Browser hanya berbicara dengan Nuxt. `server/routes/api/[...path].ts` meneruskan `/api/*` ke Laravel:
alamat upstream tetap (`runtimeConfig.laravelBaseUrl`), path di-whitelist dengan regex
`^[a-z0-9/-]+$`, header `accept: application/json` dipaksa. Karena satu origin, sesi dan CSRF tetap
berbasis cookie.

`middleware/auth.global.ts` memuat sesi tiap navigasi dan mengalihkan menurut
`definePageMeta({ role: 'admin' | 'resident' })`: belum login → `/login`; peran salah → beranda perannya
(`/admin` atau `/resident`).

## 3. Composable

Lima composable ini menggantikan kode yang dulu disalin di tiap komponen. Komponen fitur tidak memanggil
`$fetch`, `setInterval`, atau `setTimeout` secara langsung.

### `useSession`

```ts
export function useSession() {
  const user = useState<User | null>('session-user', () => null)
  const csrfToken = useState('session-csrf', () => '')
  const isAdmin = computed(() => user.value?.role === 'admin')

  function apply(session: Session) {
    user.value = session.user
    csrfToken.value = session.csrfToken
  }

  async function load() {
    apply(await $fetch<Session>('/api/session'))
  }

  return { user, csrfToken, isAdmin, apply, load }
}
```

### `useApi`

Semua permintaan tulis lewat sini agar header CSRF tidak pernah terlupa. Token diambil dari sesi; hanya
bila server menjawab 419 (token kedaluwarsa) sesi dimuat ulang dan permintaan diulang sekali. Kode lama
memanggil `/session` sebelum **dan** sesudah setiap mutasi — tiga permintaan untuk satu aksi.

```ts
type Method = 'POST' | 'PATCH' | 'PUT'

export function useApi() {
  const { csrfToken, load } = useSession()

  async function send<T = ApiMessage>(method: Method, path: string, body?: object): Promise<T> {
    const request = () => $fetch<T>(`/api/${path}`, {
      method,
      body,
      headers: { 'X-CSRF-TOKEN': csrfToken.value },
    })

    try {
      return await request()
    } catch (error) {
      if (statusOf(error) !== 419) {
        throw error
      }
      await load()
      return await request()
    }
  }

  return {
    post: <T = ApiMessage>(path: string, body?: object) => send<T>('POST', path, body),
    patch: <T = ApiMessage>(path: string, body?: object) => send<T>('PATCH', path, body),
    put: <T = ApiMessage>(path: string, body?: object) => send<T>('PUT', path, body),
  }
}
```

### `useAction`

Pola `busy / error / success` + `try / catch / finally` ditulis sekali.

```ts
export function useAction() {
  const busy = ref(false)
  const error = ref('')
  const success = ref('')

  /** Menjalankan aksi tulis; mengembalikan true bila berhasil. */
  async function run(task: () => Promise<ApiMessage | void>): Promise<boolean> {
    busy.value = true
    error.value = ''
    success.value = ''
    try {
      const result = await task()
      success.value = result?.message ?? ''
      return true
    } catch (caught) {
      error.value = errorMessage(caught)
      return false
    } finally {
      busy.value = false
    }
  }

  return { busy, error, success, run }
}
```

### `useTable`

Padanan klien dari `TableQuery`: menyimpan `q / sort / direction / per_page / page`, memuat data, dan
menerapkan aturan tabel di `domain.md` § 4 (debounce 300 ms, kembali ke halaman 1, balik arah).

```ts
const table = useTable<LeavePermit>('leave-permits')

table.rows        // Ref<T[]>
table.meta        // Ref<{ current_page, last_page, total } | undefined>
table.loading     // Ref<boolean>
table.loadError   // Ref<boolean>
table.search      // Ref<string> — v-model kotak cari
table.sort, table.direction
table.page, table.perPage         // v-model untuk paginasi
table.sortBy(column)
table.goToFirstPage()
table.refresh()
```

Tiap pemanggilan punya state sendiri, sehingga beberapa tabel di satu halaman tidak saling memengaruhi.

### `usePolling`

```ts
usePolling(table.refresh, { pausedWhen: () => action.busy.value || table.loading.value })
```

Memuat ulang tiap `POLL_INTERVAL_MS` (15 000) selama tab terlihat dan tidak dijeda; membersihkan timer
saat komponen dilepas.

## 4. Tipe

Tipe data API ada di `types/<fitur>.ts`, bukan di dalam komponen, dan mencerminkan Resource backend.

```ts
// types/api.ts
export type Paginated<T> = {
  data: T[]
  meta: { current_page: number; last_page: number; total: number }
}
export type ApiMessage = { message: string }

// types/leave-permit.ts
export type PermitStatus = 'pending' | 'approved' | 'rejected'

export type LeavePermit = {
  id: number
  user_name: string
  leaves_at: string
  returns_at: string
  reason: string
  status: PermitStatus
  status_label: string
}

export type LeavePermitInput = {
  leaves_at: string
  returns_at: string
  reason: string
}
```

Hindari `as` dan `any`; beri tipe generik pada `useApi`/`useTable` sebagai gantinya.

## 5. Utilitas

Fungsi murni tanpa state, di-*auto-import* Nuxt dari `utils/`.

```ts
// utils/datetime.ts — satu-satunya tempat zona waktu ditangani
const WIB = 'Asia/Jakarta'
const WIB_OFFSET_MS = 7 * 60 * 60 * 1000

/** "13 Okt 2026, 14.30 WIB" */
export function formatWib(iso: string, timeStyle: 'short' | 'medium' = 'short'): string {
  const formatter = new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle, timeZone: WIB })
  return `${formatter.format(new Date(iso))} WIB`
}

/** Nilai <input type="datetime-local"> (jam WIB) → ISO UTC untuk API. */
export function wibInputToIso(local: string): string {
  return new Date(`${local}:00+07:00`).toISOString()
}

/** ISO UTC dari API → nilai <input type="datetime-local"> dalam WIB. */
export function isoToWibInput(iso: string): string {
  return new Date(new Date(iso).getTime() + WIB_OFFSET_MS).toISOString().slice(0, 16)
}
```

`utils/currency.ts` → `formatRupiah(value)`. `utils/errors.ts` → `errorMessage(error)` (pesan Indonesia
untuk 401/403/419/429/jaringan, atau gabungan `errors` validasi) dan `statusOf(error)`.

## 6. Komponen `ui/`

Komponen presentasi tanpa pengetahuan fitur. Di sinilah kelas Tailwind yang panjang tinggal, supaya
komponen fitur hanya berisi struktur dan teks.

| Komponen | Tugas |
|---|---|
| `DataTable` | Kotak cari, header yang bisa diurutkan, baris, keadaan kosong/memuat, paginasi |
| `StatusBadge` | Lencana berwarna dari `tone` (`info`, `success`, `warning`, `danger`, `neutral`) |
| `AlertMessage` | Pesan `error` / `success`, dengan tombol "Coba lagi" opsional |
| `PageHeader` | Overline + judul + deskripsi halaman |

`DataTable` menerima objek `useTable` dan daftar kolom; isi sel yang bukan teks polos diberikan lewat
slot bernama sesuai `key` kolom.

```vue
<DataTable
  :table="table"
  :columns="columns"
  search-label="Cari izin keluar"
  empty-text="Belum ada pengajuan izin keluar."
>
  <template #status="{ row }">
    <StatusBadge :tone="PERMIT_TONE[row.status]">
      {{ row.status_label }}
    </StatusBadge>
  </template>
</DataTable>
```

Warna status tiap fitur dipetakan sekali di `types/<fitur>.ts` (mis. `PERMIT_TONE`), bukan dengan
ternary berantai di template.

## 7. Komponen fitur — contoh izin keluar

Tiap fitur dipecah tiga: **Section** (pemilik data dan aksi), **Form**, **Table**. Form dan Table hanya
menerima props dan memancarkan event, sehingga masing-masing muat di satu layar dan bisa dibaca terpisah.

### `components/leave-permit/LeavePermitSection.vue`

```vue
<script setup lang="ts">
import type { LeavePermit, LeavePermitInput, PermitStatus } from '~/types/leave-permit'

const { isAdmin } = useSession()
const api = useApi()
const action = useAction()
const table = useTable<LeavePermit>('leave-permits')
const editing = ref<LeavePermit | null>(null)

usePolling(table.refresh, { pausedWhen: () => action.busy.value || table.loading.value })

async function save(input: LeavePermitInput) {
  const permit = editing.value
  const saved = await action.run(() =>
    permit ? api.patch(`leave-permits/${permit.id}`, input) : api.post('leave-permits', input),
  )
  if (saved) {
    editing.value = null
    table.goToFirstPage()
  }
  await table.refresh()
}

async function review(permit: LeavePermit, status: PermitStatus) {
  await action.run(() => api.patch(`leave-permits/${permit.id}/review`, { status }))
  await table.refresh()
}

// Admin bisa memproses izin saat penghuni sedang mengeditnya.
watch(table.rows, (rows) => {
  const current = rows.find(row => row.id === editing.value?.id)
  if (current && current.status !== 'pending') {
    editing.value = null
    action.error.value = 'Pengajuan sudah diproses admin dan tidak dapat diedit.'
  }
})
</script>

<template>
  <section
    aria-label="Izin keluar"
    class="card mt-6 p-4 md:p-6"
  >
    <h2 class="section-title">
      Izin keluar
    </h2>
    <p class="muted mt-2">
      {{ isAdmin ? 'Tinjau pengajuan izin keluar penghuni.' : 'Pengajuan dapat diedit selama berstatus Diajukan.' }}
      Semua waktu menggunakan WIB.
    </p>

    <AlertMessage
      :error="action.error.value"
      :success="action.success.value"
    />

    <LeavePermitForm
      v-if="!isAdmin"
      :permit="editing"
      :busy="action.busy.value"
      @save="save"
      @cancel="editing = null"
    />

    <LeavePermitTable
      :table="table"
      :busy="action.busy.value"
      :admin="isAdmin"
      @edit="editing = $event"
      @review="review"
    />
  </section>
</template>
```

### `components/leave-permit/LeavePermitForm.vue`

```vue
<script setup lang="ts">
import type { LeavePermit, LeavePermitInput } from '~/types/leave-permit'

const props = defineProps<{
  permit: LeavePermit | null
  busy: boolean
}>()

const emit = defineEmits<{
  save: [input: LeavePermitInput]
  cancel: []
}>()

const form = reactive({ leavesAt: '', returnsAt: '', reason: '' })

watch(() => props.permit, (permit) => {
  form.leavesAt = permit ? isoToWibInput(permit.leaves_at) : ''
  form.returnsAt = permit ? isoToWibInput(permit.returns_at) : ''
  form.reason = permit?.reason ?? ''
}, { immediate: true })

function submit() {
  emit('save', {
    leaves_at: wibInputToIso(form.leavesAt),
    returns_at: wibInputToIso(form.returnsAt),
    reason: form.reason,
  })
}
</script>

<template>
  <form
    class="mt-5 space-y-4"
    @submit.prevent="submit"
  >
    <h3 class="font-semibold">
      {{ permit ? 'Edit pengajuan izin' : 'Ajukan izin keluar' }}
    </h3>

    <div class="grid gap-4 sm:grid-cols-2">
      <label>
        Waktu keluar (WIB)
        <input
          v-model="form.leavesAt"
          type="datetime-local"
          required
          :disabled="busy"
        >
      </label>
      <label>
        Waktu kembali (WIB)
        <input
          v-model="form.returnsAt"
          type="datetime-local"
          required
          :min="form.leavesAt || undefined"
          :disabled="busy"
        >
      </label>
    </div>

    <label>
      Alasan
      <textarea
        v-model="form.reason"
        rows="3"
        maxlength="2000"
        required
        :disabled="busy"
      />
    </label>

    <div class="flex gap-3">
      <button
        class="btn"
        :disabled="busy"
      >
        {{ permit ? 'Simpan perubahan izin' : 'Ajukan izin' }}
      </button>
      <button
        v-if="permit"
        type="button"
        class="btn-text"
        :disabled="busy"
        @click="emit('cancel')"
      >
        Batal edit
      </button>
    </div>
  </form>
</template>
```

`LeavePermitTable.vue` mengikuti pola yang sama: mendefinisikan `columns`, merender `DataTable`, dan
memancarkan `edit` / `review`. Kolom "Nama user" hanya ada bila `admin`.

Form di-reset oleh induknya dengan mengganti prop `permit` (atau `key`), bukan dengan induk mengubah
state anak.

## 8. Halaman

Halaman hanya berisi meta, judul, dan susunan komponen. Tidak ada pengambilan data atau logika fitur di
halaman kecuali data yang memang milik halaman itu.

```vue
<script setup lang="ts">
definePageMeta({ role: 'resident' })
useHead({ title: 'Kamar saya · Gojags Asrama' })
</script>

<template>
  <div>
    <PageHeader title="Kamar saya" />
    <MyRoomCard />
    <StayHistory />
    <LeavePermitSection />
    <ComplaintSection />
  </div>
</template>
```

## 9. Format template

Kode lama menulis seluruh tabel dalam satu baris sepanjang 700 karakter. Aturan berikut membuat struktur
halaman terlihat dari indentasinya dan membuat *diff* mudah ditinjau:

- Satu elemen per baris; anak selalu diindentasi di bawah induknya.
- Elemen dengan lebih dari dua atribut: satu atribut per baris.
- Urutan atribut: `v-if`/`v-for` → `key`/`ref` → `v-model` → atribut biasa → `:binding` → `@event`.
- Tidak ada logika di template selain ekspresi pendek. Ternary bersarang, `.map().join()`, dan beberapa
  pernyataan dalam satu `@click` pindah ke `computed` atau fungsi bernama.
- Maksimal 120 karakter per baris, juga di `<script>`. Satu pernyataan per baris — tanpa
  `busy.value = true; error.value = ''` atau `try { … } catch { … }` sebaris.
- Urutan `<script setup>`: impor tipe → props/emits → composable → state → computed → fungsi → watcher
  dan lifecycle.
- Komponen di atas kira-kira 150 baris perlu dipecah.
- Aksesibilitas yang sudah ada dipertahankan: `aria-label` pada section, tabel, dan kotak cari;
  `role="alert"` / `role="status"` pada pesan; `aria-sort` pada header.

## 10. Gaya (Tailwind)

- Kelas yang muncul di lebih dari satu tempat menjadi komponen `ui/` (bila punya markup) atau kelas di
  `@layer components` pada `main.css` (bila hanya gaya: `.card`, `.btn`, `.btn-text`, `.muted`,
  `.overline`, `.section-title`).
- Gaya dasar `input`, `select`, **dan `textarea`** didefinisikan di `@layer base`, sehingga form tidak
  mengulang kelas border/focus.
- Varian gelap ditulis di tempat yang sama dengan varian terang (di komponen `ui/` atau `main.css`),
  tidak tersebar di komponen fitur.
- Skrip inisialisasi tema hanya satu salinan: `frontend/public/js/theme.js`.

## 11. Test

Satu spec Playwright per fitur di `frontend/tests/browser/`, login dengan akun seed, memilih elemen lewat
peran dan label (`getByRole`, `getByLabel`) — bukan kelas CSS. Backend dan Nuxt harus sudah berjalan
dengan basis data ter-seed; `workers: 1` karena tes berbagi satu basis data. Jalankan
`npm run typecheck` sebelum Playwright.
