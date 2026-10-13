# Daftar tugas rewrite Gojags Asrama

Dokumen ini adalah instruksi kerja untuk agen AI yang me-rewrite repo ini. Kerjakan tugas **berurutan,
satu per satu**. Setiap tugas berdiri sendiri: baca bagian "Aturan umum" sekali, lalu untuk tiap tugas
baca berkas yang disebut di "Baca dulu" sebelum menulis kode.

Sumber kebenaran struktur ada di `.claude/skills/gojags-asrama-architecture/`:

| Singkatan | Berkas |
|---|---|
| SKILL | `.claude/skills/gojags-asrama-architecture/SKILL.md` |
| BACKEND | `.claude/skills/gojags-asrama-architecture/references/backend.md` |
| FRONTEND | `.claude/skills/gojags-asrama-architecture/references/frontend.md` |
| DOMAIN | `.claude/skills/gojags-asrama-architecture/references/domain.md` |
| SCHEMA | `.claude/skills/gojags-asrama-architecture/references/schema.md` |
| MAP | `.claude/skills/gojags-asrama-architecture/references/migration-map.md` |
| TESTING | `.claude/skills/gojags-asrama-architecture/references/testing.md` |

Bila dokumen ini dan berkas di atas berbeda, **berkas di atas yang benar**; laporkan perbedaannya.

## Aturan umum (berlaku untuk semua tugas)

1. Baca SKILL satu kali sebelum tugas pertama.
2. **Jangan meniru kode lama.** Kode lama hanya untuk memahami perilaku. Kode lama tetap bisa dibaca
   lewat `git show main:<path>` setelah berkasnya dihapus.
3. Identifier (kelas, variabel, kolom, URL, nama berkas) berbahasa Inggris. Teks yang dibaca pengguna
   (label, pesan, judul) berbahasa Indonesia. Pesan dalam tanda kutip di DOMAIN disalin persis.
4. Dilarang di kode baru: `DB::table(`, `expectsJson(`, `view(`, `$request->validate(` di controller,
   string status mentah di luar enum, baris lebih dari 120 karakter, `if (...) pernyataan;` sebaris.
5. Kerjakan hanya yang tertulis di tugas. Jangan menambah fitur, pustaka, atau abstraksi lain.
6. Jangan mengubah apa pun di luar folder `gojags-asrama/` (terutama `../skripsi/`).
7. Setelah tiap tugas: jalankan perintah di "Selesai bila". Bila gagal, perbaiki dulu; jangan lanjut ke
   tugas berikutnya dengan tes merah. Bila tiga kali gagal atau instruksi tidak jelas, **berhenti dan
   laporkan** — jangan menebak dan jangan melemahkan tes agar lulus.
8. Setelah tugas lulus: `git add -A` lalu `git commit -m "rewrite(<ID>): <ringkasan>"`.
9. Perintah backend dijalankan dari root repo; perintah frontend dari `frontend/`.

Perintah verifikasi yang dipakai berulang:

```powershell
composer test                 # semua tes PHPUnit (basis data sesuai phpunit.xml)
php artisan test tests/Feature/NamaTest.php
vendor/bin/pint               # format PHP
vendor/bin/pint --test        # cek format tanpa mengubah
cd frontend; npm run typecheck
cd frontend; npm run test:unit      # Vitest (tersedia setelah C2)
cd frontend; npm run test:browser   # Playwright tiruan (tersedia setelah C6)
```

Baca TESTING § 1–3 sekali sebelum menulis tes pertama, supaya tahu aturan mana diuji di lapis mana.

---

# Fase 0 — Persiapan

## T00 · Cabang kerja

- Jalankan `git status`. Bila ada perubahan yang belum di-commit, **berhenti** dan minta pemilik repo
  meng-commit atau menyimpannya dulu. Jangan meng-commit perubahan milik orang lain.
- Buat cabang: `git switch -c rewrite`.
- Jalankan `composer install` dan `cd frontend; npm install` bila `vendor/` atau `node_modules/` belum ada.

**Selesai bila:** `git branch --show-current` mencetak `rewrite` dan `git status` bersih.

---

# Fase A — Fondasi backend

## A1 · Buang kode lama backend

**Baca dulu:** MAP § "Dihapus tanpa pengganti".

**Hapus:**
- `resources/views/`, `resources/css/`, `resources/js/`
- `public/css/`, `public/js/`
- `vite.config.js`, `package.json`, `package-lock.json` di root repo (bukan yang di `frontend/`)
- `app/Http/Controllers/` semua kecuali `Controller.php`
- `app/Services/`
- `database/migrations/2026_10_09_*.php` (semua migrasi fitur lama; migrasi `0001_01_01_*` tetap)
- `database/seeders/AsramaAccountSeeder.php`, `database/seeders/AsramaUserSeeder.php`
- `tests/Feature/*.php` dan `tests/Unit/ExampleTest.php`

**Ubah:**
- `routes/web.php` → hanya berisi rute `/up` yang mengembalikan `{"status":"ok"}`.
- `database/seeders/DatabaseSeeder.php` → `run()` dikosongkan sementara.
- `composer.json`: di bagian `scripts`, hapus langkah yang menjalankan `npm` di root (mis. di `setup`).
- Cari sisa rujukan: `grep -rn "npm run build\|vite\|resources/views" Dockerfile docker docker-compose*.yml composer.json`
  dan hapus/sesuaikan baris yang merujuk ke aset root yang sudah dihapus. Jangan menyentuh
  `frontend/Dockerfile`.

**Selesai bila:** `php artisan route:list` berjalan tanpa error dan hanya menampilkan `/up` (plus rute
bawaan framework); `composer test` berjalan (boleh "No tests found").

## A2 · Konfigurasi dan enum

**Baca dulu:** BACKEND § 2 dan § 11; SCHEMA § "Enum dan label".

**Buat:**
- `config/asrama.php` persis seperti BACKEND § 11.
- `app/Enums/Role.php`, `Gender.php`, `RoomStatus.php`, `LaundryStatus.php`, `CateringStatus.php`,
  `PermitStatus.php`, `ComplaintStatus.php` — backed enum string, masing-masing dengan `label(): string`.
  Nilai dan label persis tabel di SCHEMA.
- Di `RoomStatus` tambahkan `public static function fromCounts(int $assigned, int $checkedIn): self`
  yang menerapkan tabel keputusan DOMAIN § 5 (urutan pengecekan penting).
