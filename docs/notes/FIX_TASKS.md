# Tugas lanjutan setelah rewrite

Lanjutan dari `REWRITE_TASKS.md`. Rewrite sudah selesai sampai E2; dokumen ini berisi **semua** pekerjaan
yang tersisa, hasil dari empat audit (domain, domain mendalam, hasil rewrite, logika lanjutan) dan dari
jawaban pengelola asrama di `konfirmasi.md`.

| Fase | Isi | Tugas |
|---|---|---|
| 0 | Persiapan | P0 |
| R | Memperbaiki kekurangan hasil rewrite | R1–R7 |
| F | Memperbaiki cacat logika domain | F1–F9 |
| L | Memperbaiki kesalahan logika dari audit keempat | L1–L8 |
| S | Menerapkan keputusan pengelola yang sudah ada | S1–S3 |
| Z | Penutup | Z1–Z3 |
| Lampiran | Pekerjaan yang masih menunggu jawaban pengelola | — |

Aturan umum, perintah verifikasi, dan singkatan berkas sama seperti di `REWRITE_TASKS.md` (baca bagian
"Aturan umum" di sana lebih dulu):

| Singkatan | Berkas di `.claude/skills/gojags-asrama-architecture/` |
|---|---|
| SKILL | `SKILL.md` |
| BACKEND / FRONTEND / DOMAIN / SCHEMA / TESTING / MAP | `references/<nama>.md` |

**Berkas skill sudah diperbarui untuk semua tugas di bawah. Bila kode dan skill berbeda, skill yang benar.**

Aturan tambahan untuk dokumen ini:

1. Kerjakan berurutan. Satu tugas, satu commit: `git commit -m "fix(<ID>): <ringkasan>"`.
2. **Tes dulu.** Untuk tugas yang memperbaiki cacat, tulis tesnya lebih dulu dan pastikan **gagal**
   sebelum kode diperbaiki. Tes yang langsung lulus berarti tidak menguji cacatnya.
3. Jangan mengubah aturan bisnis selain yang tertulis. Pekerjaan di Lampiran **tidak boleh dikerjakan**.
4. Jangan menjalankan `migrate:fresh` terhadap basis data selain yang berakhiran `_testing` atau `_e2e`.
5. Jangan menyentuh `.claude/skills/`, `.agents/skills/`, `konfirmasi.md`, atau `../skripsi/`.
6. Bila tiga kali gagal, atau instruksi bertentangan dengan skill, berhenti dan laporkan.

---

# Fase 0 — Persiapan

## P0 · Titik awal

- `git branch --show-current` harus `rewrite`.
- `git status`: bila ada perubahan kode yang belum di-commit dan bukan buatan Anda, **berhenti dan
  laporkan**. Berkas dokumen yang belum terlacak (`FIX_TASKS.md`, `konfirmasi.md`) dan perubahan di
  folder skill boleh diabaikan — jangan di-commit oleh tugas kode; commit terpisah:
  `git add FIX_TASKS.md konfirmasi.md .claude/skills .agents/skills` lalu
  `git commit -m "docs: tugas lanjutan, konfirmasi pengelola, pembaruan skill"`.
- Catat hasil awal: `composer test`, `vendor/bin/pint --test`, dan di `frontend/`: `npm run typecheck`,
  `npm run test:unit`, `npm run test:browser`.

**Hasil awal yang diharapkan:** PHPUnit 70 lulus; pint bersih; typecheck bersih; Vitest 10 lulus;
Playwright tiruan 32 lulus dan 1 gagal (`csrf.spec.ts`, diperbaiki di R2). Bila berbeda, laporkan dulu.

---

# Fase R — Kekurangan hasil rewrite

## R1 · Pembatas peran di semua halaman

**Masalah:** hanya `pages/admin/rooms.vue` dan `pages/resident/room.vue` yang memuat
`definePageMeta({ role })`. Enam halaman lain tidak, sehingga penghuni bisa membuka `/admin`,
`/admin/laundry`, `/admin/catering`, dan admin bisa membuka halaman penghuni. Data tetap ditolak backend
(403), tetapi pengalihan tidak bekerja. `roles.spec.ts` lolos karena hanya menguji dua halaman itu.

**Baca dulu:** FRONTEND § 2; TESTING § 6 (baris `roles.spec.ts`).

**Tes dulu** — tulis ulang `frontend/tests/browser/roles.spec.ts`:
- Dua array: `ADMIN_PAGES = ['/admin', '/admin/rooms', '/admin/laundry', '/admin/catering']` dan
  `RESIDENT_PAGES = ['/resident', '/resident/room', '/resident/laundry', '/resident/catering']`.
- Dalam perulangan, untuk tiap halaman: tamu → `/login`; peran yang salah → beranda perannya; peran
  yang benar → tetap di halaman itu.
- Satu tes tambahan yang membaca isi folder `app/pages/admin` dan `app/pages/resident` (pakai
  `node:fs`) dan memastikan setiap berkas `.vue` ada di array di atas, supaya halaman baru tidak terlewat.

**Ubah:**
- `app/utils/navigation.ts` → tambah `requiredRole(path: string): Role | null` (`/admin` dan `/admin/…`
  → `'admin'`; `/resident` dan `/resident/…` → `'resident'`; selain itu `null`).
- `app/middleware/auth.global.ts` → pakai `requiredRole(to.path)` sebagai ganti `to.meta.role`.
- Hapus `definePageMeta({ role: … })` dari `admin/rooms.vue` dan `resident/room.vue`, serta deklarasi
  tipe `role` pada page meta bila ada.

**Selesai bila:** `npm run typecheck` bersih, `npm run test:browser` hijau untuk `roles.spec.ts`, dan
`grep -rn "definePageMeta({ role" frontend/app` kosong.

## R2 · Perbaiki `csrf.spec.ts` dan pola pencarian pesan

