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
