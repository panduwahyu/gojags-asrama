# CLAUDE.md

Panduan arsitektur, alur permintaan, struktur direktori, skema data, dan pengujian untuk repositori **Gojags Asrama**.

Subsistem Manajemen Asrama GOJAGS (Pusdiklat BPS): backend **Laravel 10** JSON API (PHP `^8.2`) di root repo dan frontend **Nuxt 4** SPA (`ssr: false`, Tailwind CSS v4) di `frontend/`. Semua penanda kode (nama kelas, variabel, kolom, URL, berkas) berbahasa Inggris, sedangkan seluruh teks antarmuka dan pesan validasi berbahasa Indonesia.

## Perintah

```powershell
# Backend (root repo)
php artisan serve --host=127.0.0.1 --port=8000
php artisan migrate --seed
composer test
php artisan test tests/Feature/RoomAllocationTest.php
php artisan test --filter=nama_method_test
vendor/bin/pint --test

# Frontend (frontend/)
npm --prefix frontend run dev          # http://127.0.0.1:3000
npm --prefix frontend run build
npm --prefix frontend run typecheck
npm --prefix frontend run test:unit    # Vitest (tests/unit/*.test.ts)
npm --prefix frontend run test:browser # Playwright tiruan (tests/browser/*.spec.ts, port 3100)
npm --prefix frontend run test:e2e     # Playwright E2E (tests/e2e/*.spec.ts, port 3100 & 8100)
npm --prefix frontend run test:smoke   # node scripts/smoke.mjs terhadap server berjalan
```

- Akun seed (`Database\Seeders\AccountSeeder`):
  - **Admin**: `admin@example.id` / `admin123` (`role: admin`)
  - **Penghuni**: `user@example.id` / `user123` (`role: resident`), serta `user1..10@example.id` / `user123` (`religion` awal kosong untuk pengujian alokasi).
- Alamat backend untuk proxy Nuxt: `NUXT_LARAVEL_BASE_URL` (bawaan `http://127.0.0.1:8000`).

## Arsitektur

### Alur Permintaan

Peramban hanya berkomunikasi dengan Nuxt. `frontend/server/routes/api/[...path].ts` meneruskan permintaan `/api/<path>` ke `${laravelBaseUrl}/api/<path>` beserta query string (hanya mengizinkan path yang cocok dengan `^[a-z0-9/-]+$`, memaksa `Accept: application/json`, dan menyetel `Cache-Control: no-store`, serta menimpa `X-Forwarded-For` dengan IP klien).

- Seluruh rute JSON berada di `routes/api.php` yang dimuat oleh `RouteServiceProvider` dengan prefiks `/api` dan grup middleware `['web', 'cache.headers:no_store']`, sehingga sesi dan CSRF tetap berbasis cookie pada satu origin.
- `GET /api/session` mengembalikan `{ csrfToken, user }`.
- `POST /api/login` dilindungi rate limiter `login` (maksimal 5 percobaan per menit per kombinasi email + IP klien, dan batas longgar per IP dari config/asrama.php; IP diteruskan proxy Nuxt lewat X-Forwarded-For).
- Di frontend, `useSession()` menyimpan state sesi, `useApi()` mengirim permintaan `GET`/`POST`/`PATCH`/`PUT`/`DELETE`
  ke `/api/...` dengan header `X-CSRF-TOKEN` otomatis (serta memuat ulang sesi dan mengulang sekali bila menerima `419`),
  dan `useAction()` mengelola state `busy`, `error`, dan `success`.
- `frontend/app/middleware/auth.global.ts` membatasi akses berdasarkan awalan URL (`/admin` untuk admin,
  `/resident` untuk resident) tanpa `definePageMeta`. Otorisasi sebenarnya ditegakkan di backend melalui
  middleware `auth` dan `role:admin` / `role:resident` (`App\Http\Middleware\RequireRole`).
  Admin dapat menghapus menu catering melalui `DELETE /api/catering-menus/{menu}`.

### Struktur Direktori