- `tests/Unit/RoomStatusTest.php`: uji `fromCounts` untuk (0,0)→empty, (1,0)→assigned, (2,1)→assigned,
  (1,1)→occupied, (2,2)→full.

**Ubah:** `config/app.php` → `'timezone' => 'UTC'`.

**Selesai bila:** `php artisan test tests/Unit/RoomStatusTest.php` hijau.

## A3 · Migrasi

**Baca dulu:** SCHEMA (seluruhnya); BACKEND § 12.

**Ubah:** `database/migrations/0001_01_01_000000_create_users_table.php` → tambahkan kolom `role`
(string 20, default `resident`), `cohort` (string 100, nullable), `gender` (string 10, nullable),
`religion` (string 50, nullable) ke tabel `users`.

**Buat** satu migrasi per tabel, dengan awalan tanggal berurutan `2026_10_13_00000N_`:
`create_rooms_table`, `create_room_slots_table`, `create_stays_table`, `create_laundry_orders_table`,
`create_catering_menus_table`, `create_catering_ratings_table`, `create_leave_permits_table`,
`create_complaints_table`. Kolom, tipe, FK, `onDelete`, unik, dan indeks persis SCHEMA. Migrasi **tidak**
menyisipkan baris. Tiap migrasi punya `down()` yang men-drop tabelnya.

**Selesai bila:** `php artisan migrate:fresh` berhasil tanpa error.

## A4 · Model

**Baca dulu:** BACKEND § 3 dan § 9; SCHEMA § "Relasi model".

**Ubah:** `app/Models/User.php`
- `$fillable`: `name`, `email`, `password`, `role`, `cohort`, `gender`, `religion`.
- `$casts`: tambah `role => Role::class`, `gender => Gender::class`.
- Relasi: `slot()` hasOne `RoomSlot`; `stays()`, `laundryOrders()`, `leavePermits()`, `complaints()`,
  `cateringRatings()` hasMany.
- `scopeResidents(Builder $query)`: `where('role', Role::Resident)`.
- `public static function nameOf(string $table): Builder` seperti dijelaskan di akhir BACKEND § 6.
- Hapus docblock bawaan yang hanya mengulang nama properti.

**Buat** di `app/Models/`: `Room`, `RoomSlot`, `Stay`, `LaundryOrder`, `CateringMenu`, `CateringRating`,
`LeavePermit`, `Complaint`. Untuk tiap model:
- `use HasFactory`, `$fillable` hanya kolom dari form pengguna, `$casts` untuk kolom waktu (`datetime`),
  status (enum), dan `cost` (`decimal:2`).
- Relasi sesuai SCHEMA.
- `LeavePermit`, `Complaint`, `LaundryOrder`: `scopeVisibleTo(Builder $query, User $user)` persis pola
  BACKEND § 3, dan `isPending(): bool`.
- `Room`: relasi `slots()`, `scopeWithOccupancy`, accessor `status` (BACKEND § 9),
  `scopeOrderByStatus(Builder $query, string $direction)` dan
  `scopeOrderByFirstResident(Builder $query, string $direction)` (nama penghuni terkecil menurut abjad).
- `RoomSlot`: relasi `room()`, `user()`.

**Selesai bila:** `php artisan tinker --execute="App\Models\Room::withOccupancy()->count();"` mencetak `0`
tanpa error setelah `php artisan migrate:fresh`.

## A5 · Factory

**Baca dulu:** BACKEND § 12.

**Ubah:** `database/factories/UserFactory.php` → bawaan berperan `resident`; state `admin()`,
`resident()`, dan `profiled(string $cohort = '64', Gender $gender = Gender::Male, string $religion = 'islam')`.

**Buat** factory untuk `Room`, `RoomSlot`, `Stay`, `LaundryOrder`, `CateringMenu`, `CateringRating`,
`LeavePermit`, `Complaint`. State yang wajib ada: `LeavePermit`: `approved()`, `rejected()`;
`Complaint`: `resolved()`; `LaundryOrder`: `done()`; `Stay`: `checkedOut()`.

**Selesai bila:** `php artisan tinker --execute="App\Models\LeavePermit::factory()->approved()->create();"`
berhasil setelah `migrate:fresh`.

## A6 · Seeder

**Baca dulu:** DOMAIN § 14.

**Buat:**
- `database/seeders/RoomSeeder.php`: untuk tiap lantai di `config('asrama.floors')`, buat
  `config('asrama.rooms_per_floor')` kamar bernomor `lantai*100 + n`, masing-masing
  `config('asrama.room_capacity')` slot (`position` 1..n). Pakai `firstOrCreate` agar aman dijalankan ulang.
- `database/seeders/AccountSeeder.php`: akun persis DOMAIN § 14. Akun yang sudah ada tidak ditimpa
  profilnya.

**Ubah:** `DatabaseSeeder` memanggil `RoomSeeder` lalu `AccountSeeder`.

**Buat tes** `tests/Feature/SeederTest.php`: setelah `$this->seed()`, ada 78 kamar, 156 slot, 1 admin,
11 penghuni, dan `user1@example.id` ber-`religion` null; menjalankan `$this->seed()` dua kali tidak
menggandakan data.

**Selesai bila:** `php artisan test tests/Feature/SeederTest.php` hijau.

## A7 · Kerangka HTTP

**Baca dulu:** BACKEND § 1, § 6 (controller dasar), § 7, § 8, § 10; DOMAIN § 1, § 4, § 7 ("Urutan kunci").

**Buat:**
- `app/Support/DateFormat.php` persis BACKEND § 10.
- `app/Support/TableQuery.php` persis BACKEND § 7.
- `app/Support/RoomLocks.php` dengan dua method statis. Keduanya harus dipanggil di dalam transaksi dan
  mengunci dengan urutan `rooms` → `users` → `room_slots`:
  - `acquireAll(): array` — mengunci dan mengembalikan `[$rooms, $users, $slots]` (koleksi model; `rooms`
    diurut `floor` menurun lalu `number`, `users` dan `slots` menurut `id`).
  - `acquireFor(User $user): ?RoomSlot` — mengunci semua baris `rooms`, baris user itu, lalu slot milik
    user itu (dengan relasi `room`), dan mengembalikan slot atau `null`.