**Masalah:** `csrf.spec.ts` gagal: `section.getByRole('status')` cocok dengan dua elemen (pesan sukses
dan "Memuat…"). Hasilnya bergantung pada waktu muat, jadi tes bisa lulus atau gagal secara acak. Pola
yang sama mungkin ada di spec lain.

**Baca dulu:** TESTING § 8 (butir "Pesan dicari lewat teksnya").

**Ubah:**
- `frontend/tests/browser/csrf.spec.ts` → `section.getByRole('status').filter({ hasText: '…' })`.
- Cari pola serupa: `grep -rn "getByRole('status')\|getByRole('alert')" frontend/tests`. Setiap
  pemakaian yang langsung diberi `toContainText`/`toBeVisible` tanpa `filter({ hasText })` diperbaiki
  dengan cara yang sama.

Jangan mengubah komponen aplikasi untuk membuat tes lulus.

**Selesai bila:** `npx playwright test --repeat-each=5` (di `frontend/`) hijau seluruhnya.

## R3 · Pesan tiruan sama dengan pesan asli

**Masalah:** tes tiruan mengarang pesan sendiri, berbeda dari pesan backend. Contoh: "Email atau kata
sandi salah." (asli: "Email atau kata sandi tidak sesuai."), "Izin keluar berhasil disetujui." (asli:
"Izin keluar disetujui."), "Keluhan berhasil dikirim." (asli: "Keluhan asrama berhasil dikirim."), dan
format pesan alokasi.

**Baca dulu:** DOMAIN (semua pesan dalam tanda kutip); TESTING § 8 (butir "Pesan tiruan").

**Buat:** `frontend/tests/support/messages.ts` — satu objek konstanta `MESSAGES` berisi setiap pesan
sukses dan error dari DOMAIN, disalin huruf demi huruf, dikelompokkan per fitur
(`MESSAGES.leavePermit.submitted`, `MESSAGES.session.invalidCredentials`, …). Untuk pesan alokasi, buat
fungsi `allocationSummary(allocated, incomplete, waiting)`.

**Ubah:** semua spec di `tests/browser/` dan `tests/support/` memakai `MESSAGES`; tidak ada lagi string
`message: '…'` yang diketik langsung di spec (kecuali pesan teknis seperti `'Server error'`).

**Verifikasi silang:** untuk tiap nilai di `MESSAGES`, `grep -rn "<pesan>" app` harus menemukannya di
kode backend. Laporkan pesan yang tidak ditemukan.

**Selesai bila:** `npm run typecheck` bersih dan `npm run test:browser` hijau.

## R4 · Lengkapi tes polling

**Masalah:** `polling.spec.ts` hanya menguji "memuat ulang setiap 15 detik". Dua aturan lain belum diuji.

**Baca dulu:** DOMAIN § 4 (butir terakhir); TESTING § 8.

**Tambah tes** di `frontend/tests/browser/polling.spec.ts`, semuanya dengan `page.clock`:
- Tab tersembunyi (`document.hidden` bernilai true lewat `page.addInitScript` atau emulasi visibilitas)
  → setelah 15 detik **tidak** ada permintaan baru.
- Selama aksi tulis masih berjalan (tiruan POST ditahan), 15 detik berlalu → **tidak** ada permintaan
  daftar baru; setelah aksi selesai, polling berjalan lagi.
- Halaman laundry admin tidak melakukan polling (formulir status per baris tidak boleh ter-reset).

**Selesai bila:** `npm run test:browser` hijau.

## R5 · Pecah feature test menjadi satu aturan per method

**Masalah:** `CateringTest`, `ComplaintTest`, dan `LaundryOrderTest` masing-masing hanya punya 3 method
yang menguji banyak aturan sekaligus (mis. `test_resident_rating_bounds_overwrite_and_aggregates`). Bila
gagal, tidak jelas aturan mana yang rusak.

**Baca dulu:** TESTING § 3; BACKEND § 13.

**Ubah** ketiga berkas itu: pecah tiap method gabungan menjadi beberapa method, satu aturan DOMAIN per
method, bernama kalimat perilaku. **Tidak ada asersi yang dihapus** — hanya dipindahkan. Penyiapan data
yang berulang pindah ke method privat.

**Selesai bila:** `composer test` hijau; jumlah asersi total tidak lebih kecil dari sebelumnya (337);
tidak ada method tes yang namanya memuat lebih dari satu aturan yang digabung kata "and".

## R6 · Perapian sisa

**Baca dulu:** SKILL § "Aturan keterbacaan"; FRONTEND § 6–9.

**Ubah (tanpa mengubah perilaku atau teks antarmuka):**
- `frontend/app/components/ui/DataTable.vue` (165 baris) → pindahkan formulir cari ke
  `ui/TableSearch.vue` dan baris header yang bisa diurut ke `ui/SortableHeader.vue`; `DataTable` tinggal
  menyusun.
- `frontend/app/components/room/ResidentProfiles.vue` (152 baris) → pindahkan satu formulir profil ke
  `room/ResidentProfileForm.vue` (props: `resident`, `busy`; emit: `save`). Suntingan yang belum disimpan
  pada penghuni lain tetap tidak boleh hilang.
- `frontend/app/pages/admin/index.vue` (129 baris) → pindahkan daftar layanan ke
  `app/utils/dashboard.ts` dan susunan kartu ke `dashboard/DashboardServices.vue`; halaman tinggal meta,
  pengambilan data, dan tiga komponen.
- `frontend/nuxt.config.ts` → pecah satu baris yang lebih dari 120 karakter.
- Root repo: hapus folder `resources/` yang kosong; di `composer.json` hapus skrip `dev` (memanggil
  `npx concurrently`, padahal `package.json` root sudah tidak ada).

**Selesai bila:** `npm run typecheck` bersih; `npm run test:browser` hijau; `composer test` hijau; tidak
ada berkas `.vue` di `components/` atau `pages/` yang lebih dari 150 baris:
`wc -l $(git ls-files 'frontend/app/**/*.vue') | awk '$1 > 150 && $2 != "total"'` kosong.

