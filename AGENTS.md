# AGENTS.md — Gojags Asrama

Panduan konteks arsitektur, alur kerja pengembangan, aturan domain, dan konvensi kode untuk AI Coding Agent yang bekerja pada repositori **Gojags Asrama**.

---

## 1. Gambaran Umum Proyek & Tech Stack

**Gojags Asrama** adalah sistem manajemen operasional asrama berbasis web dengan pemisahan peran (`admin` dan `resident`/penghuni) untuk pengelolaan alokasi kamar, check-in/check-out, izin keluar, keluhan fasilitas asrama, laundry, serta catering dan rating.

- **Backend**: Laravel 10 (PHP `^8.2`), menggunakan Eloquent Models, Backed Enums (`app/Enums/`), Action classes (`app/Actions/`), FormRequests (`app/Http/Requests/`), dan JsonResources (`app/Http/Resources/`), dengan autentikasi berbasis session cookie + CSRF (`web` middleware group pada `routes/api.php`).
- **Database**: MySQL (`gojags` pada development, `gojags_asrama_testing` pada PHPUnit, `gojags_asrama_e2e` pada E2E). Skema lengkap terdokumentasi di `dbdiagram.txt`.
- **Frontend (SPA)**: Nuxt 4 (`ssr: false`), Vue 3.5, TypeScript, Tailwind CSS v4 (`frontend/`).
- **Proxy Komunikasi**: Nuxt Nitro server route (`frontend/server/routes/api/[...path].ts`) meneruskan request `/api/*` ke backend Laravel (`http://127.0.0.1:8000/api/*`).
- **Testing**:
  - Backend: PHPUnit 10 (`tests/Unit/`, `tests/Feature/`)
  - Frontend: Vitest (`frontend/tests/unit/*.test.ts`), Playwright UI tiruan (`frontend/tests/browser/*.spec.ts`), Playwright E2E (`frontend/tests/e2e/*.spec.ts`), dan smoke script (`frontend/scripts/smoke.mjs`).

---

## 2. Perintah Penting (Commands)

### Menjalankan Aplikasi (Development)
- **Backend Laravel (root)**:
  ```powershell
  php artisan serve --host=127.0.0.1 --port=8000
  ```
- **Frontend Nuxt (`frontend/`)**:
  ```powershell
  npm --prefix frontend run dev
  ```

### Database & Seeding
- Migrasi & seed inventaris kamar serta akun bawaan:
  ```powershell
  php artisan migrate --seed
  ```
- Akun default (`Database\Seeders\AccountSeeder`):
  - **Admin**: `admin@example.id` / `admin123` (`role: admin`)
  - **Penghuni**: `user@example.id` / `user123` (`role: resident`)
  - **Penghuni tambahan**: `user1@example.id` s.d. `user10@example.id` / `user123` (`role: resident`)

### Pengujian & Pemeriksaan Kualitas
- **Test Backend (PHPUnit)**:
  ```powershell
  composer test
  # atau
  php artisan test
  ```
- **Code Formatting PHP (Laravel Pint)**:
  ```powershell
  ./vendor/bin/pint --test
  ```
- **Typecheck & Unit Test Frontend (Nuxt / Vitest)**:
  ```powershell
  npm --prefix frontend run typecheck
  npm --prefix frontend run test:unit
  ```
- **Browser Tiruan, E2E, & Smoke Test (Frontend)**:
  ```powershell
  npm --prefix frontend run test:browser
  npm --prefix frontend run test:e2e
  npm --prefix frontend run test:smoke
  ```

---

## 3. Struktur Direktori Utama