- `routes/api.php` berisi kerangka kosong (hanya `use` dan komentar pengelompokan); rute diisi di tugas
  berikutnya.

**Ubah:**
- `app/Http/Controllers/Controller.php` → tambahkan method `message()` (BACKEND § 6).
- `app/Http/Middleware/RequireRole.php` → bandingkan dengan enum:
  `abort_unless($request->user()?->role === Role::from($role), 403, 'Anda tidak memiliki izin untuk halaman ini.')`.
- `app/Providers/RouteServiceProvider.php` → muat `routes/api.php` dengan middleware
  `['web', 'cache.headers:no_store']` dan prefix `api` (BACKEND § 1). Hapus pemuatan grup `api` bawaan.
- `app/Http/Middleware/Authenticate.php` → `redirectTo()` selalu mengembalikan `null` (API tidak pernah
  me-redirect ke halaman login).
- `app/Exceptions/Handler.php` → pastikan semua permintaan ke `api/*` dirender sebagai JSON (mis. lewat
  `shouldReturnJson`), sehingga 401/403/404/422 selalu JSON.

**Buat tes** `tests/Feature/TableQueryTest.php` memakai `LeavePermit` + rute sementara yang didaftarkan di
dalam tes: `per_page=7` → 422; `sort=password` → 422; `q` lebih dari 100 karakter → 422; urutan bawaan
`id` menurun; `sort` non-id memakai `id` sebagai pengurut kedua.

**Selesai bila:** `php artisan test tests/Feature/TableQueryTest.php` hijau dan `vendor/bin/pint --test` bersih.

## A8 · Sesi (login/logout)

**Baca dulu:** DOMAIN § 2; BACKEND § 4–6.

**Buat:**
- `app/Http/Requests/LoginRequest.php`: `email` (required, email), `password` (required, string).
- `app/Http/Resources/UserResource.php`: `id`, `name`, `email`, `role` (nilai enum).
- `app/Http/Controllers/SessionController.php`:
  - `show` → `{ csrfToken, user }` (`user` null bila belum login).
  - `store` → `Auth::attempt`; gagal → `ValidationException` pada `email` dengan pesan DOMAIN § 2;
    berhasil → `session()->regenerate()` lalu kembalikan bentuk yang sama dengan `show`.
  - `destroy` → logout, `invalidate()`, `regenerateToken()`, kembalikan bentuk yang sama dengan `show`.
  - Ketiganya memakai satu method privat `payload(Request $request): JsonResponse`.

**Rute** (di `routes/api.php`): `GET session`, `POST login` (`throttle:5,1`), `POST logout` (`auth`).

**Buat tes** `tests/Feature/SessionTest.php`: sesi tamu → `user` null dan ada `csrfToken`; login benar →
200 + `user.role`; login salah → 422 dengan pesan persis; percobaan ke-6 dalam semenit → 429; logout →
`user` null; `POST logout` tanpa login → 401; respons berheader `Cache-Control` mengandung `no-store`.

**Selesai bila:** `php artisan test tests/Feature/SessionTest.php` hijau.

---

# Fase B — Fitur backend

Pola tiap tugas: enum (sudah ada) → FormRequest → Resource → Controller → rute → feature test. Contoh
lengkap ada di BACKEND § 4–6 dan § 13. Bentuk respons mengikuti tabel di BACKEND § 1. Setiap tes fitur
minimal mencakup: jalur berhasil, tiap aturan + pesan dari DOMAIN, peran salah (403), tamu (401).

## B1 · Daftar kamar dan monitoring (admin)

**Baca dulu:** DOMAIN § 5; BACKEND § 9.

**Buat:**
- `app/Http/Resources/RoomResource.php`: `id`, `number`, `floor`, `status`, `status_label`,
  `assigned_count`, `capacity` (dari config), `residents` = daftar `{ id, name, checked_in_at }` dari slot
  berpenghuni, diurut nama.
- `app/Http/Controllers/RoomController.php` → `index`: `Room::withOccupancy()->with('slots.user')` lewat
  `TableQuery`; cari pada `number`, `floor`; kunci sort: `number`, `floor`, `assigned_count`,
  `status` (→ `orderByStatus`), `resident` (→ `orderByFirstResident`). Respons memakai
  `->additional(['monitoring' => [...]])` berisi jumlah kamar per nilai `RoomStatus` (keempat kunci selalu
  ada).

**Rute:** `GET rooms` (admin).

**Tes** `tests/Feature/RoomTest.php` (memanggil `$this->seed(RoomSeeder::class)`): total 78; status tiap
kombinasi slot sesuai DOMAIN § 5; `monitoring` memuat empat kunci; urut `status` naik = empty, assigned,
occupied, full; cari `q=501` menemukan kamar 501; penghuni → 403.

**Selesai bila:** `php artisan test tests/Feature/RoomTest.php` hijau.

## B2 · Profil penghuni (admin)

**Baca dulu:** DOMAIN § 6; BACKEND § 8.

**Buat:**
- `app/Http/Resources/ResidentResource.php`: `id`, `name`, `cohort`, `gender`, `religion`, `room_id`
  (id kamar dari slot, atau null).
- `app/Http/Requests/UpdateResidentRequest.php`: `cohort` (required, string, max 100), `gender`
  (required, enum `Gender`), `religion` (required, string, max 50).
- `app/Actions/UpdateResidentProfile.php` → `handle(User $resident, array $profile): void`: normalisasi
  `cohort` dan `religion` (DOMAIN § 6), tolak bila kosong, dalam transaksi panggil
  `RoomLocks::acquireFor($resident)`, tolak bila sudah punya slot, lalu simpan. `abort(404)` bila user
  bukan `resident`.
- `app/Http/Controllers/ResidentController.php` → `index` (semua penghuni urut `id`, tanpa paginasi,
  `with('slot')`) dan `update`.

**Rute:** `GET residents`, `PATCH residents/{resident}` (admin).

**Tes** `tests/Feature/ResidentProfileTest.php`: simpan berhasil + nilai tersimpan ternormalisasi
(`"  ISLAM  "` → `islam`); `cohort` berisi spasi saja → 422 pesan DOMAIN; penghuni yang sudah punya slot →
422 pesan DOMAIN; target admin → 404; `gender` tak dikenal → 422.

**Selesai bila:** tes hijau.

## B3 · Alokasi otomatis (admin)

