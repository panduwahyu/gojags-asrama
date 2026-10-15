# Audit Ringan: Aksesibilitas, Penanganan Galat, Kesiapan Deploy

Tanggal: 2026-10-15 · Commit yang diaudit: 7510de3

## Ringkasan

Tiga pemeriksaan ringan. **Tidak ada temuan Tinggi.** Aksesibilitas (24a): kontrol formulir, tombol, gambar, dan bahasa dokumen sudah baik; masalah yang ditemukan hanya tampilan 375 px (paginasi meluber 3 px, tombol header terpotong) dan satu lompatan level heading — 5 temuan Rendah. Penanganan galat (24b): respons 500 aman saat `APP_DEBUG=false`; 1 temuan Sedang yang mendekati risiko data (nilai SQL ikut tercatat di log) dan 2 Rendah. Kesiapan deploy (24c): build sukses, tetapi **tidak ada petunjuk deploy maupun backup basis data** di dokumentasi — 2 Sedang, 1 Rendah. Jumlah temuan: Tinggi 0, Sedang 3, Rendah 8.

## 24a. Aksesibilitas dan UI

| # | Tingkat | Temuan | Bukti | Saran perbaikan |
|---|---|---|---|---|
| 1 | Rendah | Pada 375 px halaman bertabel meluber horizontal 3 px (`scrollWidth` 378 > 375) dan tombol "Akhir" pada paginasi terpotong di tepi kartu | Pengukuran Playwright: `/admin/rooms`, `/admin/laundry`, `/resident/room`, `/resident/catering` → 378/375; `/login`, `/admin`, `/resident` → 375/375. Screenshot `screenshots/admin-kamar-375.png`, `screenshots/penghuni-kamar-375.png` | Periksa grup tombol halaman di `components/ui/TablePagination.vue` agar membungkus/mengecil di layar sempit |
| 2 | Rendah | Pada 375 px ikon bulat berwarna biru di sisi kanan header terpotong di bawah tombol menu | Tampak di bagian atas `screenshots/admin-kamar-375.png` dan `screenshots/penghuni-kamar-375.png` (pengamatan visual, tidak diukur) | Periksa lebar kontainer `components/layout/AppHeader.vue` pada layar kecil |
| 3 | Rendah | Lompatan level heading di beranda penghuni: `h1` (DashboardHero) langsung ke `h3` (kartu layanan) tanpa `h2` | `components/dashboard/DashboardHero.vue` (`h1`); `components/dashboard/DashboardServiceCard.vue:35` (`h3`); `pages/resident/index.vue:39-42` | Ubah kartu menjadi `h2` atau beri `h2` pembuka bagian layanan |
| 4 | Rendah | Tabel di layar sempit memotong kolom (mis. "Waktu kembali" pada izin keluar penghuni) tanpa petunjuk bahwa tabel dapat digulir | `screenshots/penghuni-kamar-375.png` | Beri bayangan tepi/petunjuk gulir pada `DataTable.vue` |
| 5 | Rendah | Tidak ada tautan "Lewati ke konten utama" | `grep -rniE "skip\|Lewati ke konten" layouts components` kosong; ada `<main>` di `layouts/default.vue:17` dan `layouts/auth.vue:86` | Tambah tautan lewati pada `layouts/default.vue` |

Yang sudah baik (hasil pemeriksaan statis, bukan uji pembaca layar):

- **Label**: 22 kontrol `input`/`select`/`textarea`; semuanya memiliki `<label for>` yang cocok, `aria-label`, atau berada di dalam `<label>` pembungkus (skrip pemeriksa tidak menemukan kontrol tanpa label). Komponen `ui/FormField.vue` menyeragamkan pola ini.
- **Tombol**: 34 `<button>`; tidak ada tombol tanpa teks yang juga tanpa `aria-label`. Ikon (`layout/AppIcon.vue:48`) `aria-hidden="true"`.
- **Gambar**: 8 `<img>`, semuanya punya `alt` (dua gambar hias berisi `alt=""`).
- **Bahasa dan landmark**: `htmlAttrs: { lang: 'id' }` di `nuxt.config.ts`; `<main>`, `<nav aria-label="Breadcrumb">`, dan `<nav aria-label="Navigasi halaman tabel">` tersedia.
- **Heading**: setiap halaman punya satu `h1` (`PageHeader`, `DashboardHero`, atau `login.vue:45`); bagian memakai `h2` dan formulir `h3`, kecuali temuan #3.
- Kontras warna dan urutan fokus papan ketik **tidak diperiksa**.

