# AGENTS.md — Gojags Asrama

Panduan konteks arsitektur, alur kerja pengembangan, aturan domain, dan konvensi kode untuk AI Coding Agent yang bekerja pada repositori **Gojags Asrama**.

---

## 1. Gambaran Umum Proyek & Tech Stack

**Gojags Asrama** adalah sistem manajemen operasional asrama berbasis web dengan pemisahan peran (`admin` dan `user`/penghuni) untuk pengelolaan alokasi kamar, check-in/check-out, laundry, catering & rating, izin keluar, serta keluhan fasilitas asrama.

- **Backend**: Laravel 10 (PHP `^8.2`), menggunakan Query Builder (`DB::table(...)`) & Eloquent `User`, autentikasi berbasis session + CSRF (`web` middleware group).
- **Database**: SQLite (`database/database.sqlite` pada development, `:memory:` pada pengujian). Skema lengkap terdokumentasi di `dbdiagram.txt`.
- **Frontend Utama (SPA)**: Nuxt 4 (`ssr: false`), Vue 3.5, TypeScript, Tailwind CSS v4 (`frontend/`).
- **Proxy Komunikasi**: Nuxt Nitro server route (`frontend/server/routes/backend/[...path].ts`) meneruskan request `/backend/api/asrama/*` ke backend Laravel (`http://127.0.0.1:8000/api/asrama/*`).
- **Testing**:
  - Backend: PHPUnit 10 (`tests/Feature/`, `tests/Unit/`)
  - Frontend: Smoke script (`frontend/scripts/smoke.mjs`) & Playwright E2E (`frontend/tests/browser/*.spec.ts`)

---

## 2. Perintah Penting (Commands)

### Menjalankan Aplikasi (Development)
- **Backend + Frontend bersamaan (dari root)**:
  ```bash
  npm run dev:asrama
  ```
- **Backend Laravel saja (root)**:
  ```bash
  php artisan serve --host=127.0.0.1 --port=8000
  ```
- **Frontend Nuxt saja (`frontend/`)**:
  ```bash
  npm --prefix frontend run dev
  ```

### Database & Seeding
- Migrasi & seed akun bawaan:
  ```bash
  php artisan migrate --seed
  ```
- Akun default (`Database\Seeders\AsramaAccountSeeder`):
  - **Admin**: `admin@example.id` / `admin123` (`role: admin`)
  - **User**: `user@example.id` / `user123` (`role: user`)

### Pengujian & Pemeriksaan Kualitas
- **Test Backend (PHPUnit)**:
  ```bash
  composer test
  # atau
  php artisan test
  ```
- **Code Formatting PHP (Laravel Pint)**:
  ```bash
  ./vendor/bin/pint --test
  ```
- **Typecheck Frontend (Nuxt / Vue TSC)**:
  ```bash
  npm --prefix frontend run typecheck
  ```
- **Smoke & Browser E2E Test (Frontend)**:
  ```bash
  npm --prefix frontend run test:smoke
  npm --prefix frontend run test:browser
  ```

---

## 3. Struktur Direktori Utama

```text
├── app/
│   ├── Http/
│   │   ├── Controllers/       # Controller domain (Asrama, RoomAllocation, UserRoom, Catering, UserLaundry, LeavePermission, Complaint)
│   │   └── Middleware/        # Authenticate, RequireRole ('role:admin' / 'role:user')
│   ├── Models/
│   │   └── User.php           # Model autentikasi utama
│   └── Services/
│       ├── RoomAllocator.php  # Algoritma alokasi kamar otomatis berbasis transaksi & row locking
│       └── RoomStatus.php     # Kalkulasi & sinkronisasi status kamar (Kosong, Assigned, Terisi, Penuh)
├── database/
│   ├── migrations/            # Migrasi tabel inti & layanan asrama
│   └── seeders/               # AsramaAccountSeeder, AsramaUserSeeder, DatabaseSeeder
├── frontend/
│   ├── app/
│   │   ├── assets/css/        # Stylesheet global & konfigurasi Tailwind v4 (main.css)
│   │   ├── components/        # Komponen UI reusable (CateringPanel, LaundryPanel, LeavePermissions, RoomComplaints, dll.)
│   │   ├── composables/       # useAsrama.ts (state auth, CSRF, fetch mutasi), useTableSearch.ts
│   │   ├── layouts/           # default.vue (dashboard sidebar/topbar), auth.vue (halaman login)
│   │   ├── middleware/        # auth.global.ts (proteksi rute & redirect sesuai role)
│   │   └── pages/             # Halaman admin (/admin, /kamar, /laundry, /catering) & penghuni (/user/*)
│   ├── server/routes/backend/ # Proxy Nitro ([...path].ts) ke endpoint /api/asrama/*
│   └── tests/browser/         # Pengujian end-to-end Playwright
├── routes/
│   └── web.php                # Seluruh definisi rute JSON (/api/asrama/*) dan rute web
├── tests/
│   └── Feature/               # Feature test Laravel per modul domain
└── dbdiagram.txt              # Dokumentasi skema DBML lengkap
```

---

## 4. Aturan Domain & Logika Bisnis Inti

