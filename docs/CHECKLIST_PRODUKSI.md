# Daftar Periksa Produksi

Sebelum aplikasi dipasang di server:

- [ ] `.env` Laravel: `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` baru (`php artisan key:generate`).
- [ ] `.env` Laravel: `SESSION_SECURE_COOKIE=true` (situs diakses lewat HTTPS).
- [ ] Laravel hanya mendengarkan `127.0.0.1` dan tidak dapat diakses langsung dari luar; semua akses
      lewat Nuxt. `TrustProxies` hanya memercayai `127.0.0.1`.
- [ ] Bila Nuxt berada di belakang reverse proxy (nginx), proxy itu **menimpa** header
      (`proxy_set_header X-Forwarded-For $remote_addr;`) dan `NUXT_TRUST_FORWARDED_FOR=true`.
      Tanpa ini, rate limit login melihat semua pengguna sebagai satu IP.
- [ ] Jangan menjalankan `AccountSeeder` (akun demo `admin123`/`user123`). `DatabaseSeeder` sudah
      melewatinya di luar lingkungan `local`, `testing`, `e2e` — pastikan `APP_ENV=production`.
- [ ] Ganti sandi akun admin pertama setelah dibuat.

## Penerapan

- [ ] Basis data: buat pengguna khusus dengan sandi (jangan `root` tanpa sandi seperti contoh di `.env.example`),
      lalu `php artisan migrate --force` (termasuk CHECK constraint `2026_10_15_000001`; butuh MySQL ≥ 8.0.16 / MariaDB ≥ 10.2).
- [ ] Jalankan `php artisan config:cache` dan `php artisan route:cache`; `storage/` dan `bootstrap/cache/` dapat ditulis server web.
- [ ] Laravel dilayani PHP-FPM (`public/index.php`) dengan beberapa worker, **bukan** `php artisan serve`. Login memakai bcrypt
      (±360 ms CPU per verifikasi): untuk 100–150 login serentak, jumlah worker ≈ jumlah core (lihat `docs/audit/23-performa.md`).
- [ ] Nuxt dijalankan dengan `npm --prefix frontend run build` lalu `node frontend/.output/server/index.mjs` di bawah pengelola proses
      (systemd/pm2). Walau `ssr: false`, server Nitro tetap dibutuhkan untuk meneruskan `/api/*` ke Laravel.
      Atur `NUXT_LARAVEL_BASE_URL` ke alamat Laravel. Node yang dipakai harus memenuhi syarat engine Nuxt.
- [ ] Pemantauan: `GET /up` (Laravel) mengembalikan `{"status":"ok"}`.
- [ ] Log: `LOG_STACK=daily` dan `LOG_LEVEL=warning` (atau `error`). Berkas log dapat memuat nilai query saat galat basis data;
      batasi izin bacanya hanya untuk akun layanan.

## Backup dan pemulihan

- [ ] Jadwalkan `mysqldump` harian ke penyimpanan terpisah (simpan minimal 14 hari), mis. cron:
      `mysqldump --single-transaction -u <user> -p<sandi> <basis_data> | gzip > /backup/asrama-$(date +%F).sql.gz`.
- [ ] Uji pemulihan ke basis data kosong sekurang-kurangnya sekali sebelum produksi.