```text
├── app/
│   ├── Actions/               # Logika bisnis lintas-model (AllocateRooms, CheckIn, CheckOut, UpdateResidentProfile)
│   ├── Enums/                 # Backed enum domain (Role, Gender, RoomStatus, LaundryStatus, CateringStatus, MealTime, PermitStatus, ComplaintStatus)
│   ├── Http/
│   │   ├── Controllers/       # Controller REST tipis (Session, Dashboard, Room, RoomAllocation, Resident, MyRoom, LeavePermit, Complaint, LaundryOrder, CateringMenu, CateringRating)
│   │   ├── Middleware/        # Authenticate, RequireRole
│   │   ├── Requests/          # FormRequest per aksi tulis dengan pesan validasi Bahasa Indonesia
│   │   └── Resources/         # JsonResource per entitas (tanpa pembungkus per baris)
│   ├── Models/                # Model Eloquent (User, Room, RoomSlot, Stay, LaundryOrder, CateringMenu, CateringRating, LeavePermit, Complaint)
│   └── Support/               # DateFormat, TableQuery, RoomLocks, RoomOccupancy
├── config/
│   └── asrama.php             # Konstanta konfigurasi domain (floors, room_capacity, rooms_per_floor, per_page_options)
├── database/
│   ├── factories/             # Factory Eloquent untuk seluruh model
│   ├── migrations/            # Satu migrasi create_<table_name>_table per tabel
│   └── seeders/               # RoomSeeder, AccountSeeder, DatabaseSeeder
├── frontend/
│   ├── app/
│   │   ├── assets/css/        # Stylesheet global Tailwind v4 (main.css)
│   │   ├── components/        # Komponen UI (ui/, layout/, auth/, dashboard/, room/, leave-permit/, complaint/, laundry/, catering/)
│   │   ├── composables/       # useSession, useApi, useAction, usePolling, useTable
│   │   ├── layouts/           # default.vue, auth.vue
│   │   ├── middleware/        # auth.global.ts
│   │   ├── pages/             # login.vue, index.vue, admin/*, resident/*
│   │   ├── types/             # Tipe TypeScript per fitur dan peta warna lencana
│   │   └── utils/             # datetime.ts, currency.ts, errors.ts, navigation.ts
│   ├── server/routes/api/     # Proxy Nitro ([...path].ts) ke /api/*
│   └── tests/                 # unit/ (Vitest), browser/ (Playwright tiruan), e2e/ (Playwright E2E), support/
├── routes/
│   ├── api.php                # Seluruh endpoint JSON /api/*
│   └── web.php                # Endpoint kesehatan /up
├── tests/
│   ├── Feature/               # Feature test PHPUnit per modul domain
│   └── Unit/                  # Unit test murni (RoomStatusTest)
└── dbdiagram.txt              # Dokumentasi skema DBML lengkap
```

### Data & Domain

- Semua akses basis data menggunakan model Eloquent (`User`, `Room`, `RoomSlot`, `Stay`, `LaundryOrder`, `CateringMenu`, `CateringRating`, `LeavePermit`, `Complaint`) dan enum di `app/Enums/`.
- Semua kolom waktu disimpan dalam **UTC** dan dikirim ke klien sebagai string ISO-8601 UTC (`...Z`). Konversi ke/dari zona waktu tampilan **WIB (`Asia/Jakarta`)** dilakukan di satu tempat pada frontend (`frontend/app/utils/datetime.ts`).
- `App\Support\TableQuery` menyatukan pencarian (`q`), pengurutan yang di-whitelist (`sort`, `direction`), dan paginasi (`per_page` 10/50/100); padanan kliennya adalah composable `useTable<T>(path)` dan komponen generik `DataTable.vue`.
- Status kamar (`RoomStatus`: `empty`, `assigned`, `occupied`, `full`) dihitung langsung dari `room_slots` tanpa kolom status di tabel `rooms`.
- `App\Support\RoomLocks` mengunci tabel dengan urutan tetap `rooms` → `users` → `room_slots` di dalam transaksi untuk mencegah deadlock pada alokasi otomatis, pembaruan profil penghuni, check-in, dan check-out.
- `App\Models\Concerns\VisibleToOwner` menyediakan scope `visibleTo($user)`: penghuni hanya melihat miliknya, admin melihat semuanya (dipakai `LeavePermit`, `Complaint`, `LaundryOrder`).

### Pengujian

- **Unit PHP (`tests/Unit/`)**: menguji logika murni tanpa basis data (enum, RoomStatus::fromCounts, accessor model).
- **Feature API (`tests/Feature/`)**: menguji seluruh aturan domain, validasi, dan batas peran terhadap basis data pengujian.
- **Unit TS (`frontend/tests/unit/`)**: menguji fungsi murni di `frontend/app/utils/` dengan Vitest.
- **UI Tiruan (`frontend/tests/browser/`)**: menguji perilaku komponen dan halaman Nuxt dengan Playwright (`page.route`), tanpa memerlukan Laravel.
- **E2E (`frontend/tests/e2e/`)**: menguji alur lintas peran menembus Nuxt, proxy, Laravel, dan basis data khusus `gojags_asrama_e2e` yang di-reset melalui `frontend/scripts/reset-e2e-db.mjs`.
