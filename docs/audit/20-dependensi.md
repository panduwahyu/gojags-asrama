# Audit Kerentanan Dependensi

Tanggal: 2026-10-15 · Commit yang diaudit: b205c3f

## Ringkasan

`composer audit` melaporkan **4 advisory pada `laravel/framework`** (terpasang v10.50.3): 1 high, 1 medium, 1 rendah, 1 tanpa tingkat — dua di antaranya adalah advisory yang sama (CRLF injection pada aturan `email`, GHSA-5vg9-5847-vvmq). Tidak ada yang punya perbaikan di Laravel 10; versi minimum yang memperbaiki semuanya adalah **12.69.0** (atau 13.30.0), yaitu **dua versi mayor** di atas `^10.48`. `npm audit` melaporkan **14 kerentanan (2 critical, 12 high)** pada rantai `nuxt`, sama baik dengan maupun tanpa `--omit=dev` karena `nuxt` ada di `dependencies`. Hasil build produksi (`.output`, 3,3 MB) **tidak memuat satu pun** paket rentan npm, sehingga dampak npm terbatas pada mesin pengembang/build. Dua paket npm (`node-forge`, `braces`) **belum punya versi perbaikan** di registri: versi terbaru (1.4.0 dan 3.0.3) masih berada dalam rentang rentan.

## Temuan