Screenshot (375×812, data contoh tiruan): `docs/audit/screenshots/` — `login-375.png`, `admin-beranda-375.png`, `admin-kamar-375.png`, `admin-laundry-375.png`, `penghuni-beranda-375.png`, `penghuni-kamar-375.png`, `penghuni-catering-375.png`.

## 24b. Penanganan galat dan logging

| # | Tingkat | Temuan | Bukti | Saran perbaikan |
|---|---|---|---|---|
| 1 | Sedang | Nilai yang terikat pada query SQL ikut tercatat di `storage/logs/laravel.log` saat terjadi galat basis data, sehingga log dapat memuat email, hash kata sandi, atau data pengguna lain | Probe sementara: query `select … where x = ?` dengan nilai `nilai-sensitif` pada tabel yang tidak ada → `grep -c "nilai-sensitif" storage/logs/laravel.log` = 1 (pesan `QueryException` menyertakan SQL dengan nilainya) | Batasi akses/izin berkas log di server, rotasi (lihat #2), dan pertimbangkan menyaring pesan `QueryException` di `Handler` sebelum dicatat |
| 2 | Rendah | Log memakai satu berkas tanpa rotasi pada level `debug`; berkas tumbuh tanpa batas | `config/logging.php:55-64` (`stack` → `single`, `storage/logs/laravel.log`); `.env.example:19,21` (`LOG_STACK=single`, `LOG_LEVEL=debug`); `laravel.log` lokal 641 KB | Produksi: `LOG_STACK=daily` (bawaan 14 hari, `config/logging.php:72`) dan `LOG_LEVEL=warning` atau `error`; tambahkan ke `docs/CHECKLIST_PRODUKSI.md` |
| 3 | Rendah | Respons 404 dan 405 API berbahasa Inggris dan menyebut rute dan metode yang didukung; klien hanya memetakan 401/403/419/429/422 | Probe: `404` → `The route api/tidak-ada could not be found.`; `405` → `The POST method is not supported for route api/session. Supported methods: GET, HEAD.`; `frontend/app/utils/errors.ts:31-48` | Kembalikan pesan Indonesia generik untuk `NotFoundHttpException`/`MethodNotAllowedHttpException` di `Handler.php` |

Hasil probe (`tests/Feature/TmpErrorProbeTest.php`, sudah dihapus):

| Skenario | Status | Isi respons |
|---|---|---|
| 500, `APP_DEBUG=false`, `RuntimeException` | 500 | `{"message":"Server Error"}` (JSON) |
| 500, `APP_DEBUG=false`, galat SQL | 500 | `{"message":"Server Error"}` |
| 500, `APP_DEBUG=true` (pembanding) | 500 | memuat pesan asli (`RAHASIA-DB-password=hunter2`), `exception`, `file`, `line`, dan `trace` |

Catatan:

- Dengan `APP_DEBUG=false` tidak ada jejak tumpukan atau pesan internal yang bocor, termasuk untuk galat SQL. Dengan `true` semuanya bocor; `docs/CHECKLIST_PRODUKSI.md` sudah mewajibkan `false`.
- Penyaringan kata sandi: `Handler.php:10` (`$dontFlash` memuat `current_password`, `password`, `password_confirmation`) dan `TrimStrings.php:9` (`$except` sama). `Handler.php:12-15` memaksa respons JSON untuk `api/*`.
- Probe menulis entri uji ke `storage/logs/laravel.log` lokal (berkas itu di-ignore git, `storage/logs/.gitignore`).

## 24c. Kesiapan build dan deploy

| # | Tingkat | Temuan | Bukti | Saran perbaikan |
|---|---|---|---|---|
| 1 | Sedang | Tidak ada petunjuk menjalankan Nuxt dan Laravel di server (pengelola proses, reverse proxy, php-fpm, health check). README, `AGENTS.md`, dan `CLAUDE.md` hanya memuat perintah pengembangan; satu-satunya kata "nginx" ada di `docs/CHECKLIST_PRODUKSI.md:9` | `grep -rniE "backup\|pm2\|systemd\|nginx\|supervisor\|php-fpm\|mysqldump" README.md AGENTS.md CLAUDE.md docs/*.md docs/notes` → hanya `CHECKLIST_PRODUKSI.md:9` (di luar dokumen rencana dan audit) | Tambah bagian deploy: Nuxt tetap perlu berjalan (`ssr: false`, tetapi `frontend/server/routes/api/[...path].ts` meneruskan `/api/*`) lewat `node .output/server/index.mjs` di bawah pengelola proses; Laravel lewat php-fpm dengan `public/index.php`; health check `GET /up` (`routes/web.php:5`) |
| 2 | Sedang | Tidak ada petunjuk backup dan pemulihan basis data | `grep -rniE "backup\|mysqldump"` di dokumen yang sama: kosong | Tambah jadwal `mysqldump` harian, retensi, dan uji pemulihan ke daftar periksa |
| 3 | Rendah | `docs/CHECKLIST_PRODUKSI.md` belum memuat: `php artisan migrate --force`, `config:cache`/`route:cache`, kredensial DB (contoh memakai `DB_USERNAME=root`, `DB_PASSWORD=` kosong), izin tulis `storage/` dan `bootstrap/cache`, rotasi log, `NUXT_LARAVEL_BASE_URL` | `.env.example:27-28` (`root`, sandi kosong); `frontend/.env.example:1`; isi `CHECKLIST_PRODUKSI.md` (6 butir) | Lengkapi daftar periksa |

Hasil build:

- `npm --prefix frontend run build` → **sukses** (keluar 0, "Build complete in 48.0s"); `.output` 3,3 MB di disk, total keluaran Nitro 1,91 MB (473 kB gzip); isi `.output`: `nitro.json`, `public/`, `server/`. `.output` di-ignore git (`frontend/.gitignore:3`).
- `.output/server/node_modules` hanya berisi `@babel`, `@vue`, `devalue`, `entities`, `estree-walker`, `hookable`, `nostics`, `source-map-js`, `ufo`, `unhead`, `vue`, `vue-bundle-renderer`; tidak ada paket rentan dari audit 20.

Cocokkan dengan `docs/CHECKLIST_PRODUKSI.md` (dibuat di Tugas 17):

| Butir daftar periksa | Selaras dengan kode? |
|---|---|
| `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` baru | Ya (probe 24b menunjukkan `debug=false` aman) |
| `SESSION_SECURE_COOKIE=true` | Ya (`config/session.php:172` membaca env) |
| Laravel hanya di `127.0.0.1`, `TrustProxies` hanya `127.0.0.1` | Ya (`app/Http/Middleware/TrustProxies.php`) |
| Nginx menimpa `X-Forwarded-For`, `NUXT_TRUST_FORWARDED_FOR=true` | Ya (`nuxt.config.ts` `trustForwardedFor`) |
| Jangan jalankan `AccountSeeder` di produksi | Ya (`DatabaseSeeder.php`, `SeederTest::test_demo_accounts_are_not_seeded_in_production`) |
| Ganti sandi admin pertama | Tidak ada mekanisme ubah sandi di aplikasi (audit 19, keputusan no. 19) |

## Dugaan (belum terbukti)

- Penyebab meluapnya paginasi di 375 px kemungkinan grup tombol "Awal/Sebelumnya/Berikutnya/Akhir" yang tidak membungkus, karena `nav` induknya sudah `flex-wrap` (`TablePagination.vue:21-24`); belum diperiksa lewat DevTools.
- Bila aplikasi dijalankan dengan `php artisan serve` di server, kinerja dan keamanannya tidak memadai untuk produksi; tidak ada dokumen yang melarang atau menyarankannya.
- Tidak ada mekanisme ubah sandi, sehingga butir "ganti sandi admin pertama" hanya dapat dilakukan lewat `php artisan tinker` atau basis data.

## Perintah yang dijalankan

- `grep -rnE "<(input|select|textarea)\b"` dan skrip regex pemeriksa label, tombol tanpa teks, `<img>` tanpa `alt`, struktur heading di `frontend/app`
- Spec sementara `frontend/tests/browser/tmp-mobile.spec.ts` (viewport 375×812, 3 tes, 7 halaman; screenshot ke `docs/audit/screenshots/`); berkas **sudah dihapus**
- Tes sementara `tests/Feature/TmpErrorProbeTest.php` (1 tes, 5 probe respons galat); berkas **sudah dihapus**; `git status` tidak menampilkan `Tmp*`/`tmp-*`
- `npm --prefix frontend run build` → sukses; `du -sh frontend/.output` → 3,3 MB
- `grep` dokumen deploy dan backup; pembacaan `Handler.php`, `TrimStrings.php`, `config/logging.php`, `.env.example`, `docs/CHECKLIST_PRODUKSI.md`