```text
├── app/
│   ├── Actions/               # Logika bisnis (AllocateRooms, CheckIn, CheckOut, UpdateResidentProfile)
│   ├── Enums/                 # Backed enum (Role, Gender, RoomStatus, LaundryStatus, CateringStatus, MealTime, ...)
│   ├── Http/
│   │   ├── Controllers/       # Controller REST tipis per sumber daya
│   │   ├── Middleware/        # Authenticate, RequireRole ('role:admin' / 'role:resident')
│   │   ├── Requests/          # FormRequest validasi dengan pesan Bahasa Indonesia
│   │   └── Resources/         # JsonResource transformasi respons JSON
│   ├── Models/                # Model Eloquent (User, Room, RoomSlot, Stay, LaundryOrder, CateringMenu, ...)
│   └── Support/               # DateFormat, TableQuery, RoomLocks, RoomOccupancy
├── config/
│   └── asrama.php             # Konfigurasi lantai, kapasitas kamar, opsi paginasi, display_timezone
├── database/
│   ├── factories/             # Factory Eloquent untuk pengujian
│   ├── migrations/            # Migrasi tabel inti & layanan asrama
│   └── seeders/               # RoomSeeder, AccountSeeder, DatabaseSeeder
├── frontend/
│   ├── app/
│   │   ├── assets/css/        # Stylesheet global & konfigurasi Tailwind v4 (main.css)
│   │   ├── components/        # Komponen terkelompok per domain (ui/, layout/, auth/, dashboard/, room/, ...)
│   │   ├── composables/       # useSession.ts, useApi.ts, useAction.ts, usePolling.ts, useTable.ts
│   │   ├── layouts/           # default.vue (sidebar/header/footer), auth.vue (halaman login)
│   │   ├── middleware/        # auth.global.ts (proteksi rute via awalan URL tanpa definePageMeta)
│   │   ├── pages/             # Halaman admin (/admin, /admin/rooms, ...) & penghuni (/resident, ...)
│   │   ├── types/             # Definisi tipe data API & peta warna status
│   │   └── utils/             # datetime.ts, currency.ts, errors.ts, navigation.ts
│   ├── server/routes/api/     # Proxy Nitro ([...path].ts) ke endpoint /api/*
│   └── tests/                 # Pengujian unit (Vitest), browser tiruan (Playwright), dan E2E (Playwright)
├── routes/
│   ├── api.php                # Seluruh definisi rute JSON (/api/*)
│   └── web.php                # Endpoint kesehatan (/up)
├── tests/
│   ├── Feature/               # Feature test Laravel per modul domain
│   └── Unit/                  # Unit test murni (RoomStatusTest)
└── dbdiagram.txt              # Dokumentasi skema DBML lengkap
```

---

## 4. Aturan Domain & Logika Bisnis Inti

1. **Autentikasi & Otorisasi Peran (`role`)**:
   - Setiap user memiliki `role` bernilai `'admin'` (`Role::Admin`) atau `'resident'` (`Role::Resident`).
   - Middleware `role:admin` dan `role:resident` (`App\Http\Middleware\RequireRole`) membatasi akses endpoint.
   - Endpoint login dibatasi oleh rate limiter `login` (maksimal 5 percobaan per menit per kombinasi email).
   - Di frontend, `auth.global.ts` membatasi navigasi berdasarkan awalan URL (`/admin` dan `/resident`).
   - Rute JSON di `routes/api.php` berjalan di bawah grup middleware `web` agar mendapatkan proteksi session dan CSRF.

2. **Manajemen Kamar, Slot, & Alokasi Otomatis (`rooms`, `room_slots`, `stays`)**:
   - Gedung asrama memiliki lantai `5`, `4`, dan `3` (`config('asrama.floors')`), kapasitas 2 slot per kamar.
   - Status kamar (`App\Enums\RoomStatus`, dihitung dari `room_slots` tanpa kolom status di tabel `rooms`):
     - `empty` (`Kosong`): 0 slot terisi.
     - `assigned` (`Assigned`): Ada penghuni dialokasikan tetapi belum semua check-in (`assigned > checked_in`).
     - `occupied` (`Terisi`): 1 penghuni sudah check-in dan tidak ada slot `assigned` yang tertunda.
     - `full` (`Penuh`): 2 penghuni sudah check-in.
   - `AllocateRooms::handle()` mengalokasikan penghuni (`role = 'resident'`) yang berprofil lengkap dan belum pernah
     check-out. Alokasi memprioritaskan lantai dari atas ke bawah (5 → 4 → 3) serta kesamaan `gender` dan `religion`.
   - Untuk mencegah deadlock, transaksi alokasi, pembaruan profil, check-in, dan check-out selalu mengambil lock
     (`RoomLocks`) dengan urutan tabel: `rooms` → `users` → `room_slots`.