**Baca dulu:** DOMAIN § 7 (baca dua kali; ini aturan paling rumit); BACKEND § 8.

**Buat:**
- `app/Actions/AllocateRooms.php` → `handle(): array` mengembalikan
  `['allocated' => [...], 'waiting' => [...], 'incomplete' => [...]]` dengan bentuk elemen DOMAIN § 7.
  Seluruhnya dalam satu `DB::transaction(..., 3)` yang diawali `RoomLocks::acquireAll()`. Pecah menjadi
  method privat kecil: `pendingResidents()`, `homeFloorFor()`, `findRoomFor()`, `assign()`. Simpan hunian
  di memori (peta `room_id => [User, ...]`) dan perbarui tiap kali menempatkan orang. Lantai dan kapasitas
  dari `config/asrama.php`.
- `app/Http/Controllers/RoomAllocationController.php` → `__invoke`: panggil Action, tambahkan `message`
  dengan format DOMAIN § 7.

**Rute:** `POST rooms/allocate` (admin).

**Tes** `tests/Feature/RoomAllocationTest.php` — satu method per aturan:
1. Cohort baru ditempatkan di lantai kosong tertinggi (5).
2. Cohort kedua mendapat lantai utuh berikutnya (4), bukan bercampur di lantai 5.
3. Cohort yang sudah punya penghuni di lantai 4 melanjutkan di lantai 4.
4. Teman sekamar selalu sama gender dan agama; dua orang beda agama tidak sekamar.
5. Kamar terisi sebagian yang cocok diisi sebelum kamar kosong.
6. Profil tidak lengkap → masuk `incomplete`, tidak mendapat slot.
7. User yang pernah check-out tidak dialokasikan ulang.
8. Penghuni yang sudah punya kamar tidak dipindah saat alokasi dijalankan lagi.
9. Bila tidak ada kamar yang cocok → masuk `waiting` (kecilkan inventaris lewat `config()` di dalam tes).
10. Pesan ringkasan sesuai format; penghuni → 403.

**Selesai bila:** tes hijau. Bila sebuah aturan di DOMAIN § 7 terasa ambigu, lihat implementasi lama:
`git show main:app/Services/RoomAllocator.php`.

## B4 · Kamar saya: check-in / check-out (penghuni)

**Baca dulu:** DOMAIN § 8; BACKEND § 8 (contoh `CheckIn`).

**Buat:**
- `app/Actions/CheckIn.php` (persis BACKEND § 8) dan `app/Actions/CheckOut.php` (aturan DOMAIN § 8).
- `app/Http/Resources/StayResource.php`: `id`, `room_number`, `floor`, `checked_in_at`, `checked_out_at`.
- `app/Http/Controllers/MyRoomController.php`:
  - `show` → `{ data: { room: { number, floor, checked_in_at } | null, stays: [...] } }`, riwayat terbaru dulu.
  - `checkIn` → "Check-in berhasil."; `checkOut` → "Check-out berhasil. Slot kamar telah dilepaskan."

**Rute:** `GET my-room`, `POST my-room/check-in`, `POST my-room/check-out` (penghuni).

**Tes** `tests/Feature/MyRoomTest.php`: tanpa alokasi → `room` null dan check-in 422 pesan DOMAIN;
check-in mengisi `checked_in_at` + membuat `stays` dengan salinan nama/nomor/lantai; check-in dua kali →
422; check-out sebelum check-in → 422; check-out mengisi `checked_out_at` dan mengosongkan slot;
status kamar berubah assigned → occupied → empty; admin → 403.

**Selesai bila:** tes hijau.

## B5 · Izin keluar

**Baca dulu:** DOMAIN § 9; BACKEND § 3–6 (kode lengkapnya ada di sana — salin dan lengkapi).

**Buat:** `SaveLeavePermitRequest`, `ReviewLeavePermitRequest`, `LeavePermitResource`,
`LeavePermitController` (`index`, `store`, `update`, `review`).

**Rute:** `GET leave-permits` (auth), `POST leave-permits` dan `PATCH leave-permits/{permit}` (penghuni),
`PATCH leave-permits/{permit}/review` (admin).

**Tes** `tests/Feature/LeavePermitTest.php`: ajukan → 201 + status `pending`; `returns_at` ≤ `leaves_at` →
422 pesan DOMAIN; waktu tanpa akhiran `Z` (mis. `2026-10-13T10:00`) → 422; penghuni hanya melihat
miliknya, admin melihat semua + `user_name`; edit saat `pending` berhasil; edit setelah diproses → 422
pesan DOMAIN; edit milik orang lain → 404; admin menyetujui/menolak → pesan sesuai, `reviewed_by` dan
`reviewed_at` terisi; meninjau dua kali → 422 pesan DOMAIN; `status=pending` saat review → 422;
penghuni memanggil review → 403; cari menurut nama pemohon dan urut `user_name` berfungsi.

**Selesai bila:** tes hijau.

## B6 · Keluhan

**Baca dulu:** DOMAIN § 10.

**Buat:** `StoreComplaintRequest` (`title` max 150, `body` max 2000), `ComplaintResource` (`id`,
`user_name`, `title`, `body`, `status`, `status_label`, `created_at`, `resolved_at`),
`ComplaintController` (`index`, `store`, `resolve`). `resolve` mengunci baris; hanya mengubah bila masih
`pending` (idempoten).

Cari: `title`, `body`, `status`, `user.name`. Sort: `title`, `body`, `status`, `created_at`,
`resolved_at`, `user_name`.

**Rute:** `GET complaints` (auth), `POST complaints` (penghuni), `PATCH complaints/{complaint}/resolve` (admin).

**Tes** `tests/Feature/ComplaintTest.php`: kirim → 201 pesan DOMAIN; batas panjang → 422; visibilitas per
peran; resolve mengisi `resolved_by`/`resolved_at`; resolve kedua oleh admin lain **tidak** mengubah
keduanya dan tetap 200; penghuni resolve → 403.

**Selesai bila:** tes hijau.

## B7 · Laundry

**Baca dulu:** DOMAIN § 11.

**Buat:** `StoreLaundryOrderRequest` (`items` max 255; `set_count` integer 1–1000),
`UpdateLaundryOrderRequest` (`status` enum `LaundryStatus`; `cost` wajib bila `status` = `done`, nullable,
numeric, min 0, max 9999999999.99, `decimal:0,2`), `LaundryOrderResource` (`id`, `user_name`, `items`,
`set_count`, `status`, `status_label`, `cost`, `created_at`), `LaundryOrderController` (`index`, `store`,
`update`).