1. **Autentikasi & Otorisasi Peran (`role`)**:
   - Setiap user memiliki `role` bernilai `'admin'` atau `'user'`.
   - Middleware `role:admin` dan `role:user` (`App\Http\Middleware\RequireRole`) membatasi akses endpoint.
   - Rute JSON di bawah `/api/asrama/*` berada di `routes/web.php` agar mendapatkan proteksi session dan CSRF token dari middleware `web`.

2. **Manajemen Kamar, Slot, & Alokasi Otomatis (`kamar`, `kamar_slots`, `kamar_stays`)**:
   - Gedung asrama memiliki lantai `3`, `4`, dan `5`, dengan kapasitas maksimal **2 slot per kamar** (`nomor` 1 dan 2 di `kamar_slots`).
   - Status kamar (`App\Services\RoomStatus`):
     - `Kosong`: 0 slot terisi.
     - `Assigned`: Ada penghuni yang dialokasikan tetapi belum semua yang dialokasikan melakukan check-in (`assigned > checked_in`).
     - `Terisi`: 1 penghuni sudah check-in dan tidak ada slot `Assigned` yang tertunda.
     - `Penuh`: 2 penghuni sudah check-in.
   - Setiap perubahan pada `kamar_slots` (alokasi, check-in, check-out) **wajib** memanggil `RoomStatus::sync($kamarId)`.
   - `RoomAllocator::allocate()` mengalokasikan penghuni (`role = 'user'`) yang memiliki profil lengkap (`angkatan`, `gender`, `agama`), belum memiliki slot aktif, dan belum pernah check-out (`kamar_stays.checked_out_at IS NOT NULL`). Alokasi memprioritaskan lantai dari atas ke bawah (5 → 4 → 3) serta kecocokan `gender` dan `agama` dalam satu kamar.
   - Untuk mencegah deadlock, transaksi alokasi dan pembaruan profil selalu mengambil lock (`lockForUpdate()`) dengan urutan tabel: `kamar` → `users` → `kamar_slots`.

3. **Layanan Laundry (`laundry`)**:
   - Penghuni mengajukan laundry (`status: 'Diajukan'`) dengan jumlah stel (`jumlah_stel`) dan deskripsi pakaian (`detail`).
   - Admin memproses dan menyelesaikan laundry (`status: 'Selesai'`), di mana pengisian `biaya` wajib saat status diubah menjadi `Selesai`.

4. **Layanan Catering & Rating (`catering`, `catering_ratings`)**:
   - Status catering: `Terjadwal` → `Disiapkan` → `Selesai`.
   - `waktu_catering` disimpan dalam **UTC** di database.
   - Penghuni hanya dapat memberikan rating (`1` s.d. `5`) sebanyak 1 kali per item catering (`unique(catering_id, user_id)`).

5. **Izin Keluar (`izin_keluar`)**:
   - Penghuni mengajukan izin dengan `waktu_keluar`, `waktu_kembali` (wajib setelah `waktu_keluar`), dan `alasan`.
   - Status: `Diajukan` (dapat diedit oleh penghuni selama masih `Diajukan`), kemudian ditinjau oleh admin menjadi `Disetujui` atau `Ditolak` (`reviewed_by`, `reviewed_at`).

6. **Keluhan Asrama (`keluhan_asrama`)**:
   - Penghuni mengirim keluhan (`judul`, `isi`) dengan status awal `Diajukan`.
   - Admin menyelesaikan keluhan sehingga status berubah menjadi `Resolved` (`resolved_by`, `resolved_at`).

---

## 5. Konvensi Pengembangan untuk Agent

- **Bahasa Antarmuka & Pesan Validasi**: Gunakan Bahasa Indonesia untuk label UI, pesan error/validasi pengguna, dan status domain (`Kosong`, `Diajukan`, `Disetujui`, `Selesai`, dll.).
- **Komunikasi Frontend-Backend**:
  - Di Nuxt (`frontend/`), gunakan composable `useAsrama()` (`mutate()` untuk `POST`/`PATCH` dengan header `X-CSRF-TOKEN` otomatis, dan `$fetch('/backend/api/asrama/...')` untuk `GET`).
  - Proxy `frontend/server/routes/backend/[...path].ts` hanya mengizinkan path yang cocok dengan regex `/^api\/asrama\/[a-z0-9/-]+$/`. Pastikan endpoint baru di `routes/web.php` berada di dalam grup `Route::prefix('api/asrama')` dan mengikuti pola karakter tersebut.
- **Perubahan Skema Database**:
  - Selalu buat file migration baru di `database/migrations/` dan perbarui dokumentasi skema di `dbdiagram.txt` agar tetap sinkron.
- **Pengujian (Testing)**:
  - Setiap penambahan fitur atau perbaikan bug pada backend harus disertai atau diverifikasi dengan feature test di `tests/Feature/` (`php artisan test`).
  - Untuk perubahan pada halaman atau komponen Nuxt, jalankan `npm --prefix frontend run typecheck` untuk memastikan tidak ada error TypeScript.
  - Jangan menghapus komentar penjelasan non-trivial yang sudah ada di codebase.