## R7 · Jalankan tes E2E untuk pertama kali

**Masalah:** tugas E1 di-commit tanpa pernah dijalankan — basis data `gojags_asrama_e2e` tidak ada.
Enam spec E2E dan `global.setup.ts` belum terbukti jalan.

**Baca dulu:** TESTING § 7.

**Langkah:**
1. `php artisan migrate:status --env=e2e`. Bila jawabannya "Unknown database", **berhenti dan minta
   pemilik repo membuat basis data `gojags_asrama_e2e`**. Jangan membuatnya sendiri dan jangan mengganti
   `DB_DATABASE` di `.env.e2e`.
2. Setelah basis data ada: di `frontend/`, `npm run test:e2e`.
3. Perbaiki spec yang gagal. Bila kegagalan disebabkan cacat aplikasi (bukan cacat spec), jangan
   menyesuaikan spec — laporkan cacatnya.

**Selesai bila:** `npm run test:e2e` hijau dua kali berturut-turut. Bila langkah 1 membuat Anda berhenti,
tulis **"R7 BELUM DIJALANKAN"** di laporan dan lanjut ke fase F.

---

# Fase F — Cacat logika domain

## F1 · Cari dan urut status memakai label

**Masalah:** status disimpan sebagai `pending`/`approved`/`done`, dan `TableQuery` mencarinya dengan
`LIKE`. Mengetik "Diajukan", "Disetujui", "Ditolak", atau "Selesai" mengembalikan 0 baris. Urutan kolom
Status mengikuti abjad nilai Inggris: izin menjadi Disetujui → Diajukan → Ditolak, laundry menjadi
Selesai → Diajukan. Selain itu `%` dan `_` yang diketik pengguna bekerja sebagai wildcard.

**Baca dulu:** BACKEND § 7 (seluruhnya; kodenya lengkap dan sudah diuji); DOMAIN § 4.

**Tes dulu** (tambahkan ke `tests/Feature/TableQueryTest.php`, memakai rute sementara yang sudah ada):
- `q=Diajukan` menemukan hanya izin `pending`; `q=setuju` menemukan `approved`; `q=pending` (nilai
  mentah) menemukan 0 baris.
- `sort=status&direction=asc` mengurut Diajukan, Disetujui, Ditolak; `desc` kebalikannya.
- Dua baris ber-`reason` `alasan_satu` dan `alasanXdua`: `q=alasan_` menemukan 1 baris; `q=%25`
  menemukan 0 baris.
- Di `LaundryOrderTest`: `sort=status&direction=asc` → Diajukan sebelum Selesai.

**Ubah:**
- `app/Support/TableQuery.php` → persis BACKEND § 7.
- `LeavePermitController@index`, `ComplaintController@index`, `LaundryOrderController@index` → keluarkan
  `'status'` dari `search([...])` dan `sort([...])`, tambahkan `->status('status', <Enum>::class)`.
- Pastikan urutan `case` di `PermitStatus`, `ComplaintStatus`, `LaundryStatus` adalah urutan alur kerja.

**Selesai bila:** `composer test` hijau.

## F2 · Urutan kunci kamar konsisten

**Masalah:** `RoomLocks::acquireAll()` mengunci baris `rooms` berurut `floor` menurun lalu `number`,
sedangkan `acquireFor()` berurut `id`. Urutan berbeda pada baris yang sama bisa menimbulkan *deadlock*
antara alokasi dan check-in.

**Baca dulu:** BACKEND § 8 (paragraf urutan kunci); DOMAIN § 7 "Urutan kunci".

**Ubah:** `app/Support/RoomLocks.php` → kedua method memanggil satu method privat
`lockRooms(): Collection` yang mengurut `floor` menurun lalu `number`.

**Selesai bila:** `composer test` hijau, dan urutan `rooms` hanya ditulis di satu method.

## F3 · Pembatas login per alamat email

**Masalah:** `throttle:5,1` menghitung per IP. Semua permintaan datang lewat proxy Nuxt, jadi Laravel
melihat satu IP untuk seluruh pengguna: hanya 5 percobaan login per menit untuk semua orang, dan siapa
pun bisa mengunci login semua pengguna.

**Baca dulu:** DOMAIN § 2.

**Tes dulu** (di `tests/Feature/SessionTest.php`):
- 5 percobaan gagal untuk `a@example.id`, lalu percobaan ke-6 untuk email yang sama → 429.
- Setelah itu, login `b@example.id` dengan kata sandi benar tetap → 200.
- `A@Example.ID` dan `a@example.id` dihitung sebagai satu email.

**Ubah:**
- `app/Providers/RouteServiceProvider.php` → daftarkan limiter `login`:
  `Limit::perMinute(5)->by(Str::lower((string) $request->input('email')))`.
- `routes/api.php` → rute `login` memakai `throttle:login`.

Jangan mengubah `TrustProxies`.

**Selesai bila:** `php artisan test tests/Feature/SessionTest.php` hijau.

## F4 · Slot tidak mewarisi waktu check-in lama

**Masalah:** bila akun penghuni yang sudah check-in dihapus, `room_slots.user_id` menjadi null tetapi
`checked_in_at` tetap terisi. Alokasi berikutnya hanya mengisi `user_id`, sehingga penghuni baru langsung
dianggap sudah check-in, tidak bisa check-in, dan tidak bisa check-out.

**Baca dulu:** DOMAIN § 5 (butir `checked_in_at`).

**Tes dulu** (di `RoomAllocationTest`): satu slot kosong dengan `checked_in_at` terisi; jalankan alokasi
sampai slot itu terisi; pastikan `checked_in_at` null, status kamar `assigned`, dan penghuni barunya
berhasil `POST /api/my-room/check-in`.

**Ubah:** `AllocateRooms::assign()` → `$slot->update(['user_id' => …, 'checked_in_at' => null])`.

**Selesai bila:** `php artisan test tests/Feature/RoomAllocationTest.php` hijau.