Cari: `items`, `status`, `user.name`. Sort: `items`, `set_count`, `created_at`, `status`, `cost`, `user_name`.

**Rute:** `GET laundry-orders` (auth), `POST laundry-orders` (penghuni), `PATCH laundry-orders/{order}` (admin).

**Tes** `tests/Feature/LaundryOrderTest.php`: ajukan → 201, `pending`, `cost` null; `set_count` 0 dan
1001 → 422; visibilitas per peran; `done` tanpa `cost` → 422; `done` + `cost` → tersimpan; `cost` tiga
desimal → 422; penghuni update → 403.

**Selesai bila:** tes hijau.

## B8 · Catering dan rating

**Baca dulu:** DOMAIN § 12.

**Buat:**
- `StoreCateringMenuRequest` (`menu_number` integer 1–10000; `provider` max 255; `served_at`
  `DateFormat::UTC`), `UpdateCateringMenuRequest` (`status` enum `CateringStatus`),
  `RateCateringMenuRequest` (`rating` integer 1–5).
- `CateringMenuResource`: `id`, `menu_number`, `provider`, `served_at`, `status`, `status_label`,
  `rating_average` (float dibulatkan 2 desimal atau null), `rating_count` (int), `my_rating` (int atau null).
- `CateringMenu::scopeWithRatingsFor(Builder $query, User $user)`: `withAvg('ratings as rating_average', 'rating')`,
  `withCount('ratings as rating_count')`, dan subquery `my_rating` untuk user tersebut.
- `CateringMenuController` (`index`, `store`, `update`) dan `CateringRatingController` (`update` →
  `updateOrCreate` pada pasangan menu + user).

Cari: `menu_number`, `provider`. Sort: `menu_number`, `provider`, `served_at`, `rating_average`,
`rating_count`, `my_rating`.

**Rute:** `GET catering-menus` (auth), `POST catering-menus` dan `PATCH catering-menus/{menu}` (admin),
`PUT catering-menus/{menu}/rating` (penghuni).

**Tes** `tests/Feature/CateringTest.php`: tambah menu → 201, `scheduled`; ubah status; rating 0 dan 6 →
422; rating ulang menimpa (tetap satu baris); rata-rata dan jumlah penilai benar untuk dua penilai;
`my_rating` null bagi yang belum menilai; penghuni menambah menu → 403; admin memberi rating → 403.

**Selesai bila:** tes hijau.

## B9 · Dashboard dan pemeriksaan akhir backend

**Baca dulu:** DOMAIN § 1 dan § 13.

**Buat:** `app/Http/Controllers/DashboardController.php` (`__invoke`) →
`{ data: { rooms, laundry_orders, catering_menus } }` berisi jumlah baris. **Rute:** `GET dashboard` (admin).

**Tes** `tests/Feature/RoleAccessTest.php`: dengan *data provider*, tiap endpoint admin → 403 untuk
penghuni dan 401 untuk tamu; tiap endpoint penghuni → 403 untuk admin dan 401 untuk tamu; `POST` tanpa
token CSRF ke salah satu endpoint → 419 (aktifkan middleware CSRF di tes ini saja). Tambahkan tes
`dashboard` mengembalikan tiga hitungan.

**Pemeriksaan akhir** — semua perintah ini harus bersih/kosong:

```powershell
composer test
vendor/bin/pint --test
php artisan route:list --path=api
grep -rnE "DB::table\(|expectsJson\(|\bview\(" app routes
grep -rnE "\$request->validate\(" app/Http/Controllers
```

**Selesai bila:** `composer test` hijau seluruhnya dan kedua `grep` tidak mengeluarkan apa pun.

---

# Fase C — Fondasi frontend

Semua path di fase C–D relatif terhadap `frontend/`.

## C1 · Buang kode lama frontend dan pasang proxy

**Baca dulu:** FRONTEND § 1–2; MAP § "Frontend".

**Hapus:** `app/pages/` (semua), `app/components/` (semua), `app/composables/` (semua),
`server/routes/backend/`, `public/js/asrama-theme.js`. Sebelum menghapus, salin isi
`public/js/asrama-theme.js` ke `public/js/theme.js`.

**Buat:** `server/routes/api/[...path].ts` — proxy `/api/<path>` ke
`${laravelBaseUrl}/api/<path>` beserta query string; tolak (404) path yang tidak cocok
`^[a-z0-9/-]+$`; paksa header `accept: application/json`; set `Cache-Control: no-store`.

**Ubah:** `nuxt.config.ts` → skrip head menunjuk `/js/theme.js`.

**Selesai bila:** berkas-berkas di atas ada/terhapus. (`typecheck` belum perlu lulus di tugas ini.)

## C2 · Tipe dan utilitas

**Baca dulu:** FRONTEND § 4–5.

**Buat** di `app/types/`:
- `api.ts`: `Paginated<T>`, `ApiMessage`, `Tone = 'info' | 'success' | 'warning' | 'danger' | 'neutral'`.
- `session.ts`: `Role = 'admin' | 'resident'`, `User`, `Session`.
- `room.ts`, `leave-permit.ts`, `complaint.ts`, `laundry.ts`, `catering.ts`: tipe yang **persis
  mencerminkan field Resource** di tugas B1–B8, tipe input form, dan satu peta warna per fitur
  (`ROOM_TONE`, `PERMIT_TONE`, `COMPLAINT_TONE`, `LAUNDRY_TONE`: `Record<Status, Tone>`).

**Buat** di `app/utils/`:
- `datetime.ts` persis FRONTEND § 5.
- `currency.ts`: `formatRupiah(value: string | null): string` → Rupiah `id-ID`, atau `'Belum ditentukan'`.
- `errors.ts`: `statusOf(error: unknown): number | undefined` dan `errorMessage(error: unknown): string`
  dengan pesan: validasi → gabungan semua pesan `errors`; 401 → "Sesi telah berakhir. Silakan login
  kembali."; 403 → "Anda tidak memiliki izin untuk halaman ini."; 419 → "Sesi tidak valid. Muat ulang
  halaman dan coba kembali."; 429 → "Terlalu banyak percobaan. Coba kembali sebentar lagi."; lainnya →
  "Tidak dapat menghubungi server. Pastikan backend Laravel berjalan."

