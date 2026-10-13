# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

Subsistem Manajemen Asrama GOJAGS (Pusdiklat BPS): backend **Laravel 10** (PHP ^8.2) di root repo dan frontend **Nuxt 4** SPA (`ssr: false`, Tailwind 4) di `frontend/`. Teks antarmuka dan pesan validasi berbahasa Indonesia. `README.md` masih bawaan Laravel dan tidak memuat informasi proyek.

## Perintah

```powershell
# Backend (root repo)
composer setup                       # install, salin .env, key:generate, migrate, npm install + build
php artisan serve                    # http://127.0.0.1:8000
php artisan migrate --seed           # skema + akun demo
composer test                        # config:clear lalu php artisan test (SQLite :memory:)
php artisan test tests/Feature/RoomAllocationTest.php
php artisan test --filter=nama_method_test
vendor/bin/pint                      # format PHP

# Frontend (frontend/)
npm run dev                          # http://127.0.0.1:3000
npm run build; npm run preview
npm run typecheck
npm run test:smoke                   # node scripts/smoke.mjs
npm run test:browser                 # Playwright
npx playwright test tests/browser/rooms.spec.ts
npx playwright test -g "judul test"
./run.ps1 dev|build|typecheck|preview   # memakai Node lokal di ../.tools bila ada
```

- `test:smoke` dan `test:browser` tidak menyalakan server sendiri: backend Laravel dan Nuxt harus sudah berjalan (target `ASRAMA_URL`, bawaan `http://127.0.0.1:3000`) dengan database yang sudah di-seed. Playwright memakai `msedge` di Windows (ubah lewat `PLAYWRIGHT_CHANNEL`) dan `workers: 1` karena test berbagi satu database.
- Akun seed: `admin@example.id` / `admin123`, `user@example.id` / `user123`, serta `user1..10@example.id` / `user123` (`agama` sengaja kosong sehingga belum bisa dialokasikan).
- Alamat backend untuk Nuxt: `NUXT_LARAVEL_BASE_URL` (`frontend/.env`).

## Arsitektur

### Alur permintaan

Browser hanya berbicara dengan Nuxt. `frontend/server/routes/backend/[...path].ts` mem-proxy `/backend/api/asrama/*` ke Laravel (upstream tetap, path di-whitelist dengan regex, selain itu 404). Karena itu sesi dan CSRF tetap berbasis cookie pada satu origin — tidak ada Sanctum/token.

- Semua rute ada di `routes/web.php` (tidak ada `routes/api.php`), sehingga endpoint JSON `api/asrama/*` berada di grup middleware `web` dan **wajib CSRF**.
- `GET /api/asrama/session` mengembalikan `{ csrfToken, user }`. Di frontend, `useAsrama().mutate()` selalu memanggil `session()` sebelum dan sesudah mutasi dan mengirim header `X-CSRF-TOKEN`; gunakan composable ini untuk semua POST/PATCH.
- `frontend/app/middleware/auth.global.ts` memuat sesi pada tiap navigasi dan mengarahkan berdasarkan `definePageMeta({ role })`. Otorisasi sebenarnya ada di backend: `auth` + `role:admin|user` (`App\Http\Middleware\RequireRole`).

### Dua antarmuka di atas controller yang sama

Setiap rute didaftarkan dua kali di `routes/web.php`: di bawah `api/asrama` (dipakai Nuxt) dan sebagai rute web biasa yang merender Blade (`resources/views/`). Controller bercabang dengan `$request->expectsJson()` — JSON untuk Nuxt, view/redirect untuk Blade. Perubahan perilaku harus menjaga kedua cabang, dan rute baru perlu ditambahkan di kedua blok. Rute admin tidak berprefiks (`/kamar`, `/laundry`), rute penghuni berprefiks `user/`.

### Data

Tidak ada model Eloquent selain `User`; semua akses data memakai query builder `DB::table(...)` dengan nama tabel berbahasa Indonesia: `kamar`, `kamar_slots`, `kamar_stays`, `laundry`, `catering`, `izin_keluar`, `keluhan_asrama`. `dbdiagram.txt` berisi skema DBML.

- `AsramaController` generik atas nama tabel: parameter rute `{page}`/`{category}` langsung menjadi nama tabel, dibatasi oleh `whereIn` di rute dan konstanta `AsramaController::STATUSES`. Daftar status yang valid per kategori didefinisikan di sana.
- Tabel berpaginasi menerima `q`, `sort`, `direction`, `per_page` (hanya 10/50/100); kolom sort di-whitelist per halaman.
- Migrasi `prepare_room_allocation` meng-*hardcode* inventaris: 78 kamar (lantai 3–5, 26 kamar per lantai, nama `301`…`526`), masing-masing 2 baris `kamar_slots`. Penghuni = `kamar_slots.user_id` (unik); check-in = `kamar_slots.checked_in_at`.

### Kamar

- `App\Services\RoomStatus`: status kamar (`Kosong` / `Assigned` / `Terisi` / `Penuh`) diturunkan dari `kamar_slots` lewat potongan SQL (`statusSql()`), dipakai untuk select, sort, dan ringkasan monitoring. Kolom `kamar.status` hanyalah salinan — panggil `RoomStatus::sync($id)` setiap kali slot atau check-in berubah.
- `App\Services\RoomAllocator::allocate()`: alokasi otomatis dalam satu transaksi dengan `lockForUpdate` berurutan `kamar` → `users` → `kamar_slots` (pertahankan urutan ini di kode lain yang mengunci tabel yang sama). Aturan: penghuni dikelompokkan per `angkatan`, tiap angkatan mendapat "lantai asal" (lantai yang sudah dipakai angkatan itu, atau lantai kosong tertinggi) dan mengisi dari lantai itu ke bawah; teman sekamar harus sama `gender` dan `agama`; kamar terisi sebagian yang cocok diisi dulu sebelum membuka kamar kosong. Pengguna tanpa `angkatan`/`gender`/`agama` dikembalikan sebagai `incomplete`, yang sudah check-out (`kamar_stays.checked_out_at`) tidak dialokasikan ulang, sisanya `waiting`.

### Frontend

Halaman admin (`pages/admin.vue`, `kamar.vue`, `[category].vue`) dan penghuni (`pages/user/*`) berbagi komponen panel (`LaundryPanel`, `CateringPanel`, `LeavePermissions`, `RoomComplaints`) yang berubah perilaku menurut peran. `useTableSearch` + `SortableHeader` + `TablePagination` adalah padanan Nuxt dari parameter tabel backend di atas. Tema terang/gelap diinisialisasi oleh `js/asrama-theme.js` yang dimuat di `<head>`; berkas ini ada dua salinan (`frontend/public/js/` untuk Nuxt, `public/js/` untuk Blade) yang harus dijaga tetap sama.

### Test

Feature test PHPUnit (`tests/Feature/`) menguji controller per fitur terhadap SQLite in-memory; spesifikasi Playwright (`frontend/tests/browser/`) mencakup fitur yang sama dari sisi UI dan login dengan akun seed.