## F5 · Kapasitas kamar dari konfigurasi

**Masalah:** angka `2` tertulis langsung di `RoomStatus::fromCounts` dan `Room::scopeOrderByStatus`.

**Baca dulu:** BACKEND § 9.

**Tes dulu:** `tests/Unit/RoomStatusTest.php` → tambah kasus kapasitas 3: (3,2)→assigned,
(2,2)→occupied, (3,3)→full.

**Ubah:**
- `RoomStatus::fromCounts(int $assigned, int $checkedIn, int $capacity)`; enum tidak memanggil `config()`.
- `Room`: accessor `status` mengoper `(int) config('asrama.room_capacity')`; `scopeOrderByStatus` memakai
  kapasitas lewat *binding*.
- Perbarui semua pemanggil.

**Selesai bila:** `composer test` hijau dan `grep -rn ">= 2" app/Enums app/Models` kosong.

## F6 · Biaya laundry bisa dikosongkan

**Masalah:** `$validated['cost'] ?? $order->cost` membuat `cost: null` tidak mengosongkan biaya.

**Baca dulu:** DOMAIN § 11.

**Tes dulu** (di `LaundryOrderTest`): Selesai dengan 15000 → kembali ke `pending` dengan `cost: null` →
biaya null. Kirim hanya `status` tanpa kunci `cost` pada pesanan berbiaya → biaya tidak berubah.

**Ubah:** `LaundryOrderController@update` → bedakan dengan `array_key_exists('cost', $validated)`.

**Selesai bila:** `php artisan test tests/Feature/LaundryOrderTest.php` hijau.

## F7 · Rating catering dalam transaksi

**Masalah:** `updateOrCreate` tanpa kunci; dua kiriman bersamaan bisa menabrak indeks unik (500).

**Baca dulu:** DOMAIN § 12.

**Ubah:** `CateringRatingController@update` → `DB::transaction(..., 3)`; kunci baris menu
(`CateringMenu::lockForUpdate()->findOrFail($menu->id)`) lalu `updateOrCreate`.

**Selesai bila:** `php artisan test tests/Feature/CateringTest.php` hijau.

## F8 · Tabel tidak menampilkan jawaban usang

**Masalah:** `useTable.refresh()` menerapkan setiap jawaban yang tiba; jawaban lambat bisa menimpa hasil
yang lebih baru. Variabel `Changed` di `applySearch()` bernama terbalik dari isinya.

**Baca dulu:** FRONTEND § 3 (`useTable`, paragraf terakhir).

**Tes dulu** (di `data-table.spec.ts`): jawaban `q=lama` ditahan 600 ms, `q=baru` langsung; ketik
"lama" lalu ganti "baru"; setelah keduanya tiba, tabel tetap menampilkan baris "baru". Pakai
`page.clock`, tanpa `waitForTimeout`.

**Ubah:** `frontend/app/composables/useTable.ts` → penghitung permintaan; hanya jawaban bernomor terbaru
yang diterapkan (termasuk `loadError` dan `loading`). Ganti nama `Changed` → `unchanged`.

**Selesai bila:** `npm run typecheck` bersih dan `npm run test:browser` hijau.

## F9 · Tes untuk aturan yang belum tercakup

**Baca dulu:** DOMAIN § 5, § 7, § 8; TESTING § 3.

**Tambah tes — tanpa mengubah kode aplikasi. Bila tes gagal, berhenti dan laporkan; jangan menyesuaikan
tes dengan perilaku:**
- `RoomAllocationTest`:
  - Tidak ada lantai kosong utuh → angkatan baru berlantai asal di lantai tertinggi yang masih punya slot.
  - Di lantai yang sama: kamar yang penghuni pertamanya se-angkatan dipilih sebelum kamar terisi sebagian
    milik angkatan lain, dan keduanya sebelum kamar kosong.
  - Angkatan berlantai asal 5 yang lantainya penuh meluap ke lantai 4.
  - Angkatan diproses menurut urutan alami: `"9"` sebelum `"10"`.
  - Angkatan yang ditulis `" 64 "` dan `"64"` diperlakukan sebagai satu angkatan.
- `RoomTest`: `sort=resident` (naik: kamar kosong dulu, lalu nama penghuni pertama menurut abjad) dan
  `sort=assigned_count`.
- `MyRoomTest`: check-out saat baris `stays` aktif tidak ada → 422 "Riwayat check-in aktif tidak
  ditemukan." dan slot tetap tertahan.
- `LeavePermitTest`: mengirim `status`, `user_id`, atau `reviewed_by` saat mengajukan izin tidak
  berpengaruh (izin tetap `pending` milik pengirim).
- `LaundryOrderTest`: mengirim `status` atau `cost` saat mengajukan tidak berpengaruh.

**Selesai bila:** `composer test` hijau.

---

# Fase L — Kesalahan logika dari audit keempat

## L1 · Alokasi tidak gagal total bila slot dan kapasitas berbeda

**Masalah:** `AllocateRooms` menganggap kamar masih muat bila jumlah penghuninya lebih kecil dari
`config('asrama.room_capacity')`, tanpa melihat apakah baris slot kosongnya ada. Bila kapasitas di
konfigurasi dinaikkan menjadi 3 tanpa menjalankan ulang `RoomSeeder`, atau ada kamar tanpa slot, alokasi
menjawab **500 "Slot kamar tidak konsisten."** dan tidak seorang pun mendapat kamar (terbukti lewat
uji-coba).

**Baca dulu:** DOMAIN § 7 (butir "kandidat"); BACKEND § 8.

**Tes dulu** (di `RoomAllocationTest`):
- Inventaris dibuat dengan kapasitas 2, lalu `config(['asrama.room_capacity' => 3])`: alokasi → 200;
  tiap kamar terisi paling banyak 2 orang; sisanya masuk `waiting`.
- Satu kamar tanpa baris slot di lantai 5: alokasi → 200 dan kamar itu dilewati.