**Tes unit** (baca TESTING § 4): tambahkan devDependency `vitest`, buat `vitest.config.ts` (alias `~` →
`./app`, `include: ['tests/unit/**/*.test.ts']`), skrip `"test:unit": "vitest run"`, dan tiga berkas
`tests/unit/datetime.test.ts`, `currency.test.ts`, `errors.test.ts` dengan kasus yang disebut di TESTING § 4.
Hapus `tests/browser/*.spec.ts` lama (ditulis ulang mulai C6; isinya tetap bisa dibaca lewat
`git show main:frontend/tests/browser/<nama>.spec.ts`).

**Selesai bila:** `npm run test:unit` hijau dan tidak ada `any` di `app/types` maupun `app/utils`.

## C3 · Composable

**Baca dulu:** FRONTEND § 3; DOMAIN § 4.

**Buat** di `app/composables/`: `useSession.ts`, `useApi.ts`, `useAction.ts` (ketiganya persis FRONTEND
§ 3), `usePolling.ts`, dan `useTable.ts`.

`useTable<T>(path: string)`:
- State: `search`, `sort` (`'id'`), `direction` (`'desc'`), `page` (1), `perPage` (10).
- Memuat `Paginated<T>` dari `/api/${path}` dengan query `q, sort, direction, per_page, page`.
- Mengetik di `search` → debounce 300 ms → terapkan `q` dan kembali ke halaman 1.
- `sortBy(column)`: kolom sama → balik arah; kolom baru → `asc`; kembali ke halaman 1.
- Mengubah `perPage` → kembali ke halaman 1.
- Mengembalikan API yang tercantum di FRONTEND § 3 (`rows`, `meta`, `loading`, `loadError`, `search`,
  `sort`, `direction`, `page`, `perPage`, `sortBy`, `goToFirstPage`, `refresh`).
- Membersihkan timer debounce saat komponen dilepas.

`usePolling(refresh, { pausedWhen })`: `setInterval` 15 000 ms (konstanta `POLL_INTERVAL_MS`), hanya
memanggil `refresh` bila `!document.hidden && !pausedWhen()`; dibersihkan di `onBeforeUnmount`.

**Selesai bila:** berkas ada; tidak ada `setInterval`/`setTimeout` di luar `useTable.ts` dan `usePolling.ts`.

## C4 · Gaya dan komponen `ui/`

**Baca dulu:** FRONTEND § 6, § 9, § 10.

**Ubah:** `app/assets/css/main.css` → tambahkan `textarea` ke gaya dasar `input, select`; tambahkan kelas
komponen `.btn-text`, `.section-title`; pertahankan `.card`, `.btn`, `.muted`, `.overline`.

**Buat** di `app/components/ui/`:
- `StatusBadge.vue` — prop `tone: Tone`; isi lewat slot; kelas terang+gelap per tone dalam satu peta.
- `AlertMessage.vue` — props `error?: string`, `success?: string`, `retryable?: boolean`; emit `retry`;
  `role="alert"` untuk error, `role="status"` untuk sukses; tidak merender apa pun bila keduanya kosong.
- `PageHeader.vue` — props `title: string`, `description?: string`; overline "LAYANAN ASRAMA".
- `DataTable.vue` — generik (`<script setup lang="ts" generic="T extends { id: number }">`); props
  `table` (hasil `useTable<T>`), `columns: { key: string; label: string; sortable?: boolean }[]`,
  `searchLabel: string`, `emptyText: string`. Merender: form cari (input ber-`aria-label` = `searchLabel`,
  tombol "Cari", tombol "Reset" bila ada teks), teks "Memuat…" ber-`role="status"` saat memuat,
  `<table>` dengan header yang bisa diurutkan (`aria-sort`, panah ▲ ▼ ↕), satu slot bernama per kolom
  (`#<key>="{ row }"`, bawaan: teks `row[key]`), baris kosong berisi `emptyText`, dan navigasi halaman
  (Awal, Sebelumnya, `n/m`, Berikutnya, Akhir, pilihan 10/50/100 ber-label "Baris per halaman").
  Bila terlalu panjang, pecah bagian paginasi ke `ui/TablePagination.vue`.

**Selesai bila:** `npm run typecheck` tidak melaporkan error pada berkas di `components/ui/`.

## C5 · Layout, middleware, login

**Baca dulu:** FRONTEND § 2, § 8, § 9; kode lama untuk teks dan tampilan:
`git show main:frontend/app/layouts/default.vue`, `git show main:frontend/app/pages/login.vue`,
`git show main:frontend/app/middleware/auth.global.ts`.

**Buat/ubah:**
- `app/middleware/auth.global.ts` → memakai `useSession().load()`; beranda admin `/admin`, penghuni
  `/resident`; aturan pengalihan sama dengan versi lama.
- `app/components/layout/AppSidebar.vue`, `AppHeader.vue`, `AppFooter.vue`, `ThemeToggle.vue` — pecahan
  dari layout lama dengan teks dan tampilan yang sama. Menu admin: Beranda `/admin`, Kamar
  `/admin/rooms`, Laundry `/admin/laundry`, Catering `/admin/catering`. Menu penghuni: Beranda
  `/resident`, Kamar `/resident/room`, Laundry `/resident/laundry`, Catering `/resident/catering`.
  Daftar menu didefinisikan satu kali (mis. `app/utils/navigation.ts`) dan dipakai sidebar serta footer.
- `app/components/layout/AppIcon.vue` — pengganti `AsramaIcon.vue` lama (isi SVG sama).
- `app/layouts/default.vue` — hanya menyusun ketiga komponen layout + `<slot />`.
- `app/pages/login.vue` — form email + kata sandi; memakai `useAction` dan `useApi().post<Session>('login', …)`,
  lalu `useSession().apply(...)` dan pindah ke beranda sesuai peran. Logout di sidebar memakai pola yang sama.
- `app/pages/index.vue` — hanya mengalihkan (ditangani middleware).

**Selesai bila:** `npm run typecheck` bersih; dengan backend (`php artisan migrate:fresh --seed; php artisan serve`)
dan `npm run dev` berjalan, login `admin@example.id` / `admin123` berhasil dan menampilkan kerangka
halaman dengan sidebar.

