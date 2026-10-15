# Audit Performa di Bawah Beban

Tanggal: 2026-10-15 · Commit yang diaudit: 46de8c5

## Ringkasan

Target (keputusan stakeholder no. 18): 156 penghuni, 100–150 login serentak, polling 15 detik. Pada data seukuran target (78 kamar, 156 slot terisi, 156 pesanan laundry/izin/keluhan, 30 menu dengan 600 rating) **tidak ada indikasi N+1**: jumlah query konstan (2–5) baik pada `per_page=10` maupun `100` untuk semua endpoint `GET`, dan waktu respons 1–37 ms. Titik yang paling mungkin menjadi hambatan bukan polling, melainkan **login serentak**: satu verifikasi bcrypt (cost 12) memakan ±360 ms CPU, sehingga 150 login bersamaan ≈ 54 detik CPU bila server hanya punya satu worker. Temuan: 1 Sedang, 5 Rendah. Uji beban terhadap server **tidak dijalankan**; seluruh angka berasal dari pengukuran satu permintaan pada lingkungan uji (MySQL lokal, tanpa proxy Nuxt, sesi `array`).

## Temuan

| # | Tingkat | Temuan | Bukti | Saran perbaikan |
|---|---|---|---|---|
| 1 | Sedang | Login serentak dibatasi CPU bcrypt: 360 ms per verifikasi (cost 12). 150 login bersamaan ≈ 54 detik CPU; pada server satu worker, pengguna terakhir menunggu hampir semenit | `php -r` (pengukuran): `cost=12 verify rata-rata 360 ms`, `cost=10 … 87 ms`; `BCRYPT_ROUNDS=12` di `.env.example:16` dan `config/hashing.php:5`; `PHP_CLI_SERVER_WORKERS` tidak diatur | Jalankan di PHP-FPM/beberapa worker (jumlah worker ≈ jumlah core); atau turunkan `BCRYPT_ROUNDS` ke 10 (≈ 13 detik untuk 150 login) dengan sadar akan trade-off keamanan; pertimbangkan login berselang bila ada acara serentak |
| 2 | Rendah | `/api/rooms` dipolling dan memuat **semua** 78 kamar di setiap permintaan untuk ringkasan monitoring | `RoomController.php` (`monitoring()`: `Room::withOccupancy()->get()`); 5 query, 20–37 ms | Cukup pada skala ini; bila kamar bertambah, hitung ringkasan dengan satu query agregat |
| 3 | Rendah | Beban polling dominan: pada `/resident/room` ada 2 poller (izin keluar dan keluhan), 150 penghuni × 2 × 4/menit = **1.200 permintaan/menit (20/detik)** bila semuanya di halaman itu. Tiap permintaan juga menyentuh sesi di DB | `frontend/app/components/leave-permit/LeavePermitSection.vue:11`, `complaint/ComplaintSection.vue:10`; `.env.example:30` `SESSION_DRIVER=database`; polling hanya saat tab terlihat (`usePolling.ts:13`) | Cukup untuk target; bila perlu, naikkan interval, polling hanya satu section aktif, atau pindahkan sesi ke Redis/file |
| 4 | Rendah | `/api/residents` tidak dipaginasi dan mengabaikan `per_page`: selalu mengembalikan semua penghuni (15,8 KB untuk 156) | `ResidentController.php` (`index`: `->get()`); ukuran respons sama untuk `per_page=10` dan `100`; tidak dipolling | Aman untuk ±156; paginasi bila daftar tumbuh (keputusan no. 18 menyebut kebutuhan paginasi daftar profil) |
| 5 | Rendah | Kolom `sort`/`search` tidak punya indeks pendukung; `LIKE %q%` memang tidak memakai indeks B-tree. Hanya ada indeks `(user_id, status)` pada tabel tiga fitur, `rooms.number` unik, `rooms.floor` | Daftar sort/search di bawah; migrasi `2026_10_13_*`. Terukur 17–37 ms pada 156 baris dengan `per_page=100` | Tidak mendesak; evaluasi indeks (`created_at`, `leaves_at`, `served_at`) bila baris mencapai puluhan ribu |
| 6 | Rendah | Pembacaan sesi/pengguna di DB tidak terhitung pada pengukuran: tes memakai `actingAs` (sesi `array`), sedangkan produksi menambah sekitar 2–3 query per permintaan (baca dan perbarui `sessions`, ambil user) | `config/session.php:21` (`database`); `.env.example:30` | Ukur ulang lewat HTTP nyata bila beban dipertanyakan |

## Endpoint: query, waktu, polling

Waktu = satu permintaan setelah pemanasan, tanpa jaringan dan tanpa proxy Nuxt. Peran admin untuk `per_page` dua kolom; baris `resident` untuk peran penghuni.