**Ubah:** `app/Actions/AllocateRooms.php` → hitung slot kosong per kamar dari koleksi `$slots` yang sudah
dikunci (peta `room_id => jumlah slot kosong`, dikurangi tiap kali `assign()`); `homeFloorFor()` dan
`findRoomFor()` memakai peta itu, bukan `count($people) < $capacity`. `RuntimeException` di `assign()`
tetap ada sebagai pengaman.

**Selesai bila:** `php artisan test tests/Feature/RoomAllocationTest.php` hijau.

## L2 · Akun demo tidak dibuat di produksi

**Masalah:** `docker/backend-entrypoint.sh` menjalankan `php artisan migrate --seed --force` setiap kali
kontainer menyala, dan `DatabaseSeeder` selalu memanggil `AccountSeeder`. Akibatnya akun
`admin@example.id` / `admin123` selalu ada di lingkungan mana pun — termasuk produksi — dan dibuat ulang
bila dihapus.

**Baca dulu:** DOMAIN § 14.

**Tes dulu** (di `SeederTest`):
- Lingkungan `production` (`$this->app->detectEnvironment(fn () => 'production')`): setelah
  `$this->seed()` ada 78 kamar dan **0 user**.
- Lingkungan `testing`: akun demo tetap dibuat (tes yang ada tetap lulus).

**Ubah:** `database/seeders/DatabaseSeeder.php` → `RoomSeeder` selalu dipanggil; `AccountSeeder` hanya
bila `app()->environment(['local', 'testing', 'e2e'])`.

Jangan mengubah `docker-compose*.yml` atau `backend-entrypoint.sh`; laporkan saja bahwa
`docker-compose.yml` berbawaan `APP_ENV=local` dan `APP_DEBUG=true`, yang harus diganti pemilik repo
untuk produksi.

**Selesai bila:** `php artisan test tests/Feature/SeederTest.php` hijau.

## L3 · Jumlah kamar di kepala halaman tidak ikut hasil pencarian

**Masalah:** `pages/admin/rooms.vue` menulis "`N` kamar di lantai 3, 4, dan 5" dengan `N = meta.total`,
yaitu jumlah hasil pencarian. Mencari "501" mengubah teksnya menjadi "1 kamar di lantai 3, 4, dan 5".

**Baca dulu:** DOMAIN § 5 (butir terakhir).

**Tes dulu** (di `tests/browser/rooms.spec.ts`): monitoring tiruan berjumlah 78, `meta.total` 1 → kepala
halaman tetap menampilkan "78 kamar".

**Ubah:** `frontend/app/pages/admin/rooms.vue` → `N` = jumlah keempat nilai `monitoring` (ringkasan itu
selalu mencakup semua kamar); sebelum data tiba, kalimatnya tanpa angka. Hapus angka `78` yang tertulis
langsung.

**Selesai bila:** `npm run typecheck` bersih dan `npm run test:browser` hijau.

## L4 · Daftar profil penghuni ikut dimuat ulang

**Masalah:** di halaman kamar admin, daftar profil penghuni hanya dimuat saat halaman dibuka dan setelah
alokasi. Bila penghuni check-out (slotnya lepas), profilnya tetap tampil "Sudah dialokasikan" dan
terkunci sampai halaman dimuat ulang. Di versi lama daftar ini ikut diperbarui tiap 15 detik.

**Baca dulu:** DOMAIN § 4 (dua butir terakhir) dan § 6.

**Tes dulu** (di `tests/browser/rooms.spec.ts`, dengan `page.clock`):
- Penghuni A awalnya ber-`room_id` terisi; setelah 15 detik tiruan mengembalikan `room_id: null` →
  formulir A terbuka kembali.
- Selama itu, isian yang sudah diketik pada formulir penghuni B (belum disimpan) **tidak hilang**.

**Ubah:** `frontend/app/components/room/ResidentProfiles.vue` → pakai `usePolling(loadResidents, …)`
dengan jeda saat `action.busy`. Penggabungan yang sudah ada (pertahankan baris yang `room_id`-nya tidak
berubah) dipertahankan.

**Selesai bila:** `npm run test:browser` hijau.

## L5 · Menyimpan satu baris laundry tidak menghapus isian baris lain

**Masalah:** `LaundryStatusForm` me-reset isiannya setiap kali objek `order` berganti. Setelah admin
menyimpan satu baris, tabel dimuat ulang, semua objek baris berganti, dan status/biaya yang sudah diketik
di baris **lain** hilang. Versi lama menyimpan isian per id.

**Baca dulu:** DOMAIN § 4 (butir "tidak boleh menghapus isian").

**Tes dulu** (di `tests/browser/laundry.spec.ts`): dua pesanan; admin mengetik biaya di baris kedua
(belum disimpan), lalu menyimpan baris pertama → biaya yang diketik di baris kedua masih ada.

**Ubah:** `LaundryStatusForm.vue` → `watch` hanya pada nilai yang datang dari server
(`() => [props.order.status, props.order.cost]`), sehingga isian hanya di-reset bila data baris itu
sendiri berubah. Pastikan `:key` baris di `LaundryTable` adalah `order.id`.

**Selesai bila:** `npm run test:browser` hijau.

## L6 · Sesi berakhir mengalihkan ke login

**Masalah:** bila sesi habis saat halaman terbuka, tabel hanya menampilkan "Gagal memuat data." tiap 15
detik dan aksi tulis menampilkan pesan error; pengguna tidak dialihkan. Logout yang dijawab 401 juga
membuat pengguna terjebak di tampilan "sudah login".

**Baca dulu:** DOMAIN § 2 (butir terakhir); FRONTEND § 3.

**Tes dulu** (`tests/browser/session-expiry.spec.ts`, baru):
- Daftar tabel dijawab 401 → pengguna tiba di `/login`.
- Aksi tulis dijawab 401 → pengguna tiba di `/login`.
- `POST logout` dijawab 401 → pengguna tiba di `/login`.