## C6 · Infrastruktur Playwright tiruan

**Baca dulu:** TESTING § 5, § 6, § 8.

**Buat:**
- `tests/support/shared.ts` dan `playwright.config.ts` persis TESTING § 5–6 (menggantikan konfigurasi lama).
- `tests/support/samples.ts` — satu pabrik data per tipe di `app/types` (`sampleRoom`, `sampleResident`,
  `sampleLeavePermit`, `sampleComplaint`, `sampleLaundryOrder`, `sampleCateringMenu`, `sampleStay`), tiap
  pabrik menerima `Partial<T>` untuk menimpa nilai bawaan dan memberi `id` unik.
- `tests/support/mock-api.ts` — `CSRF_TOKEN`, `mockSession`, `mockTable` (TESTING § 6) dan
  `mockEmptyTables(page)` yang menjawab kosong untuk semua endpoint GET di BACKEND § 1.
- `tests/support/fixtures.ts` persis TESTING § 6.
- Spec lintas-fitur `tests/browser/roles.spec.ts`, `login.spec.ts`, `csrf.spec.ts`, `theme.spec.ts` dengan
  isi sesuai tabel "Spec yang wajib ada" di TESTING § 6. (`data-table.spec.ts` dan `polling.spec.ts` butuh
  satu tabel sungguhan, jadi ditulis di tugas D1.)

**Ubah:** `package.json` → skrip `test:browser`, `test:e2e`, `test:smoke` seperti TESTING § 9;
`.gitignore` → tambahkan `playwright-report/`, `test-results/`, `tests/e2e/.auth/`.

**Selesai bila:** `npm run typecheck` bersih dan `npm run test:browser` hijau (Nuxt dinyalakan otomatis;
Laravel tidak diperlukan).

---

# Fase D — Fitur frontend

Pola tiap fitur: tiga komponen di `app/components/<fitur>/` — **Section** (memegang `useTable`,
`useAction`, `useApi`, `usePolling`), **Form**, **Table** (keduanya hanya props + emit). Contoh lengkap:
FRONTEND § 7. Aturan format: FRONTEND § 9. Untuk teks antarmuka, salin dari komponen lama
(`git show main:frontend/app/components/<Nama>.vue`) — teksnya tidak berubah, strukturnya yang berubah.

Tiap tugas D juga menulis **spec Playwright tiruan** fiturnya di `tests/browser/<fitur>.spec.ts` (aturan:
TESTING § 6 dan § 8; isi minimum: TESTING § 10): tampilan per peran, tiap aksi tulis (periksa isi
permintaan, pesan sukses, tabel diperbarui), dan satu kasus pesan error dari server. Skenario spec lama
bisa dijadikan daftar periksa. Jangan mengulang uji perilaku tabel — itu milik `data-table.spec.ts`.

Tiap tugas **selesai bila** `npm run typecheck` bersih, `npm run test:browser` hijau, dan tidak ada baris
>120 karakter di berkas yang dibuat.

## D1 · Izin keluar

**Baca dulu:** FRONTEND § 7; DOMAIN § 9. Kode lama: `LeavePermissions.vue`.

**Buat:** `components/leave-permit/LeavePermitSection.vue`, `LeavePermitForm.vue` (keduanya ada di
FRONTEND § 7), dan `LeavePermitTable.vue` — kolom: Nama user (admin saja), Waktu keluar, Waktu kembali,
Alasan, Status, Aksi. Aksi: admin → "Setujui" / "Tolak" untuk yang `pending`; penghuni → "Edit" untuk
yang `pending`; selain itu teks "Sudah diproses". Waktu memakai `formatWib`.

**Spec:** `leave-permit.spec.ts`, ditambah `data-table.spec.ts` dan `polling.spec.ts` (ditunda dari C6)
yang memakai tabel izin keluar sebagai contoh.

## D2 · Keluhan

**Baca dulu:** DOMAIN § 10. Kode lama: `RoomComplaints.vue`.

**Buat:** `components/complaint/ComplaintSection.vue`, `ComplaintForm.vue` (judul, isi; penghuni saja),
`ComplaintTable.vue` — kolom: Nama user (admin), Judul, Isi keluhan, Waktu diajukan, Status, Waktu
resolved ("Belum diselesaikan" bila null), Aksi (admin: "Tandai Resolved" bila `pending`, selain itu
"Sudah ditangani").

## D3 · Halaman kamar penghuni

**Baca dulu:** DOMAIN § 8. Kode lama: `pages/user/kamar.vue`.

**Buat:**
- `components/room/MyRoomCard.vue` — memuat `GET /api/my-room`; menampilkan kamar atau "Belum ada alokasi
  kamar"; tombol Check-in / Check-out dengan aturan aktif/nonaktif yang sama dengan versi lama;
  memancarkan `changed` setelah aksi berhasil.
- `components/room/StayHistory.vue` — prop `stays`; daftar riwayat.
- `pages/resident/room.vue` — `PageHeader` + `MyRoomCard` + `StayHistory` + `LeavePermitSection` +
  `ComplaintSection`. Data `my-room` dimuat sekali di halaman dan diteruskan lewat props.

## D4 · Halaman kamar admin

**Baca dulu:** DOMAIN § 5–7. Kode lama: `pages/kamar.vue`.

**Buat** di `components/room/`:
- `RoomMonitoring.vue` — prop `monitoring`; empat kartu status dengan `StatusBadge`.
- `RoomAllocation.vue` — tombol "Alokasikan kamar otomatis" (`POST rooms/allocate`), menampilkan hasil
  (profil belum lengkap, menunggu, hasil penempatan); memancarkan `allocated`.
- `ResidentProfiles.vue` — memuat `GET residents`; satu form per penghuni (Angkatan, Gender, Agama +
  datalist agama); form terkunci bila `room_id` terisi; simpan lewat `PATCH residents/{id}`. Pilihan
  gender: nilai `male`/`female`, teks "Laki-laki"/"Perempuan". Suntingan yang belum disimpan pada
  penghuni lain tidak boleh hilang saat satu profil disimpan.
- `RoomTable.vue` — `DataTable` dengan kolom Kamar (`number`), Lantai (`floor`), Penghuni (`resident`),
  Kapasitas (`assigned_count`), Status (`status`).
