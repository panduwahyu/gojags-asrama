# Deploy backend ke Railway

Frontend Nuxt (`frontend/`) dideploy terpisah ke Vercel. Service Railway
menggunakan root repository, `railway.json`, dan server PHP bawaan Railpack.

1. Hubungkan repository GitHub ke service Railway dan tambahkan service MySQL.
2. Salin `.env.railway.example` ke Variables service Laravel. Sesuaikan nama
   referensi MySQL, isi `APP_KEY`, dan isi `APP_URL` setelah Generate Domain.
   Template ini tidak berisi kredensial; jangan commit nilai production.
3. Untuk aplikasi baru, buat key dengan `php artisan key:generate --show`.
   Pertahankan key lama jika membawa data terenkripsi dari instalasi lama.
4. Deploy. Build aset menggunakan `npm run build`; migrasi berjalan melalui
   pre-deploy. Jangan mengganti start command bawaan Railpack.
5. Buka `/up` dan pastikan respons `{"status":"ok"}`.
6. Pada Vercel, root directory adalah `frontend` dan variable
   `NUXT_LARAVEL_BASE_URL` berisi URL HTTPS backend. Redeploy frontend lalu
   periksa login, logout, dan pembuatan data laundry.

Migrasi tidak menyalin akun/data lokal. Impor database lama atau buat akun
production secara terpisah. Jangan menjalankan seeder akun demo di production:
seeder tersebut berisi password contoh dan dapat memperbarui akun yang ada.

File upload membutuhkan volume atau object storage bila fitur upload ditambahkan;
filesystem deployment bukan penyimpanan permanen.

Referensi:
- https://docs.railway.com/guides/laravel
- https://docs.railway.com/config-as-code/reference
- https://railpack.com/languages/php