**Ubah:**
- `composables/useSession.ts` → tambah `expire()`: mengosongkan `user` lalu `navigateTo('/login')`.
- `composables/useApi.ts` → `get` dan `send` memanggil `expire()` saat 401, lalu tetap melempar error.
- `composables/useTable.ts` → memuat data lewat `useApi().get` (bukan `$fetch` langsung), sehingga 401
  ikut tertangani.
- Pengecualian: `POST login` yang dijawab 401/422 tidak memanggil `expire()`.

**Selesai bila:** `npm run typecheck` bersih dan `npm run test:browser` hijau.

## L7 · Tabel pindah ke halaman terakhir bila halamannya hilang

**Masalah:** bila pengguna berada di halaman 3 lalu datanya berkurang sampai tinggal 2 halaman, server
menjawab daftar kosong dan tabel menampilkan "belum ada data", padahal data masih ada.

**Baca dulu:** DOMAIN § 4 (butir terakhir).

**Tes dulu** (di `data-table.spec.ts`): pengguna di halaman 2; muat ulang berikutnya dijawab
`data: []`, `meta.last_page: 1`, `meta.current_page: 2` → tabel meminta halaman 1 dan menampilkan
barisnya.

**Ubah:** `composables/useTable.ts` → setelah jawaban diterapkan, bila `page > meta.last_page` dan
`meta.last_page >= 1`, set `page` ke `meta.last_page` (watcher memuat ulang sekali).

**Selesai bila:** `npm run test:browser` hijau.

## L8 · Pesan error sesuai jenis kegagalan

**Masalah:** `errorMessage()` menampilkan "Tidak dapat menghubungi server. Pastikan backend Laravel
berjalan." untuk semua yang bukan 401/403/419/422/429 — termasuk 404 dan 500, padahal servernya jelas
terjangkau. Pesan itu juga menyebut nama teknologi kepada pengguna akhir.

**Baca dulu:** FRONTEND § 5.

**Tes dulu** (di `tests/unit/errors.test.ts`):
- 404 → "Data tidak ditemukan. Mungkin sudah diubah atau dihapus."
- 500 dan 5xx lain → "Terjadi kesalahan di server. Coba kembali sebentar lagi."
- Tanpa status (jaringan putus) → "Tidak dapat menghubungi server. Periksa koneksi Anda."
- Kasus 401/403/419/422/429 yang sudah ada tidak berubah.

**Ubah:** `frontend/app/utils/errors.ts` sesuai tes; tambahkan ketiga pesan ke
`tests/support/messages.ts`.

**Selesai bila:** `npm run test:unit` hijau.

---

# Fase S — Keputusan pengelola yang sudah ada

Sumber: `konfirmasi.md` nomor 16 dan 17. Hanya butir yang **sudah dicentang** yang dikerjakan.

| Nomor | Jawaban | Tugas |
|---|---|---|
| 16a | Penghuni perlu melihat nama makanan dan waktu makan (pagi/siang/malam) | S1, S3 |
| 16b | *(belum dijawab)* — tanda tahapan menu | tidak dikerjakan; status dibiarkan seperti sekarang |
| 16c | Petugas bisa memperbaiki dan menghapus menu yang salah | S1, S3 |
| 17a | Makanan yang belum dihidangkan tidak boleh dinilai | S2, S3 |
| 17b | Nilai boleh diganti "sampai ganti hari" | S2, S3 — **tafsiran, lihat catatan S2** |
| 17c | Tidak perlu kolom komentar | tidak ada pekerjaan |

## S1 · Menu catering: nama makanan, waktu makan, ubah, hapus (backend)

**Baca dulu:** DOMAIN § 12; SCHEMA § `catering_menus` dan § "Enum dan label"; BACKEND § 1–6.

**Tes dulu** (di `CateringTest`, satu aturan per method):
- Tambah menu dengan `dish_name` dan `meal_time` → 201; keduanya tersimpan dan muncul di daftar.
- `dish_name` kosong / lebih dari 255 karakter → 422; `meal_time` di luar tiga pilihan → 422.
- `PATCH` hanya `dish_name` → hanya kolom itu berubah → "Menu catering berhasil diperbarui."
- `PATCH` hanya `status` → status berubah, kolom lain tetap.
- `DELETE` → "Menu catering berhasil dihapus."; menu dan rating-nya hilang.
- Penghuni memanggil `PATCH`/`DELETE` → 403; tamu → 401; id tidak ada → 404.
- Cari `q=<nama makanan>` menemukan menunya; `sort=meal_time&direction=asc` mengurut pagi, siang, malam.

**Buat/ubah:**
- `app/Enums/MealTime.php` — `Breakfast`, `Lunch`, `Dinner` (urutan ini), label Pagi / Siang / Malam.
- Migrasi `create_catering_menus_table` → tambah `dish_name` (string 255) dan `meal_time` (string 20).
  Ubah migrasi yang ada (basis data dibangun ulang); jangan membuat migrasi `add_…`.
- `CateringMenu` → `$fillable` dan `$casts` (`meal_time => MealTime::class`).
- `StoreCateringMenuRequest` → tambah `dish_name`, `meal_time` (wajib).
- `UpdateCateringMenuRequest` → semua kolom `sometimes`: `menu_number`, `dish_name`, `meal_time`,
  `provider`, `served_at`, `status`.
- `CateringMenuController` → `update` mengisi kolom yang dikirim; tambah `destroy`.
- `CateringMenuResource` → tambah `dish_name`, `meal_time`, `meal_time_label`.
- `routes/api.php` → `DELETE catering-menus/{menu}` (admin).
- `TableQuery` di `index` → `search` tambah `dish_name`; `sort` tambah `dish_name`; `meal_time` didaftarkan
  lewat `->status('meal_time', MealTime::class)` agar dicari lewat label dan diurut pagi → malam.
- `CateringMenuFactory` → isi kedua kolom baru.
- `RoleAccessTest` → tambah endpoint `DELETE`.