3. **Layanan Laundry (`laundry_orders`)**:
   - Penghuni mengajukan laundry (`status: 'pending'` / label `Diajukan`) dengan jumlah stel dan deskripsi pakaian.
   - Admin memproses dan menyelesaikan laundry (`status: 'done'` / label `Selesai`), dengan `cost` opsional.

4. **Layanan Catering & Rating (`catering_menus`, `catering_ratings`)**:
   - Menyimpan `dish_name`, `meal_time` (`MealTime`: `breakfast`, `lunch`, `dinner`), `provider`, dan `served_at`.
   - Admin dapat menambah, mengubah, dan menghapus menu catering (`DELETE /api/catering-menus/{menu}`).
   - Penilaian menu oleh penghuni dibuka saat menu telah dihidangkan (`served_at`) dan ditutup pada pukul 23.59 WIB
     pada tanggal menu dihidangkan (`ratingClosesAt()`).
   - Rating (`1` s.d. `5`) disimpan dalam transaksi unik (`unique(catering_menu_id, user_id)`).

5. **Izin Keluar (`leave_permits`)**:
   - Penghuni mengajukan izin dengan `leaves_at`, `returns_at` (wajib setelah `leaves_at`), dan `reason`.
   - Status (`PermitStatus`): `pending` (`Diajukan`, dapat diedit oleh penghuni selama masih `pending`), kemudian ditinjau oleh admin menjadi `approved` (`Disetujui`) atau `rejected` (`Ditolak`) (`reviewed_by`, `reviewed_at`).

6. **Keluhan Asrama (`complaints`)**:
   - Penghuni mengirim keluhan (`title`, `body`) dengan status awal `pending` (`Diajukan`).
   - Admin menyelesaikan keluhan sehingga status berubah menjadi `resolved` (`Resolved`) secara idempoten (`resolved_by`, `resolved_at`).

---

## 5. Konvensi Pengembangan untuk Agent

- **Bahasa**: Gunakan Bahasa Inggris untuk seluruh penanda kode (nama kelas, fungsi, variabel, kolom, rute, berkas) dan Bahasa Indonesia untuk label UI serta pesan validasi pengguna.
- **Komunikasi Frontend-Backend**:
  - Di Nuxt (`frontend/`), gunakan composable `useApi()` (`post`, `patch`, `put` dengan header `X-CSRF-TOKEN` otomatis dan retry satu kali saat `419`, serta `get`), `useTable<T>(path)` untuk tabel berpaginasi, dan `useAction()` untuk state mutasi.
  - Proxy `frontend/server/routes/api/[...path].ts` hanya mengizinkan path yang cocok dengan regex `/^[a-z0-9/-]+$/`.
- **Perubahan Skema Database**:
  - Selalu buat atau perbarui file migration di `database/migrations/` dan perbarui dokumentasi skema di `dbdiagram.txt` agar tetap sinkron.
- **Pengujian & Kualitas Kode**:
  - Panjang baris maksimum **120 karakter** di seluruh berkas PHP, Vue, dan TypeScript.
  - Setiap penambahan fitur atau perbaikan bug harus diverifikasi dengan `composer test`, `./vendor/bin/pint --test`, `npm --prefix frontend run typecheck`, `npm --prefix frontend run test:unit`, dan `npm --prefix frontend run test:browser`.