- `pages/admin/rooms.vue` — menyusun keempatnya + `LeavePermitSection` + `ComplaintSection`; setelah
  alokasi berhasil, tabel kamar, monitoring, dan daftar profil dimuat ulang.

## D5 · Laundry

**Baca dulu:** DOMAIN § 11. Kode lama: `LaundryPanel.vue`.

**Buat:** `components/laundry/LaundrySection.vue`, `LaundryForm.vue` (penghuni: barang + jumlah stel),
`LaundryTable.vue` (kolom: Nama user (admin), Pakaian / barang, Jumlah stel, Tanggal, Status, Biaya
laundry; admin mendapat kolom "Perbarui status" berisi form status + biaya per baris — pecah form baris
itu ke `LaundryStatusForm.vue`). Biaya memakai `formatRupiah`. Halaman: `pages/admin/laundry.vue` dan
`pages/resident/laundry.vue`, masing-masing `PageHeader` + `LaundrySection`.

## D6 · Catering

**Baca dulu:** DOMAIN § 12. Kode lama: `CateringPanel.vue`.

**Buat:** `components/catering/CateringSection.vue`, `CateringMenuForm.vue` (admin: nomor menu, penyedia,
waktu WIB → `wibInputToIso`), `CateringTable.vue` (kolom: Menu, Penyedia, Waktu catering, Rata-rata
rating, Jumlah penilai; penghuni: + Rating saya dan kolom "Beri rating"), `CateringRatingForm.vue`
(pilihan 1–5 ★ + tombol "Simpan rating", `PUT catering-menus/{id}/rating`). Halaman:
`pages/admin/catering.vue` dan `pages/resident/catering.vue`.

## D7 · Beranda

**Baca dulu:** DOMAIN § 13. Kode lama: `pages/admin.vue`, `pages/user/index.vue`.

**Buat:**
- `pages/admin/index.vue` — memuat `GET /api/dashboard`; hero + tiga kartu ringkasan. Daftar layanan
  didefinisikan sebagai array di `<script>`; pecah menjadi `components/dashboard/DashboardHero.vue` dan
  `DashboardServiceCard.vue` agar tidak ada baris panjang.
- `pages/resident/index.vue` — sambutan dengan `useSession().user.value?.name` (tanpa permintaan API).

---

# Fase E — Penutup

## E1 · Tes E2E dan smoke

**Baca dulu:** TESTING § 7 (seluruhnya, terutama "Basis data khusus dan pengaman") dan § 8.

**Buat:**
- `.env.e2e` di root repo: salinan `.env.example` dengan `APP_ENV=e2e`, `APP_KEY` hasil
  `php artisan key:generate --show`, dan `DB_DATABASE=gojags_asrama_e2e`. Tambahkan `.env.e2e` ke
  `.gitignore`, dan buat `.env.e2e.example` (tanpa `APP_KEY`) untuk di-commit.
- `frontend/scripts/reset-e2e-db.mjs` persis TESTING § 7. **Jangan** menjalankan `migrate:fresh` dengan
  cara lain, dan jangan pernah terhadap basis data yang namanya tidak berakhiran `_e2e`.
- `frontend/playwright.e2e.config.ts` persis TESTING § 7.
- `frontend/tests/e2e/accounts.ts` (`ACCOUNTS`, `STATE`), `global.setup.ts`, dan enam spec pada tabel
  "Isolasi antar-spec" di TESTING § 7: `auth`, `leave-permit`, `complaint`, `laundry`, `catering`, `rooms`.

**Ubah:** `frontend/scripts/smoke.mjs` → endpoint `/api/...` dan URL halaman yang baru.

Basis data `gojags_asrama_e2e` harus sudah ada di MySQL. Bila belum ada, **berhenti dan minta pemilik
repo membuatnya** — jangan membuat atau menghapus basis data sendiri.

**Selesai bila:** `npm run test:e2e` hijau, dan `npm run test:smoke` hijau terhadap server yang berjalan.
Bila PHP/MySQL tidak tersedia di lingkungan Anda, **laporkan bahwa tes ini belum dijalankan** — jangan
menyatakan lulus.

## E2 · Dokumentasi

**Ubah** agar menggambarkan struktur baru (bukan menambah di bawah yang lama):
- `CLAUDE.md` dan `AGENTS.md` — perintah, alur permintaan, struktur folder, data, test.
- `dbdiagram.txt` — skema persis SCHEMA.
- `README.md` — ganti isi bawaan Laravel dengan: deskripsi singkat, cara menjalankan, akun demo.
- `.env.example` — pastikan tidak ada variabel untuk aset Blade/Vite root.

Jangan mengubah apa pun di `../skripsi/`. Tulis di laporan akhir bahwa naskah dan diagram skripsi masih
memuat skema lama dan perlu diperbarui pemiliknya.

**Selesai bila:** `grep -rn "kamar_slots\|izin_keluar\|expectsJson\|Blade" CLAUDE.md AGENTS.md README.md dbdiagram.txt`
kosong.

## E3 · Pemeriksaan akhir

Jalankan semuanya dan laporkan keluaran apa adanya:

```powershell
composer test
vendor/bin/pint --test
cd frontend; npm run typecheck; npm run test:unit; npm run test:browser; npm run test:e2e; npm run build
```

Lalu periksa (semua harus kosong):

```powershell
grep -rnE "DB::table\(|expectsJson\(|\bview\(" app routes
grep -rnE "setInterval|setTimeout" frontend/app --include=*.vue
grep -rnE "waitForTimeout|test\.only" frontend/tests
grep -rnE "/backend/api|useAsrama|useTableSearch" frontend/app frontend/server frontend/scripts frontend/tests
grep -rnE "'(Diajukan|Disetujui|Ditolak|Selesai|Kosong|Terisi|Penuh)'" app --include=*.php --exclude-dir=Enums
awk 'length > 120 {print FILENAME": "FNR}' $(git ls-files 'app/*.php' 'routes/*.php' 'frontend/app/*.vue' 'frontend/app/*.ts')
```

Terakhir, cocokkan daftar "Dihapus tanpa pengganti" di MAP dengan isi repo: tidak boleh ada yang tersisa.

**Laporan akhir** berisi: tugas yang selesai, tugas yang dilewati beserta alasannya, tes yang tidak bisa
dijalankan, dan setiap tempat Anda menyimpang dari dokumen ini atau dari berkas skill.