**Selesai bila:** `composer test` hijau dan `vendor/bin/pint --test` bersih.

## S2 · Aturan penilaian catering (backend)

**Catatan tafsiran:** jawaban 17b tertulis "sampai ganti hari". Ditafsirkan sebagai: rating boleh
diberikan atau diganti sampai pukul 23.59 WIB pada tanggal menu dihidangkan. Tafsiran ini sedang
dikonfirmasi ke pengelola. Kerjakan sesuai tafsiran, dan taruh batasnya di **satu method**
(`CateringMenu::ratingClosesAt()`) supaya mudah diganti.

**Baca dulu:** DOMAIN § 12 (butir rating); TESTING § 3 (`travelTo`).

**Tes dulu** (di `CateringTest`, dengan `$this->travelTo(...)`; waktu ditulis UTC):
- Menu dihidangkan besok → rating ditolak 422 pada `rating`: "Menu belum dihidangkan sehingga belum
  bisa dinilai."
- Menu dihidangkan hari ini pukul 12.00 WIB; sekarang 12.01 WIB → rating diterima.
- Sekarang 23.59 WIB hari yang sama → rating masih bisa diganti.
- Sekarang 00.00 WIB hari berikutnya → 422: "Masa penilaian menu ini sudah berakhir."
- Menu dihidangkan pukul 23.30 WIB (= 16.30 UTC): batasnya tetap 23.59 WIB hari itu, **bukan** 23.59 UTC.
- Daftar menu memuat `can_rate` yang benar untuk ketiga keadaan (belum dihidangkan, terbuka, ditutup).

**Ubah:**
- `CateringMenu` → `ratingClosesAt(): Carbon` (akhir hari `served_at` dalam zona `Asia/Jakarta`,
  dikembalikan dalam UTC) dan `canBeRated(): bool`. Zona waktu diambil dari
  `config('asrama.display_timezone')`; tambahkan kunci itu ke `config/asrama.php` (`'Asia/Jakarta'`).
- `CateringRatingController@update` → di dalam transaksi F7, sebelum menyimpan, lempar
  `ValidationException` pada `rating` dengan pesan di atas bila belum dihidangkan / sudah ditutup.
- `CateringMenuResource` → tambah `can_rate`.

**Selesai bila:** `php artisan test tests/Feature/CateringTest.php` hijau.

## S3 · Catering di frontend

**Baca dulu:** FRONTEND § 4, § 7, § 9; DOMAIN § 12 (butir "Tampilan").

**Tes dulu** (di `tests/browser/catering.spec.ts`; data contoh lewat `sampleCateringMenu`):
- Admin menambah menu: permintaan memuat `dish_name` dan `meal_time`; baris baru menampilkan nama
  makanan dan "Siang".
- Admin menekan "Edit" pada satu menu → formulir terisi → simpan → permintaan `PATCH` memuat perubahan.
- Admin menekan "Hapus" → muncul tombol konfirmasi "Ya, hapus" di baris itu → permintaan `DELETE`
  terkirim → baris hilang. Menekan "Batal" tidak mengirim apa pun.
- Penghuni: menu ber-`can_rate: false` yang belum dihidangkan menampilkan "Belum dihidangkan" tanpa form
  rating; yang sudah lewat menampilkan "Penilaian ditutup"; yang `can_rate: true` menampilkan form.
- Pesan error server saat rating ditolak tampil di layar.

**Ubah:**
- `app/types/catering.ts` → `MealTime`, `dish_name`, `meal_time`, `meal_time_label`, `can_rate`; perbarui
  `CateringMenuInput`.
- `tests/support/samples.ts` dan `tests/support/messages.ts` → kolom dan pesan baru.
- `CateringMenuForm.vue` → kolom Nama makanan dan pilihan Waktu makan; menerima prop `menu` untuk mode
  edit (pola sama dengan `LeavePermitForm`).
- `CateringTable.vue` → kolom Nama makanan dan Waktu makan; admin mendapat kolom Aksi (Edit, Hapus).
  Konfirmasi hapus dibuat di dalam baris (dua langkah), **bukan** dengan `window.confirm`.
- `CateringRatingForm.vue` / sel rating → ikuti `can_rate`.
- `CateringSection.vue` → `useApi()` perlu method `delete`; tambahkan ke `useApi.ts` dengan pola yang
  sama (header CSRF, ulang sekali saat 419).

**Selesai bila:** `npm run typecheck` bersih, `npm run test:browser` hijau, tidak ada berkas `.vue` baru
yang lebih dari 150 baris.

---

# Fase Z — Penutup

## Z1 · Tes E2E mengikuti perubahan

Hanya bila R7 berhasil dijalankan. Sesuaikan `tests/e2e/catering.spec.ts` dengan S1–S3 (menu dibuat
dengan nama makanan, waktu makan, dan `served_at` beberapa menit yang lalu agar bisa dinilai), tambahkan
langkah "admin mengubah lalu menghapus menu", lalu `npm run test:e2e` hijau.

Bila R7 belum bisa dijalankan, tulis **"Z1 BELUM DIJALANKAN"** di laporan.

## Z2 · Dokumentasi

Perbarui agar cocok dengan kode: `CLAUDE.md` dan `AGENTS.md` (pembatas peran lewat awalan URL, limiter
`login`, `MealTime`, endpoint `DELETE catering-menus/{menu}`), `dbdiagram.txt` (kolom baru
`catering_menus`), `README.md` bila memuat hal yang berubah.

**Selesai bila:** tidak ada dokumen yang menyebut `definePageMeta({ role` atau `throttle:5,1`.

## Z3 · Pemeriksaan akhir

Jalankan tugas **E3** di `REWRITE_TASKS.md` seluruhnya (belum pernah dikerjakan), ditambah:

```powershell
cd frontend; npx playwright test --repeat-each=3
grep -rn "definePageMeta({ role" frontend/app
grep -rnE "message: '[A-Z][a-z]" frontend/tests/browser
grep -rn ">= 2" app/Enums app/Models
```

