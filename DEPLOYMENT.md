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

## Integrasi api-gojags

Project API terpisah berada di `../api-gojags`. Jalankan lokal:

```powershell
cd D:\laragon\www\api-gojags
php bin/setup.php
php -S 127.0.0.1:8001 -t public public/index.php
```

Pada backend asrama, isi `API_GOJAGS_URL` dan `API_GOJAGS_TOKEN` (token sama
dengan `.env` API), lalu jalankan `php artisan migrate` dan `php artisan config:clear`.
Konfigurasi lokal sudah diisi. Restart backend bila sebelumnya memakai config cache.
Di admin pilih Kamar > Alokasi Kamar > Sinkronisasi data peserta, pilih batch,
lalu Alokasikan kamar otomatis. Peserta di luar batch tidak dialokasikan.
Sinkronisasi ulang memakai ID sumber, tidak membuat duplikat atau mengubah password.
Peserta yang hilang dari snapshot tidak dihapus dari users/riwayat, tetapi dikeluarkan
dari daftar kandidat batch. Perubahan profil penghuni aktif ditolak agar aturan kamar
terjaga. Konflik email akun lama ditolak dan tidak mengambil alih akun tersebut.

Akun hasil sinkronisasi memiliki password acak. Sediakan password/reset password
melalui pengelolaan akun sebelum dipakai login. API tidak membagikan password.

Untuk produksi, deploy api-gojags sebagai service tersendiri dan gunakan volume
SQLite permanen sesuai README project API. Pada Railway service Laravel,
`API_GOJAGS_URL` harus berisi URL API yang dapat dijangkau backend, bukan localhost.
Tambahkan `API_GOJAGS_TOKEN` pada kedua service. Token tidak ditaruh di Vercel.
Jalankan migrasi baru sebelum menggunakan halaman alokasi. Jangan mengganti APP_KEY.

## QR check-in dan check-out

Jalankan migrasi baru sebelum deploy frontend. Admin membuka Kamar > Alokasi
Kamar, memilih batch aktif, memilih jenis QR (check-in/check-out), lalu Generate
QR angkatan. QR berlaku 15 menit dan QR baru menonaktifkan QR lama untuk batch
dan aksi yang sama. Peserta login lalu membuka Kamar > Check-in/Check-out dan
Scan QR dengan kamera. QR check-out melepaskan slot kamar seperti sebelumnya.

Backend memvalidasi session login/CSRF, token QR yang disimpan sebagai hash,
masa berlaku, status pencabutan, keanggotaan batch aktif, alokasi kamar, dan
status check-in sebelumnya. Token tidak berisi ID peserta dan identitas diambil
dari session login. Endpoint check-in/check-out lama juga wajib token QR.

Kamera ponsel memerlukan HTTPS serta izin kamera di browser. Akses melalui IP
LAN dengan HTTP biasa tidak mendukung kamera. Pemindaian diproses lokal di browser;
backend hanya menerima token QR, bukan rekaman kamera. Kamera berhenti saat QR
terbaca, tombol Tutup kamera ditekan, tab disembunyikan, atau halaman ditinggalkan.
Generate QR tidak memerlukan perubahan pada api-gojags.