| Paket | Versi terpasang | Advisory (ID) | Tingkat | Dependensi langsung/tidak | Dipakai di produksi? | Jalur pemakaian di aplikasi | Versi perbaikan | Perlu upgrade mayor? |
|---|---|---|---|---|---|---|---|---|
| `laravel/framework` | v10.50.3 | PKSA-3r5d-mb8f-1qw9 / GHSA-5vg9-5847-vvmq — CRLF injection pada aturan `email` default | Tinggi | Langsung (`composer.json:10`, `^10.48`) | Ya | Aturan `email` hanya di `app/Http/Requests/LoginRequest.php:17`; `grep -rnE "Mail::\|Mailable\|Notification::\|->notify\(" app routes` kosong, jadi aplikasi tidak mengirim email dan nilai yang divalidasi tidak masuk header email | 12.60.0 (advisory `<12.60.0`); tidak ada rilis 10.x | Ya (10 → ≥12) |
| `laravel/framework` | v10.50.3 | PKSA-mdq4-51ck-6kdq / CVE-2026-48019 — advisory yang sama dengan baris di atas (tanpa tingkat di basis data) | Tidak dinilai | Langsung | Ya | Sama dengan baris di atas | 12.60.0 / 13.10.0 | Ya |
| `laravel/framework` | v10.50.3 | PKSA-m5cs-t1y6-qpcs — Temporary Signed URL Path Confusion | Sedang | Langsung | Ya, tetapi fitur tidak dipakai | Alias middleware `signed` hanya terdaftar di `app/Http/Kernel.php:59`; `grep -rnE "signedRoute\|temporarySignedRoute\|hasValidSignature\|'signed'" app routes` tidak menemukan pemakaian | 12.61.1 / 13.12.0 | Ya |
| `laravel/framework` | v10.50.3 | PKSA-d5tc-s1qs-h781 / CVE-2026-102279 — XSS pada halaman debug | Rendah | Langsung | Hanya bila `APP_DEBUG=true` | `.env.example:4` bernilai `APP_DEBUG=true`; `docs/CHECKLIST_PRODUKSI.md` mewajibkan `false` di produksi. API mengembalikan JSON, bukan HTML | 12.69.0 / 13.30.0 | Ya |
| `@simple-git/argv-parser` | 1.1.1 | npm 1241198 — variabel `VISUAL` lolos dari deteksi editor tidak aman | Kritis | Tidak langsung: `nuxt > @nuxt/devtools > simple-git > @simple-git/argv-parser` | Tidak (tidak ada di `.output`) | `devtools: { enabled: false }` di `frontend/nuxt.config.ts:6`; hanya dimuat oleh Nuxt DevTools saat `nuxt dev` | 2.0.1 (terbit) | Tidak (lewat `simple-git` 4.0.2; `npm audit fix` tersedia) |
| `simple-git` | 3.36.0 | npm 1241199, 1241200, 1242216 — bypass guard operasi tidak aman, eksekusi perintah lewat opsi git | Kritis | Tidak langsung: `nuxt > @nuxt/devtools > simple-git` | Tidak | Sama (DevTools dinonaktifkan; aplikasi tidak memanggil `simple-git`) | 4.0.2 (terbit) | Tidak |
| `@nuxt/devtools` | 3.4.2 | via `simple-git` | Tinggi | Tidak langsung: `nuxt > @nuxt/devtools` | Tidak | Sama | Ikut `simple-git` ≥ 4.0.2 | Tidak |
| `braces` | 3.0.3 | npm 1240992 — DoS stack-exhaustion pada pola bersarang dalam | Tinggi | Tidak langsung: `nuxt > @nuxt/nitro-server > nitropack > globby > micromatch > braces` | Tidak | Dipakai nitropack saat build untuk glob berkas proyek; pola berasal dari konfigurasi proyek, bukan input pengguna | **Belum ada** — versi terbaru di registri 3.0.3 masih rentan | Tidak berlaku |
| `micromatch` | 4.0.8 | via `braces` | Tinggi | Tidak langsung (rantai di atas) | Tidak | Sama | Menunggu `braces` | Tidak berlaku |
| `fast-glob` | 3.3.3 | via `micromatch` | Tinggi | Tidak langsung: `… > nitropack > globby > fast-glob` | Tidak | Sama | Menunggu `braces` | Tidak berlaku |
| `globby` | 16.2.4 | via `fast-glob`, `micromatch` | Tinggi | Tidak langsung: `nuxt > @nuxt/nitro-server > nitropack > globby` | Tidak | Sama | Menunggu `braces` | Tidak berlaku |
| `node-forge` | 1.4.0 | npm 1240912 — verifikasi tanda tangan RSA PKCS#1 v1.5 menerima elemen DigestAlgorithm bersarang tambahan | Tinggi | Tidak langsung: `nuxt > @nuxt/nitro-server > nitropack > listhen > node-forge` | Tidak | Dipakai `listhen` untuk HTTPS/tunnel pada server dev; aplikasi tidak memverifikasi tanda tangan (`grep node-forge frontend/app frontend/server` kosong) | **Belum ada** — versi terbaru 1.4.0 masih rentan (`<=1.4.0`) | Tidak berlaku |
| `listhen` | 1.10.1 | via `node-forge` | Tinggi | Tidak langsung: `… > nitropack > listhen` | Tidak | Sama | Menunggu `node-forge` | Tidak berlaku |
| `nitropack` | 2.13.4 | via `listhen` | Tinggi | Tidak langsung: `nuxt > @nuxt/nitro-server > nitropack` | Build produksi memakainya, tetapi hasil `.output` tidak memuat `nitropack` | Mesin build Nitro; server produksi memakai `.output/server/index.mjs` | Menunggu `node-forge` | Tidak berlaku |
| `@nuxt/nitro-server` | 4.6.0 | via `nitropack`, `nuxt` | Tinggi | Tidak langsung: `nuxt > @nuxt/nitro-server` | Sama | Sama | Menunggu rantai di atas | Tidak berlaku |
| `@nuxt/vite-builder` | 4.6.0 | via `nuxt` | Tinggi | Tidak langsung: `nuxt > @nuxt/vite-builder` | Tidak | Build klien | Menunggu rantai di atas | Tidak berlaku |
| `@nuxt/vite-server` | 4.6.0 | via `nuxt` | Tinggi | Tidak langsung: `nuxt > @nuxt/vite-server` | Tidak | Server dev | Menunggu rantai di atas | Tidak berlaku |
| `nuxt` | 4.6.0 | via `@nuxt/nitro-server`, `@nuxt/vite-builder`, `@nuxt/vite-server` | Tinggi | **Langsung** (`frontend/package.json:20`, `^4.6.0`) | Hanya sebagai alat build | Semua proses `nuxt dev`/`build` | Tidak ada; `npm audit` menyarankan `nuxt@4.1.3` yang merupakan **penurunan versi** (terpasang 4.6.0, terbaru 4.6.1) | Tidak berlaku |

Catatan pembacaan:

- Hanya `nuxt` yang menjadi dependensi langsung. Sisanya muncul sebagai efek rantai; satu akar (`simple-git`) dan dua akar tanpa perbaikan (`node-forge`, `braces`) menyebabkan 14 baris.
- Kolom "Dipakai di produksi?": hasil `npm run build` (exit 0, 3,3 MB) berisi hanya `@babel, @vue, devalue, entities, estree-walker, hookable, nostics, source-map-js, ufo, unhead, vue, vue-bundle-renderer` di `.output/server/node_modules`. `find .output -type d -name <paket>` untuk `node-forge simple-git braces micromatch fast-glob globby listhen nitropack` tidak menemukan apa pun.
- `npm audit fix --force` akan **menurunkan** `nuxt` ke 4.1.3 (dilabeli perubahan mayor oleh npm). Itu bukan perbaikan.

## Dugaan (belum terbukti)

- `GET /up` dan halaman 500 Laravel mungkin membuka halaman debug bila `APP_DEBUG=true` terbawa ke produksi; ini belum diuji (lihat Tugas 24b).
- Versi perbaikan per advisory di atas diambil dari rentang "Affected versions" pada keluaran `composer audit`; patch pertama di setiap cabang (12.x, 13.x) belum diverifikasi lewat Packagist.

## Opsi untuk pemilik

**(a) Upgrade Laravel 10 → 12 (minimum 12.69.0) atau 13 (minimum 13.30.0).** Memperbaiki keempat advisory. Berkas yang kemungkinan terdampak: `composer.json` (`laravel/framework`, PHP minimum, `phpunit`/`pint`/`collision`), `composer.lock`, `bootstrap/app.php` dan struktur `app/Http/Kernel.php`, `app/Exceptions/Handler.php`, `app/Http/Middleware/*` (`TrustProxies`, `Authenticate`, `RequireRole`, `TrimStrings`), `app/Providers/RouteServiceProvider.php`, `config/*.php`, serta tes di `tests/` yang bergantung pada perilaku framework. Laravel 11 mengubah struktur bootstrap/middleware secara besar; lompatan dua mayor sebaiknya dilakukan bertahap (10 → 11 → 12) dengan `php artisan test` hijau di tiap tahap. Biaya: sedang–tinggi.

**(b) Upgrade paket npm.** `npm audit` menyatakan rantai `simple-git`/`@simple-git/argv-parser`/`@nuxt/devtools` dapat diperbaiki dengan `npm audit fix` tanpa `--force` (versi terbit: `simple-git` 4.0.2, `@simple-git/argv-parser` 2.0.1), yang menghilangkan 2 temuan kritis. Hasil perbaikan itu belum dicoba. Berkas terdampak: `frontend/package-lock.json` saja. **Jangan** `npm audit fix --force` (menurunkan `nuxt`). `node-forge` dan `braces` menunggu rilis perbaikan upstream; pantau lagi bila `listhen`/`nitropack`/`micromatch` terbit versi baru. Biaya: rendah.

**(c) Menerima risiko dengan alasan.** Untuk npm: seluruh paket rentan tidak ada di artefak produksi dan DevTools nonaktif; risiko terbatas pada mesin pengembang. Untuk Laravel: aplikasi tidak mengirim email, tidak memakai signed URL, dan `APP_DEBUG=false` diwajibkan di `docs/CHECKLIST_PRODUKSI.md`; namun Laravel 10 tidak lagi menerima patch keamanan, sehingga setiap advisory baru tidak akan punya perbaikan. Alasan penerimaan harus dicatat tertulis bila dipilih, misalnya untuk periode skripsi/demo saja.

## Perintah yang dijalankan

- `composer audit --format=plain` → 4 advisory pada `laravel/framework` (low 1, medium 1, high 1, tanpa tingkat 1)
- `composer audit --format=json` → disimpan di scratchpad (di luar repositori)
- `composer show laravel/framework` → `v10.50.3`, rilis 2026-08-12
- `npm --prefix frontend audit --omit=dev` → 14 kerentanan (12 high, 2 critical)
- `npm --prefix frontend audit` (termasuk dev) → hasil identik, 14 kerentanan
- `npm --prefix frontend ls nuxt nitropack listhen node-forge simple-git braces @simple-git/argv-parser fast-glob` → rantai seperti pada tabel
- `npm view <paket> version` untuk `node-forge` (1.4.0), `braces` (3.0.3), `simple-git` (4.0.2), `@simple-git/argv-parser` (2.0.1), `listhen` (1.10.1), `nitropack` (2.13.4), `nuxt` (4.6.1), `micromatch` (4.0.8)
- `npm --prefix frontend run build` → sukses, `.output` 3,3 MB
- `grep` pemakaian `email`, `Mail::`, signed URL, `node-forge` di `app routes frontend/app frontend/server`