| Endpoint | Jumlah query (data seed) | Waktu (ms) per_page 10 / 100 | Dipolling? | Perkiraan permintaan/menit saat 150 pengguna |
|---|---|---|---|---|
| `GET /api/session` | 0 | 2 / — | Tidak | Sekali per muat halaman |
| `GET /api/dashboard` (admin) | 3 | 4 / — | Tidak | Sekali per muat halaman admin |
| `GET /api/rooms` (admin) | 5 (konstan) | 20 / 30 | **Ya** (`pages/admin/rooms.vue:9`) | 4 per tab admin; admin sedikit → puluhan |
| `GET /api/residents` (admin) | 2 | 32 / 14 | Tidak | Hanya saat halaman kamar dibuka atau profil disimpan |
| `GET /api/leave-permits` (admin) | 3 | 9 / 23 | **Ya** (kedua peran) | 4 per tab |
| `GET /api/leave-permits` (resident) | 3 | 6 / 7 | **Ya** | 4 per tab → 600/menit bila 150 di `/resident/room` |
| `GET /api/complaints` (admin) | 3 | 8 / 21 | **Ya** | 4 per tab |
| `GET /api/complaints` (resident) | 3 | 7 / 7 | **Ya** | 4 per tab → 600/menit bila 150 di `/resident/room` |
| `GET /api/laundry-orders` (admin) | 3 | 7 / 17 | **Ya** (`LaundrySection`) | 4 per tab |
| `GET /api/laundry-orders` (resident) | 3 | 7 / 7 | **Ya** | 4 per tab → 600/menit bila 150 di `/resident/laundry` |
| `GET /api/catering-menus` (admin) | 2 | 7 / 13 | **Ya** (`CateringSection`) | 4 per tab |
| `GET /api/catering-menus` (resident) | 2 | 11 / 15 | **Ya** | 4 per tab → 600/menit bila 150 di `/resident/catering` |
| `GET /api/my-room` (resident) | 3 | 5 / — | Tidak | Sekali per muat halaman |
| `GET /api/rooms?sort=status` / `sort=resident` (admin, `per_page=100`) | 5 / 5 | 34 / 37 | (varian sort) | — |
| `POST /api/login` | — (bukan `GET`) | ±360 ms CPU bcrypt | Tidak | Lonjakan hingga 150 dalam hitungan menit |

Total terburuk: 150 penghuni di `/resident/room` ≈ 1.200 permintaan/menit (2 poller × 4 × 150). Satu penghuni hanya berada di satu halaman, jadi beban halaman lain tidak dijumlahkan.

Kolom `per_page` mendata ukuran respons: `/api/rooms` 3,7 KB → 19,7 KB; `/api/complaints` (admin) 5,0 KB → 36,8 KB; semua yang lain di bawah 24 KB.

Poller per halaman (`grep -rn "usePolling" frontend/app`): `/admin/rooms` 3 (kamar, izin, keluhan); `/admin/laundry` 1; `/admin/catering` 1; `/resident/room` 2 (izin, keluhan); `/resident/laundry` 1; `/resident/catering` 1. `/admin` (dashboard), `/resident` (beranda) tidak mempolling.

## Indeks untuk kolom sort dan search

| Controller | `search` | `sort` | Indeks yang ada |
|---|---|---|---|
| `RoomController` | `number`, `floor` | `number`, `floor`, `assigned_count`, `status`, `resident` | `rooms.number` unik, `rooms.floor`; sisanya agregat/join |
| `LeavePermitController` | `reason`, `user.name` | `leaves_at`, `returns_at`, `reason`, `user_name` | `(user_id, status)` |
| `ComplaintController` | `title`, `body`, `user.name` | `title`, `body`, `created_at`, `resolved_at`, `user_name` | `(user_id, status)` |
| `LaundryOrderController` | `items`, `user.name` | `items`, `set_count`, `created_at`, `cost`, `user_name` | `(user_id, status)` |
| `CateringMenuController` | `menu_number`, `dish_name`, `provider` | `menu_number`, `dish_name`, `provider`, `served_at`, `rating_average`, `rating_count`, `my_rating` | tidak ada selain PK |

## Dugaan (belum terbukti)

- Polling cenderung terkelompok: 100–150 penghuni yang login hampir bersamaan memulai `setInterval` pada detik yang berdekatan, sehingga permintaan datang dalam gelombang tiap 15 detik, bukan merata. Belum diukur.
- Pada server satu worker (`php artisan serve`), antrean login dapat membuat permintaan polling pengguna lain tertahan selama puncak login. Belum diuji karena uji beban terhadap server tidak dijalankan.
- Waktu respons nyata akan lebih tinggi dari angka di atas (proxy Nuxt, sesi di DB, jaringan, beban paralel).

## Perintah yang dijalankan

- Tes sementara `tests/Feature/TmpQueryCountTest.php` (1 tes, 56 assertion) dengan `RoomSeeder`, 156 penghuni teralokasi, 156 pesanan laundry/izin/keluhan, 30 menu, 600 rating; dijalankan via `php artisan test tests/Feature/TmpQueryCountTest.php`; berkas **sudah dihapus** (`git status` tidak menampilkan `Tmp*`)
- `php -r` pengukuran `password_verify` rata-rata 5 kali untuk cost 10 dan 12 → 87 ms dan 360 ms (PHP 8.3.30, 12 core)
- `grep -rn "usePolling" frontend/app`; pembacaan `routes/api.php`, `RoomController.php`, `ResidentController.php`, `usePolling.ts`, `.env.example`, `config/hashing.php`
- Uji beban lokal: **tidak dijalankan**
