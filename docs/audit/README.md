# Status Tindak Lanjut Audit

Diperbarui: 2026-10-15. Merangkum enam laporan di folder ini dan apa yang sudah dikerjakan.

Arti status: **Selesai** = diperbaiki dan teruji; **Dokumen** = diselesaikan lewat dokumentasi/daftar periksa; **Keputusan pemilik** = tidak bisa diputuskan oleh pengembang; **Tidak dikerjakan** = sengaja ditunda, alasan dicatat.

## 19 — Keputusan stakeholder

| Temuan | Status | Catatan |
|---|---|---|
| 31 item "Tidak ada", 1 "Sebagian" (kamar nonaktif, enum `Religion`, penempatan manual, impor Excel, batal izin/laundry, balasan keluhan, ubah sandi, ekspor, dll.) | **Keputusan pemilik** | Ini fitur baru, bukan perapian, dan 12 di antaranya bertentangan dengan `domain.md` (mis. profil terkunci setelah alokasi vs keputusan no. 7). Pemilik harus lebih dulu memilih mana yang menjadi sumber kebenaran; setelah itu dikerjakan sebagai fase fitur. |
| 7 item "Bertentangan" | **Keputusan pemilik** | Sama; tiap butir menyangkut aturan bisnis yang dilindungi tes. |

## 20 — Dependensi

| Temuan | Status | Catatan |
|---|---|---|
| `laravel/framework` 10.50.3: 4 advisory, tanpa patch di Laravel 10 | **Keputusan pemilik** | Satu-satunya perbaikan adalah upgrade ke ≥ 12.69.0 (dua versi mayor). |
| 14 kerentanan npm (2 critical, 12 high) | **Tidak dikerjakan** | `npm audit fix --dry-run` tidak menyelesaikan apa pun dan menarik `nuxt@4.6.1` yang butuh Node ≥ 24.15.0 (terpasang 24.13.0); `node-forge` dan `braces` belum punya versi perbaikan. Tidak ada paket rentan di `.output`, dan DevTools nonaktif. Pantau ulang setelah Node diperbarui. |

## 21 — Integritas data

| # | Temuan | Status |
|---|---|---|
| 1 | `returns_at > leaves_at` hanya di kode | **Selesai** — CHECK `chk_leave_permits_period` (migrasi `2026_10_15_000001`); izin tumpang tindih tetap belum dijaga (lihat audit 19) |
| 2 | Satu stay aktif per user tidak dijaga DB | **Tidak dikerjakan** — butuh kolom generated; ditunda sampai keputusan no. 9 (alokasi ulang) menentukan alur check-out |
| 3 | Rating 1–5 hanya di kode | **Selesai** — CHECK `chk_catering_ratings_rating` |
| 4 | Kapasitas 2 tidak dijaga DB | **Tidak dikerjakan** — kapasitas akan pindah ke kolom per kamar bila keputusan no. 1 dibangun |
| 5, 6 | Slot yatim `checked_in_at` setelah user dihapus | **Tidak dikerjakan** — dampak nol; aplikasi tidak punya fitur hapus user |
| 7 | Kolom string tanpa CHECK enum | **Tidak dikerjakan** — prioritas rendah selama semua tulis lewat model |
| 8 | `cost` negatif dan `set_count = 0` | **Selesai** — CHECK `chk_laundry_orders_cost`, `chk_laundry_orders_set_count` |
| 9 | `LaundryOrderController::update` tanpa kunci | **Selesai** — transaksi + `lockForUpdate` |

Tes: `tests/Feature/DatabaseIntegrityTest.php` (7 tes).

## 22 — Cakupan tes

| # | Temuan | Status |
|---|---|---|
| 1 | `regenerate` login, `invalidate` logout | **Selesai** — `SessionTest` (2 tes) |
| 2 | Batas `cost` laundry | **Selesai** — `LaundryOrderTest::test_cost_must_be_within_zero_and_ten_billion` |
| 3 | Urutan kunci dan transaksi ulang | **Tidak dikerjakan** — memerlukan tes konkurensi dua proses |
| 4 | Batas panjang `cohort`/`religion` | **Selesai** — `ResidentProfileTest` |
| 5 | Sort menurut rating | **Selesai** — `CateringTest` |
| 6 | Format keluaran waktu ISO UTC | **Selesai** — `LeavePermitTest` |
| 7 | Teks tampilan klien (`Menu n`, `Belum ada rating`, `Belum check-in`) | **Tidak dikerjakan** |
| 8 | Polling berhenti saat aksi berjalan; tombol nonaktif saat sibuk | **Tidak dikerjakan** |
| 9 | `samples.test.ts` bernilai rendah | **Tidak dikerjakan** — tidak berbahaya |
| 10 | Slot ganda tanpa tes sendiri | **Selesai** — `DatabaseIntegrityTest` |

Tambahan: `SessionTest::test_api_responses_are_never_cached` memperluas cakupan `no-store` ke tiga endpoint.

## 23 — Performa

| # | Temuan | Status |
|---|---|---|
| 1 | Login serentak dibatasi CPU bcrypt | **Dokumen** — `CHECKLIST_PRODUKSI.md` mewajibkan PHP-FPM beberapa worker, bukan `artisan serve` |
| 2–6 | `/api/rooms` memuat semua kamar, beban polling, `/api/residents` tanpa paginasi, indeks sort, sesi di DB | **Tidak dikerjakan** — cukup untuk 156 penghuni (2–5 query konstan, ≤ 37 ms); ukur ulang bila skala naik |

## 24 — Ringan

| # | Temuan | Status |
|---|---|---|
| 24a-1 | Paginasi meluber di 375 px | **Selesai** — `flex-wrap`; terukur 375 = 375 px pada empat halaman |
| 24a-2 | Tombol header terpotong | **Selesai** — label tombol tema disembunyikan visual di layar kecil (tetap terbaca pembaca layar); avatar kini di kiri tombol menu |
| 24a-3 | Lompatan `h1` → `h3` di beranda penghuni | **Selesai** — kartu layanan memakai `h2` |
| 24a-4 | Tabel terpotong tanpa petunjuk gulir | **Tidak dikerjakan** |
| 24a-5 | Tidak ada tautan lewati konten | **Selesai** — "Lewati ke konten utama" di `layouts/default.vue` |
| 24b-1 | Nilai query SQL tercatat di log | **Dokumen** — checklist: batasi izin berkas log; penyaringan pesan `QueryException` belum dibuat |
| 24b-2 | Log satu berkas tanpa rotasi | **Selesai** — `.env.example` memakai `LOG_STACK=daily`; checklist menyarankan `LOG_LEVEL=warning` |
| 24b-3 | 404/405 berbahasa Inggris dan menyebut rute | **Selesai** — `Handler::register()`; teruji |
| 24c-1 | Tidak ada petunjuk deploy | **Selesai** (dokumen) — bagian "Penerapan" di checklist |
| 24c-2 | Tidak ada petunjuk backup | **Selesai** (dokumen) — bagian "Backup dan pemulihan" |
| 24c-3 | Checklist belum lengkap | **Selesai** — migrasi, cache, kredensial DB, izin `storage/`, log |

## Yang menunggu keputusan pemilik

1. Sumber kebenaran untuk 12 butir yang bertentangan antara `domain.md` dan keputusan stakeholder (audit 19), lalu fase fitur.
2. Upgrade Laravel 10 → 12 (audit 20).
3. Merge `rewrite` ke `main` dan push ke `origin`.
