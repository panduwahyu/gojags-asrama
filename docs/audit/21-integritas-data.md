# Audit Integritas Data dan Konkurensi

Tanggal: 2026-10-15 · Commit yang diaudit: c034ff9

## Ringkasan

Invarian paling penting untuk alokasi kamar dijaga basis data: satu user satu slot, nomor kamar unik, `(room_id, position)` unik, dan satu rating per `(menu, user)`. Invarian lain hanya dijaga kode PHP atau tidak dijaga sama sekali; semuanya terbukti dengan probe sementara yang melanggarnya lewat model (probe sudah dihapus). **Tidak ada temuan Tinggi.** Temuan: 3 Sedang, 6 Rendah. Deadlock `SQLSTATE[40001]` **tidak terulang** pada 5 kali `php artisan test` berturut-turut (5/5 lulus, 124 tes). Semua jalur yang menulis `room_slots` sudah memakai `RoomLocks`.

## Temuan

| # | Tingkat | Temuan | Bukti | Saran perbaikan |
|---|---|---|---|---|
| 1 | Sedang | `returns_at > leaves_at` hanya dijaga `FormRequest`; DB menerima izin dengan waktu kembali sebelum keluar. Izin tumpang tindih juga tidak dijaga di mana pun | `SaveLeavePermitRequest.php:19` (`after:leaves_at`); `create_leave_permits_table.php:14-15` tanpa CHECK; probe `izin returns_at < leaves_at`: BERHASIL melanggar; probe `izin tumpang tindih`: BERHASIL | Tambahkan CHECK `returns_at > leaves_at` (MySQL ≥ 8.0.16 menegakkannya); tumpang tindih tetap lewat validasi + penguncian karena tidak bisa dinyatakan sebagai constraint sederhana |
| 2 | Sedang | Paling banyak satu `stays` aktif per user tidak dijaga DB; indeks `(user_id, checked_out_at)` tidak unik. Kode mencegahnya hanya lewat urutan `CheckIn`/`CheckOut` | `create_stays_table.php:22`; `CheckIn.php:23-27`; probe `dua stays aktif satu user`: BERHASIL melanggar | Kolom generated virtual (mis. `active_user_id = IF(checked_out_at IS NULL, user_id, NULL)`) dengan indeks unik. Penting bila keputusan no. 9 (alokasi ulang) dibangun |
| 3 | Sedang | Rating 1–5 hanya dijaga `RateCateringMenuRequest`; DB menerima `rating = 9` | `RateCateringMenuRequest.php:17` (`min:1`, `max:5`); `create_catering_ratings_table.php:15` (`unsignedTinyInteger` tanpa CHECK); probe `rating = 9`: BERHASIL | CHECK `rating BETWEEN 1 AND 5` |
| 4 | Rendah | Kapasitas 2 per kamar tidak dijaga DB: `room_slots.position` menerima 3 | `create_room_slots_table.php:14`; probe `slot position 3 (kapasitas 2)`: BERHASIL. Kapasitas hanya dari `config/asrama.php:6` dan `RoomSeeder` | CHECK pada `position` bila kapasitas tetap; bila kapasitas pindah ke kolom per kamar (keputusan no. 1) jaga lewat kode |
| 5 | Rendah | Menghapus user yang punya slot meninggalkan slot dengan `user_id` NULL tetapi `checked_in_at` tetap terisi | Probe `DELETE user`: `slot.user_id=NULL slot.checked_in_at=TERISI`. Dampak nol saat ini: `Room.php:34-38` mensyaratkan `user_id` tidak null, dan `RoomOccupancy.php:82` menimpa `checked_in_at` saat slot diisi lagi. Aplikasi tidak punya rute hapus user | Bersihkan `checked_in_at` pada penghapusan user (observer/Action) bila fitur hapus akun dibangun |
| 6 | Rendah | `checked_in_at` tanpa `user_id` tidak dicegah (keadaan yang sama dengan #5 dapat dibuat langsung) | Probe `slot checked_in_at tanpa user`: BERHASIL | CHECK `checked_in_at IS NULL OR user_id IS NOT NULL` — tidak cocok dengan `nullOnDelete` MySQL, sehingga perlu penanganan di kode (lihat #5) |
| 7 | Rendah | Kolom `status`, `role`, `gender`, `meal_time` bertipe string tanpa CHECK. Nilai tidak sah hanya ditolak oleh cast enum Eloquent, bukan oleh DB | Migrasi: `string('status', 20)` dsb.; probe `laundry status sembarang` dan `user role sembarang` ditolak dengan `ValueError` dari cast enum, bukan dari DB (`INSERT` mentah tidak diuji) | CHECK `status IN (...)` atau tipe `ENUM`; prioritas rendah selama semua tulis lewat model |
| 8 | Rendah | Nilai laundry tidak wajar diterima DB: `cost` negatif dan `set_count = 0` | Probe `laundry cost negatif & set_count 0`: BERHASIL; validasi ada di `UpdateLaundryOrderRequest.php:20-27` | CHECK `cost >= 0`, `set_count >= 1` |
| 9 | Rendah | `LaundryOrderController::update` tidak memakai transaksi maupun `lockForUpdate`, berbeda dengan izin dan keluhan; dua admin yang memperbarui bersamaan menimpa satu sama lain (last write wins) | `LaundryOrderController.php` (`update`); bandingkan `LeavePermitController.php:47,60` dan `ComplaintController.php:45`. Perilaku race tidak direproduksi | Samakan pola: transaksi + `lockForUpdate` (atau cek status sebelum menulis) |

Catatan pembacaan perilaku hapus (probe `DELETE …`, lihat tabel FK di bawah): menghapus user menghapus izin, keluhan, laundry, dan rating miliknya (cascade) tetapi **mempertahankan** riwayat `stays` karena `stays` menyalin nama dan nomor kamar; menghapus kamar menghapus slotnya dan menjaga `stays.room_number`. Tidak ada jalur hapus user/kamar di API; hanya menu yang dapat dihapus (`DELETE api/catering-menus/{menu}`), dan ratingnya ikut terhapus.

## Invarian: dijaga DB atau kode

| Invarian | Penjaga | Bukti |
|---|---|---|
| Satu user paling banyak satu slot | **DB** | `create_room_slots_table.php:15` (`user_id` unik); probe `user di dua slot` ditolak `UniqueConstraintViolationException` |
| Satu rating per (menu, user) | **DB** | `create_catering_ratings_table.php:18`; probe `rating ganda` ditolak |
| Nomor kamar unik | **DB** | `create_rooms_table.php:13`; probe `nomor kamar ganda` ditolak |
| `(room_id, position)` unik | **DB** | `create_room_slots_table.php:19`; probe `(room,position) ganda` ditolak |
| Paling banyak satu `stays` aktif per user | **Tidak dijaga DB**, dijaga alur kode | Temuan #2 |
| `leaves_at < returns_at` | **Kode saja** | Temuan #1 |
| Izin tidak tumpang tindih | **Tidak dijaga** (kode pun tidak, lihat audit 19) | Probe `izin tumpang tindih` |
| Rating 1–5 | **Kode saja** | Temuan #3 |
| Kapasitas 2 per kamar | **Kode/konfigurasi saja** | Temuan #4 |
| Nilai enum (`status`, `role`, …) | **Kode saja** (cast Eloquent) | Temuan #7 |

## Ringkasan skema (`database/migrations/2026_10_13_*`)

`rooms`

| Kolom | Tipe | Null | Bawaan | Indeks/unik |
|---|---|---|---|---|
| id | bigint unsigned | tidak | — | PK |
| number | varchar(10) | tidak | — | unik |
| floor | tinyint unsigned | tidak | — | indeks |
| timestamps | timestamp | ya | — | — |

`room_slots`

| Kolom | Tipe | Null | Bawaan | Indeks/unik · FK |
|---|---|---|---|---|
| id | bigint | tidak | — | PK |
| room_id | bigint | tidak | — | FK `rooms` · `onDelete` cascade |
| position | tinyint unsigned | tidak | — | unik `(room_id, position)` |
| user_id | bigint | ya | — | unik · FK `users` · `onDelete` set null |
| checked_in_at | timestamp | ya | — | — |

`stays`

| Kolom | Tipe | Null | Bawaan | Indeks/unik · FK |
|---|---|---|---|---|
| id | bigint | tidak | — | PK |
| user_id | bigint | ya | — | FK `users` · set null · indeks `(user_id, checked_out_at)` |
| room_id | bigint | ya | — | FK `rooms` · set null |
| user_name | varchar(255) | tidak | — | salinan |
| room_number | varchar(255) | tidak | — | salinan |
| floor | tinyint unsigned | tidak | — | salinan |
| checked_in_at | timestamp | tidak | — | — |
| checked_out_at | timestamp | ya | — | — |

`laundry_orders`

| Kolom | Tipe | Null | Bawaan | Indeks/unik · FK |
|---|---|---|---|---|
| id | bigint | tidak | — | PK |
| user_id | bigint | tidak | — | FK `users` · cascade · indeks `(user_id, status)` |
| items | varchar(255) | tidak | — | — |
| set_count | int unsigned | tidak | — | — |
| status | varchar(20) | tidak | `pending` | — |
| cost | decimal(12,2) | ya | — | — |

`catering_menus`

| Kolom | Tipe | Null | Bawaan | Indeks/unik |
|---|---|---|---|---|
| id | bigint | tidak | — | PK |
| menu_number | int unsigned | tidak | — | tanpa indeks |
| dish_name | varchar(255) | tidak | — | — |
| meal_time | varchar(20) | tidak | — | — |
| provider | varchar(255) | tidak | — | — |
| served_at | datetime | tidak | — | tanpa indeks |
| status | varchar(20) | tidak | `scheduled` | — |

`catering_ratings`

| Kolom | Tipe | Null | Bawaan | Indeks/unik · FK |
|---|---|---|---|---|
| id | bigint | tidak | — | PK |
| catering_menu_id | bigint | tidak | — | FK `catering_menus` · cascade · unik `(catering_menu_id, user_id)` |
| user_id | bigint | tidak | — | FK `users` · cascade |
| rating | tinyint unsigned | tidak | — | tanpa CHECK |

`leave_permits`

| Kolom | Tipe | Null | Bawaan | Indeks/unik · FK |
|---|---|---|---|---|
| id | bigint | tidak | — | PK |
| user_id | bigint | tidak | — | FK `users` · cascade · indeks `(user_id, status)` |
| leaves_at / returns_at | datetime | tidak | — | tanpa CHECK |
| reason | text | tidak | — | — |
| status | varchar(20) | tidak | `pending` | — |
| reviewed_by | bigint | ya | — | FK `users` · set null |
| reviewed_at | timestamp | ya | — | — |

`complaints`

| Kolom | Tipe | Null | Bawaan | Indeks/unik · FK |
|---|---|---|---|---|
| id | bigint | tidak | — | PK |
| user_id | bigint | tidak | — | FK `users` · cascade · indeks `(user_id, status)` |
| title | varchar(150) | tidak | — | — |
| body | text | tidak | — | — |
| status | varchar(20) | tidak | `pending` | — |
| resolved_by | bigint | ya | — | FK `users` · set null |
| resolved_at | timestamp | ya | — | — |

Setiap tabel juga memuat `created_at` dan `updated_at` (nullable). Kolom `users` (migrasi bawaan): `email` unik, `role` varchar(20) bawaan `resident`, `cohort`/`gender`/`religion` nullable.

## Konkurensi

- **Deadlock:** `php artisan test` dijalankan 5× berturut-turut; hasil 124 lulus pada keempatnya (5/5): durasi 26,4 s, 16,3 s, 16,1 s, 15,9 s, 16,9 s. Deadlock tidak terulang. Tidak ada proses `php artisan serve` maupun E2E yang berjalan saat itu.
- **Semua penulis `room_slots` memakai `RoomLocks`** (`grep -rnE "room_slots|RoomSlot::|->slot\(\)|RoomLocks::" app database/seeders`):
  - `AllocateRooms.php:32` → `RoomLocks::acquireAll()`; tulis lewat `RoomOccupancy.php:82`
  - `CheckIn.php:15` dan `CheckOut.php:15` → `RoomLocks::acquireFor()`
  - `UpdateResidentProfile.php:35` → `acquireFor()` (hanya membaca slot)
  - `MyRoomController.php:16` membaca slot tanpa kunci (baca saja)
  - `RoomSeeder.php:24` memakai `firstOrCreate` tanpa kunci (dijalankan manual, bukan saat layanan berjalan)
- **Semua `DB::transaction(..., 3)`** (`grep -rn "DB::transaction" app`): `AllocateRooms`, `CheckIn`, `CheckOut`, `UpdateResidentProfile`, `CateringRatingController` (kunci menu), `ComplaintController::resolve`, `LeavePermitController::update` dan `::review`. Pengecualian yang menulis tanpa transaksi: `LaundryOrderController::update` (temuan #9) dan `CateringMenuController` (`update`/`destroy`, risiko rendah karena satu baris).
- Urutan kunci konsisten `rooms` → `users` → `room_slots` di `RoomLocks::acquireAll()` dan `RoomLocks::acquireFor()` (`app/Support/RoomLocks.php`).

## Dugaan (belum terbukti)

- Penyebab deadlock pada awal sesi audit (18 kegagalan sekali) kemungkinan pemakaian bersamaan `gojags_asrama_testing` oleh proses lain (server/E2E). Tidak dapat direproduksi.
- `INSERT` mentah dengan nilai enum tidak sah kemungkinan diterima DB (kolom string tanpa CHECK); belum diuji langsung.

## Perintah yang dijalankan

- Tes sementara `tests/Feature/TmpIntegrityProbeTest.php` (2 tes, 14 probe) via `php artisan test tests/Feature/TmpIntegrityProbeTest.php` → hasil seperti di tabel; berkas **sudah dihapus** (`git status` tidak menampilkan `Tmp*`)
- `php artisan test` × 5 → 124 lulus (564 assertion) pada setiap putaran
- `grep -rnE "room_slots|RoomSlot::|->slot\(\)|RoomLocks::" app database/seeders`
- `grep -rn "DB::transaction" app`
- Pembacaan langsung `database/migrations/2026_10_13_*.php`, `app/Support/RoomLocks.php`, `app/Models/Room.php`