Ketiga `grep` harus kosong.

**Laporan akhir** memuat: daftar commit `fix(…)`; keluaran tiap perintah apa adanya; tugas yang ditandai
"BELUM DIJALANKAN" beserta sebabnya; setiap tempat Anda menyimpang dari dokumen ini atau dari skill; dan
setiap cacat aplikasi yang ditemukan tes tetapi tidak diperbaiki.

---

# Lampiran — Menunggu jawaban pengelola (JANGAN dikerjakan)

Pekerjaan berikut bergantung pada jawaban di `konfirmasi.md` yang belum diisi. Dicantumkan agar jelas
apa yang masih terbuka dan bagian kode mana yang akan terkena.

| Nomor | Keputusan yang ditunggu | Bagian yang akan berubah |
|---|---|---|
| 1 | Data kamar benar? Kamar rusak bisa ditandai? | `config/asrama.php`, `RoomSeeder`, tabel `rooms`, `RoomStatus` |
| 2 ★ | Lantai penuh → boleh ke lantai lain? | `AllocateRooms::findRoomFor()` |
| 3 | Berbagi lantai/kamar antar-angkatan | `AllocateRooms::homeFloorFor()`, `findRoomFor()` |
| 4 ★ | Syarat seagama; laki-laki/perempuan beda lantai | `AllocateRooms` (kandidat kamar, lantai asal) |
| 5 ★ | Petugas memilih, memindah, membatalkan kamar | Action baru + `RoomLocks`; halaman kamar admin |
| 6 ★ | Cara mendaftarkan penghuni; pilihan agama; format angkatan | Kelola akun; enum agama; validasi angkatan |
| 7 | Memperbaiki data setelah dapat kamar | `UpdateResidentProfile` |
| 8 ★ | Siapa yang mencatat check-in/out | `CheckIn`, `CheckOut`, halaman kamar |
| 9 ★ | Setelah check-out: boleh kembali? pembatalan? | `AllocateRooms::pendingResidents()`, Action batal check-out |
| 10 ★ | Batasan waktu izin | `SaveLeavePermitRequest` |
| 11 | Pencatatan jam keluar/kembali sebenarnya | Tabel `leave_permits`, `PermitStatus` |
| 12 | Mengubah keputusan, membatalkan izin, alasan penolakan | `LeavePermitController`, kolom alasan |
| 13 | Balasan petugas, "Sedang ditangani", buka kembali | Tabel `complaints`, `ComplaintStatus` |
| 14 ★ | Cara menghitung biaya laundry; pembayaran; batas | `UpdateLaundryOrderRequest`, tabel `laundry_orders` |
| 15 | Tahapan laundry; pembatalan | `LaundryStatus`, `LaundryOrderController` |
| 16b | Tanda tahapan menu catering | `CateringStatus` (dipakai atau dihapus) |
| 17b | Konfirmasi tafsiran "sampai ganti hari" | `CateringMenu::ratingClosesAt()` |
| 18 ★ | Jumlah pemakai | Limiter login, paginasi daftar profil |
| 19 | Fasilitas yang harus ada sebelum dipakai | Kata sandi, pemberitahuan, pengumuman, laporan, peran lain |
| 20 | Data sungguhan di versi lama | Skrip pemindahan data |

Cara menurunkan tiap jawaban menjadi pekerjaan ada di `DEDUKSI_JAWABAN_STAKEHOLDER.md`.

---

# Prompt untuk agen

```text
Kamu melanjutkan pekerjaan di repo ini pada cabang `rewrite`.

Baca sampai habis, berurutan:
1. FIX_TASKS.md
2. Bagian "Aturan umum" di REWRITE_TASKS.md
3. .claude/skills/gojags-asrama-architecture/SKILL.md

Kerjakan tugas di FIX_TASKS.md berurutan: P0, R1–R7, F1–F9, L1–L8, S1–S3, Z1–Z3.
JANGAN mengerjakan apa pun dari bagian "Lampiran".

Untuk tiap tugas:
1. Tulis "Mulai <ID> — <judul>".
2. Baca setiap berkas dan bagian (§) yang disebut di "Baca dulu". Jangan mengandalkan ingatan.
3. Bila tugas punya bagian "Tes dulu": tulis tesnya, jalankan, dan pastikan GAGAL. Tempelkan pesan
   gagalnya. Bila tes langsung lulus, tesmu tidak menguji cacatnya — perbaiki tesnya dulu.
4. Kerjakan bagian "Ubah"/"Buat". Jangan mengubah hal lain.
5. Jalankan perintah "Selesai bila" dan baca keluarannya. Bila gagal, perbaiki KODE, bukan melemahkan
   tes. Setelah tiga kali gagal, berhenti dan tampilkan error lengkapnya.
6. `git add -A` lalu `git commit -m "fix(<ID>): <ringkasan>"`. Tulis "Selesai <ID>".

Kondisi berhenti khusus:
- P0: hasil awal berbeda dari yang tertulis, atau ada perubahan kode milik orang lain.
- R7: basis data gojags_asrama_e2e tidak ada → jangan dibuat; tulis "R7 BELUM DIJALANKAN" dan lanjut.
- F9: tes baru gagal → jangan ubah kode maupun tes; berhenti dan laporkan.
- Tes E2E atau Playwright gagal karena cacat aplikasi → laporkan cacatnya, jangan sesuaikan tesnya.

Larangan:
- Mengubah aturan bisnis yang tidak disebut di tugas.
- Menjalankan migrate:fresh pada basis data yang namanya tidak berakhiran _testing atau _e2e.
- Menyentuh .claude/skills/, .agents/skills/, konfirmasi.md, atau ../skripsi/.
- Memakai window.confirm / alert, waitForTimeout, test.only, test.skip.
- Menulis "lulus" atau "selesai" untuk perintah yang tidak kamu jalankan.

Akhiri dengan laporan sesuai bagian "Laporan akhir" pada tugas Z3.
```
