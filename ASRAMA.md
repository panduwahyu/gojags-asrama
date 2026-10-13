# Gojags Asrama — Laravel + Nuxt + Tailwind CSS

Antarmuka Laravel dengan logo Gojags, font Inter, dan palet oranye yang mengacu pada https://gojags-classroom.bps.go.id/.

Backend menggunakan **Laravel 10** (`composer.json` dan `composer.lock`). Frontend menggunakan Nuxt 4 (Vue 3) dan Tailwind CSS 4 di folder `frontend/`. Struktur bootstrap, HTTP/Console Kernel, middleware, route provider, dan pengujian telah disesuaikan untuk Laravel 10. Database dan API Asrama dipertahankan.

Tombol **Mode gelap / Mode terang** tersedia di bar atas Nuxt dan Blade. Palet malam mengikuti permukaan slate gelap dan aksen oranye Gojags Classroom. Secara default tema mengikuti preferensi sistem dan berubah otomatis saat preferensi sistem berubah, sampai pengguna memilih tema secara manual; pilihan manual disimpan dalam localStorage (`gojags-asrama-theme`) dan disinkronkan antar-tab pada origin yang sama. Tema diterapkan dari skrip head sebelum aplikasi dimuat. Sumber skrip tema di `public/js/asrama-theme.js` disalin ke `frontend/public/js/asrama-theme.js`; samakan kedua file jika perilaku tema diubah.

Laravel 10 telah melewati masa dukungan keamanan resmi sejak 4 Februari 2025; lihat [kebijakan dukungan Laravel](https://laravel.com/docs/10.x/releases#support-policy).

## Menjalankan Nuxt dan Laravel

Gunakan PHP 8.2+ dan Node.js 22.22.3+ atau 24.15.0+ sesuai persyaratan Nuxt. Node.js 22.12 yang semula terpasang perlu diperbarui.

```bash
composer install
php artisan migrate
php artisan db:seed --class=AsramaAccountSeeder
npm --prefix frontend ci
```

Terminal pertama:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Terminal kedua:

```bash
npm --prefix frontend run dev
```

Buka **http://127.0.0.1:3000** untuk frontend Nuxt. Port aktual ditampilkan oleh Nuxt jika 3000 sedang digunakan. Gunakan hostname yang konsisten; jangan berganti antara `localhost` dan `127.0.0.1` saat login.

Pada workspace Windows ini, Node 24 lokal tersedia di `.tools/` untuk verifikasi tanpa mengubah Node sistem. Anda dapat menjalankan `powershell -ExecutionPolicy Bypass -File frontend/run.ps1 dev`; skrip memilih Node lokal jika tersedia. Folder `.tools/` tidak masuk Git. Pada mesin lain, pasang Node sesuai versi di atas. Skrip yang sama mendukung `build`, `typecheck`, dan `preview`.

Jika dependensi npm root sudah terpasang (`npm install`), keduanya juga dapat dijalankan dengan `npm run dev:asrama`. Script Vite root tetap tersedia untuk halaman Blade lama.

Jika alamat backend berbeda, salin `frontend/.env.example` ke `frontend/.env`, lalu ubah `NUXT_LARAVEL_BASE_URL`. Variabel ini hanya digunakan di server Nuxt. Untuk pengembangan HTTP lokal, pastikan `SESSION_DOMAIN` kosong dan `SESSION_SECURE_COOKIE=false` pada `.env` Laravel.

## Cara integrasi bekerja

- Nuxt menangani navigasi Beranda, Kamar, Laundry, Catering, dan Login.
- Tailwind dikompilasi melalui plugin Vite `@tailwindcss/vite`.
- Laravel menyediakan JSON pada `/api/asrama/*`, dengan validasi, database, pembatasan percobaan login, session, dan perlindungan CSRF.
- Browser mengakses `/backend/api/asrama/*` di origin Nuxt. Server Nuxt meneruskan request dan cookie ke Laravel, sehingga konfigurasi CORS lintas origin tidak diperlukan.
- Token CSRF diambil dari `/api/asrama/session`, lalu dikirim melalui header `X-CSRF-TOKEN` untuk setiap mutasi. Tidak ada password atau token autentikasi yang disimpan di localStorage.
- Frontend menggunakan mode SPA (`ssr: false`); backend Node Nuxt tetap diperlukan untuk proxy. Jangan menggunakan hosting statis saja untuk konfigurasi ini.

## Build produksi

```bash
npm --prefix frontend run typecheck
npm --prefix frontend run build
```

Jalankan `node frontend/.output/server/index.mjs` dengan `NUXT_LARAVEL_BASE_URL` mengarah ke backend Laravel yang dapat dijangkau server Nuxt. Atur variabel lingkungan pada proses produksi, bukan hanya file `.env`. Laravel dilayani web server PHP dengan document root `public/`; Nuxt dilayani proses Node terpisah. Untuk HTTPS produksi, gunakan cookie secure dan session domain sesuai hostname publik Nuxt (atau kosong untuk host-only). Proxy `/backend` tidak boleh dicache.

Konfigurasi Nitro menyertakan renderer Nuxt ke bundle untuk memastikan modul manifest produksi teresolusi di Windows. Audit npm saat integrasi masih melaporkan advisori pada dependensi transitif Nuxt; tinjau pembaruan upstream sebelum deployment produksi.

## Menjalankan antarmuka Blade lama

1. Siapkan `.env` dan koneksi database Laravel.
2. Jalankan `php artisan migrate`.
3. Buat akun pengelola dengan `php artisan asrama:admin`. Kata sandi dimasukkan secara tersembunyi dan disimpan sebagai hash.
4. Jalankan `php artisan serve`, atau buka virtual host Laragon yang mengarah ke direktori `public`.

Halaman awal `/` mengarah ke login tanpa sidebar dan footer. Setelah login, admin diarahkan ke `/admin`, sedangkan user diarahkan ke `/user`. Admin dapat melihat ringkasan dan mencari data. Laundry menampilkan pengajuan user beserta biaya dan status; Catering menyediakan jadwal menu dan hasil rating. Kamar menggunakan inventaris tetap dan alokasi penghuni otomatis. Beranda user menampilkan kartu sambutan dengan namanya. Sidebar user berisi Beranda, Kamar, Laundry, dan Catering; halaman Laundry menyediakan form pengajuan, sedangkan Catering menampilkan jadwal menu dan form rating. Halaman Kamar menampilkan alokasi milik user, aksi check-in/check-out, dan riwayat waktunya. Sidebar akun yang sudah login hanya memiliki tombol Logout pada bagian bawah, tanpa kartu sambutan di atasnya.

Middleware Laravel `auth` dan `role` menjaga halaman Blade maupun API. User menerima HTTP 403 saat meminta halaman atau API admin, dan admin menerima HTTP 403 saat meminta halaman atau API khusus user. Nuxt memeriksa sesi sebelum navigasi dan mengembalikan pengguna ke beranda rolenya jika membuka halaman role lain. Route lama `/pengumuman` dan `/checkin` juga khusus admin.

Seeder `AsramaAccountSeeder` membuat atau memperbarui dua akun berikut, dengan kata sandi disimpan sebagai hash:

| Role | Nama | Email | Kata sandi awal |
| --- | --- | --- | --- |
| Admin | Admin Gojags | admin@example.id | admin123 |
| User | Pandu Wahyu Aji | user@example.id | user123 |

Seeder hanya menyentuh kedua email tersebut dan tidak menghapus akun atau data lain. Menjalankan ulang seeder mengembalikan nama, role, dan kata sandi kedua akun ke nilai di atas. `php artisan asrama:admin` tetap tersedia untuk membuat admin tambahan secara interaktif. Akun lama mendapatkan role `user` sebagai default saat migrasi.

Pencarian dan paginasi tersedia pada setiap halaman layanan tanpa memuat ulang halaman. Navigasi mencakup Awal, Sebelumnya, indikator halaman sekarang/total halaman, Berikutnya, dan Akhir. Pilihan jumlah baris adalah 10 (default), 50, atau 100; mengubah jumlah baris mengembalikan tabel ke halaman pertama dan mempertahankan pencarian. API menerima parameter `per_page` dengan ketiga nilai tersebut. Pencarian berjalan otomatis 300 ms setelah mengetik, serta mendukung tombol Cari dan Reset. Nuxt mengambil data JSON, sedangkan Blade memperbarui hanya hasil tabel dan pagination melalui fetch, mempertahankan isian formulir dan mendukung navigasi Back/Forward browser. Tabel contoh pada halaman Pengumuman dan Check-in menggunakan pencarian serta pagination lokal, pilihan 10, 50, atau 100 baris per halaman (default 10). CSS dan logo tersedia lokal tanpa proses build; font Inter menggunakan Google Fonts dengan fallback Arial. Route lama `/pengumuman` dan `/checkin` tetap tersedia.

Versi awal mencakup pencatatan dasar, bukan alokasi penghuni, pembayaran, pemesanan mandiri, atau integrasi SSO BPS.

Sepuluh akun tambahan dibuat oleh `AsramaUserSeeder` dan ikut dipanggil oleh `DatabaseSeeder`. Untuk membuat hanya akun tambahan, jalankan `php artisan db:seed --class=AsramaUserSeeder`. Seeder melewati email yang sudah ada sehingga tidak mengubah password, profil, atau alokasi akun lama.

| Nama | Email | Gender | Angkatan | Kata sandi awal |
| --- | --- | --- | --- | --- |
| User 1-5 | user1@example.id sampai user5@example.id | Laki-laki | 64 | user123 |
| User 6-10 | user6@example.id sampai user10@example.id | Perempuan | 63 | user123 |

Seluruh akun tambahan memiliki role `user`. Agama awal kosong dan perlu dilengkapi admin sebelum mengikuti alokasi kamar otomatis.

## Tabel layanan

Data layanan disimpan di tiga tabel terpisah: `kamar`, `laundry`, dan `catering`. Laundry dan Catering memiliki kolom `id`, `name`, `detail`, `status`, `date`, `created_at`, dan `updated_at`. Kamar memiliki kolom `lantai` dan tidak lagi memiliki `detail`. ID setiap layanan independen.

Migrasi `2026_10_09_000002_split_asrama_service_tables` memindahkan data dari `asrama_records` berdasarkan kategori, mempertahankan ID dan timestamp, memeriksa jumlah hasil pemindahan, lalu menghapus tabel gabungan. Terapkan pada instalasi lain dengan `php artisan migrate`; jangan menggunakan `migrate:fresh` untuk database berisi data. Jika kategori lama tidak dikenal, migrasi dihentikan sebelum membuat tabel baru.

API daftar tetap memakai `/api/asrama/{category}`. Tambah data dan pembaruan status manual hanya berlaku untuk Laundry dan Catering. Pembaruan status menggunakan `PATCH /api/asrama/{category}/{id}`, misalnya `/api/asrama/laundry/1`. Form Blade menggunakan `PATCH /{category}/{id}`. Kategori hanya menerima `kamar`, `laundry`, atau `catering`, dan akses tetap khusus admin. Saat rollback, data digabungkan kembali; ID yang bertabrakan antar layanan mendapat ID baru agar seluruh catatan tetap tersimpan.

Klik setiap header tabel untuk mengurutkan naik, lalu klik lagi untuk urutan turun. Indikator panah dan `aria-sort` menandai urutan aktif. Sorting pada Kamar, Laundry, dan Catering dilakukan backend sebelum pagination; kolom dan arah divalidasi dengan daftar yang diperbolehkan. Sorting mengembalikan tabel ke halaman pertama dan mempertahankan pencarian serta jumlah baris. Penghuni ditampilkan satu nama per baris, diurutkan menurut nama. Sorting Penghuni memakai nama penghuni pertama secara alfabetis; sorting Status kamar memakai urutan Kosong, Assigned, Terisi, Penuh. Tabel contoh Pengumuman dan Check-in diurutkan lokal, termasuk pengenalan tanggal dan nilai numerik.

## Kamar dan alokasi penghuni

Migrasi `2026_10_09_000003_prepare_room_allocation` menghapus inventaris kamar lama sesuai permintaan dan mengisinya dengan 78 kamar: 301-326, 401-426, dan 501-526. Masing-masing lantai memiliki 26 kamar. Semua kamar awal berstatus **Kosong**. Migrasi ini dijalankan sekali melalui `php artisan migrate`; rollback mengembalikan struktur kolom, tetapi tidak memulihkan dua kamar lama yang sudah dihapus.

Setiap kamar memiliki dua slot dalam `kamar_slots`. Relasi `user_id` unik mencegah satu user mendapatkan lebih dari satu slot. Hanya akun ber-role `user` yang mengikuti alokasi; admin tidak dialokasikan. Data `angkatan`, `gender`, dan `agama` pada user diisi admin melalui **Kamar > Profil user untuk alokasi**, lalu disimpan per user sebelum menekan **Alokasikan kamar otomatis**. Data profil awal tidak ditebak, termasuk profil Pandu Wahyu Aji.

Aturan alokasi:

1. Angkatan diproses menurut urutan nama/nomor secara natural, lalu urutan ID user. Untuk angkatan baru, pilih lantai tertinggi yang belum ditempati agar satu angkatan diprioritaskan pada satu lantai. Urutan lantai **5 -> 4 -> 3**. Angkatan yang sudah memiliki penghuni mulai dari lantai tertinggi yang telah ditempatinya. Jika semua lantai sudah digunakan, pilih lantai tertinggi yang masih memiliki kapasitas.
2. Dalam setiap lantai, isi kamar berpenghuni yang kompatibel terlebih dahulu, dengan prioritas teman sekamar satu angkatan, kemudian gunakan kamar kosong. Gender dan agama wajib sama dalam satu kamar, termasuk ketika terjadi limpahan angkatan ke lantai lebih rendah.
3. Jika lantai penuh atau tidak memiliki kamar yang kompatibel, lanjut ke lantai di bawahnya. Jika tidak tersedia kamar sesuai aturan, user masuk daftar menunggu. Profil belum lengkap dilaporkan terpisah.
4. Status otomatis: **Kosong** = tidak ada penempatan, **Assigned** = masih ada penghuni yang ditempatkan tetapi belum check-in, **Terisi** = satu penghuni yang tersisa sudah check-in, **Penuh** = dua penghuni sudah check-in. Status tidak dapat diubah manual. Halaman kamar menampilkan lantai, nama penghuni, kapasitas, dan status.

Alokasi berjalan dalam transaksi dengan penguncian kamar, user, dan slot agar request bersamaan tidak menggandakan penempatan. Menjalankan ulang hanya memproses user yang belum mendapat kamar dan tidak memindahkan penghuni lama. Profil penghuni yang sudah dialokasikan dikunci agar aturan teman sekamar tetap konsisten.

Endpoint khusus admin: `POST /api/asrama/kamar/allocate` untuk alokasi dan `PATCH /api/asrama/allocation-users/{id}` untuk profil (`angkatan`, `gender`: `Laki-laki`/`Perempuan`, `agama`). Versi Blade menggunakan jalur yang sama tanpa awalan `/api/asrama`. Respons alokasi memuat `allocated`, `incomplete`, dan `waiting` beserta pesan jumlah hasil. Angkatan dan agama dinormalisasi huruf kecil serta spasi agar variasi penulisan tidak memecah kelompok.

## Check-in, check-out, dan monitoring

Migrasi `2026_10_09_000004_add_room_check_ins` menambahkan `checked_in_at` pada `kamar_slots` serta tabel riwayat `kamar_stays`. Seluruh penempatan lama dipertahankan dan menjadi Assigned; waktu masuk tidak dibuat secara otomatis. Riwayat menyimpan snapshot nama penghuni, nomor kamar, lantai, dan waktu masuk/keluar. Timestamp berasal dari server, disimpan dalam UTC, dikirim sebagai ISO 8601, dan ditampilkan dalam WIB (Asia/Jakarta).

User mengakses `/user/kamar`, `/user/laundry`, dan `/user/catering`. Laundry menyediakan form pengajuan dan riwayat; Catering menampilkan jadwal menu dan rating. API `GET /api/asrama/user/kamar` hanya mengembalikan kamar dan riwayat user yang sedang login. `POST /api/asrama/user/kamar/check-in` dan `POST /api/asrama/user/kamar/check-out` hanya memakai identitas dari sesi; input ID user/kamar atau waktu dari browser tidak dipakai. Versi Blade memakai jalur tanpa `/api/asrama`. Admin tidak dapat mengakses halaman maupun aksi khusus user.

Check-in memerlukan alokasi aktif. Check-out memerlukan check-in aktif, mencatat waktu keluar, dan langsung melepaskan slot kamar. Aksi berulang atau urutan yang tidak valid ditolak. Transaksi mengunci kamar, user, dan slot dengan urutan yang sama seperti alokasi agar tidak terjadi penempatan atau pencatatan ganda. User yang sudah menyelesaikan check-out tidak dimasukkan kembali oleh alokasi massal berikutnya.

Prioritas status kamar: tanpa penghuni = Kosong; jika ada penempatan yang belum check-in = Assigned; jika semua penghuni aktif sudah check-in, satu orang = Terisi dan dua orang = Penuh. Dengan demikian, setelah dua orang check-in, check-out satu orang menghasilkan Terisi dan check-out keduanya menghasilkan Kosong. Jika salah satu masih menunggu check-in, status tetap Assigned.

Monitoring admin pada halaman Kamar menampilkan jumlah global empat status, terlepas dari pencarian dan pagination. Warna status dan kartu: Kosong hijau, Assigned biru cerah, Terisi kuning, Penuh merah, termasuk pada mode gelap. Data admin diperbarui otomatis setiap 15 detik saat tab aktif; isian profil yang belum disimpan tetap dipertahankan. Nama penghuni juga menampilkan waktu check-in atau keterangan belum check-in.

## Izin keluar

Halaman Kamar user memiliki form waktu keluar, waktu kembali, dan alasan (maksimal 2.000 karakter), serta tabel pengajuan milik akun tersebut. Semua isian tanggal dan jam menggunakan WIB, disimpan sebagai UTC; waktu kembali wajib setelah waktu keluar. Pengajuan baru berstatus **Diajukan**. User dapat mengedit pengajuan miliknya hanya selama status Diajukan. Izin keluar tidak melepaskan alokasi kamar atau mencatat check-out.

Admin menangani seluruh pengajuan pada tabel Izin keluar di halaman Kamar melalui tombol **Setujui** atau **Tolak**. Status akhir **Disetujui** dan **Ditolak** mengunci perubahan, termasuk lewat API. Keputusan mencatat akun admin dan waktu pemrosesan. Transaksi dan penguncian baris mencegah edit user atau keputusan admin bersamaan menimpa pengajuan yang sudah selesai diproses.

Migrasi `2026_10_09_000005_create_izin_keluar_table` membuat tabel tersendiri `izin_keluar`. API user: `GET/POST /api/asrama/user/izin-keluar`, `PATCH /api/asrama/user/izin-keluar/{id}`. API admin: `GET /api/asrama/izin-keluar`, `PATCH /api/asrama/izin-keluar/{id}` dengan status Disetujui/Ditolak. Mutasi Blade memakai jalur tanpa `/api/asrama`. Semua mutasi memerlukan sesi dan CSRF; identitas pemohon berasal dari sesi.

Tabel mendukung pencarian, sorting kolom data, navigasi awal/akhir, dan ukuran 10/50/100 tanpa reload halaman. Nuxt menggunakan pagination API dan memuat ulang status setiap 15 detik ketika tab aktif. Blade menggunakan kontrol tabel lokal. Perubahan status izin tidak mengubah status hunian kamar.

## Pengajuan laundry

User mengajukan laundry melalui `/user/laundry` dengan daftar pakaian/barang (maksimal 255 karakter) dan total jumlah stel (bilangan bulat 1–1.000). Pengajuan memakai akun dari sesi, tanggal WIB dari server, status **Diajukan**, dan biaya awal belum ditentukan. User hanya melihat pengajuan miliknya serta status dan biaya terbaru; pemuatan status Nuxt diperbarui setiap 15 detik saat tab aktif.

Admin membuka `/laundry`, mengisi biaya dalam Rupiah, memilih status **Diajukan** atau **Selesai**, lalu menekan Simpan. Penyelesaian wajib menyertakan biaya, minimal Rp 0 dan maksimal Rp 9.999.999.999,99 (dua angka desimal). Biaya Rp 0 ditampilkan sebagai nol, berbeda dari biaya yang belum ditentukan. Tabel kedua sisi mendukung pencarian, sorting, ukuran 10/50/100, dan navigasi awal/akhir tanpa reload halaman.

Migrasi `2026_10_09_000006_add_laundry_submissions` menambah `user_id`, `jumlah_stel`, dan `biaya` pada tabel `laundry`, mempertahankan data lama. Pesanan lama tanpa pemohon tetap terlihat di admin; jumlah stel dan biaya tidak ditebak. API khusus user: `GET/POST /api/asrama/user/laundry`; admin memakai `GET /api/asrama/laundry` dan `PATCH /api/asrama/laundry/{id}` (`status`, `biaya`). Mutasi Blade memakai jalur tanpa `/api/asrama`. Akses role dan CSRF diterapkan pada endpoint tersebut. Catering menampilkan jadwal menu dan rating di sisi user.

## Menu catering dan rating

Admin membuat jadwal melalui `/catering`, mengisi nomor menu (1–10.000), penyedia (maksimal 255 karakter), dan waktu catering dalam WIB. Waktu disimpan UTC dan dikirim sebagai ISO 8601. Tabel `/user/catering` menampilkan menu, penyedia, waktu, rata-rata rating, jumlah penilai, serta rating pribadi. Skala rating adalah bilangan bulat 1–5 bintang. User memilih rating pada baris menu yang sesuai, lalu menekan Simpan rating.

Satu akun user memiliki satu rating per jadwal catering. Menilai ulang memperbarui nilai sebelumnya tanpa menambah jumlah penilai. Rata-rata dan jumlah penilai ditampilkan pada tabel kedua role; admin tidak dapat memberi rating, dan user tidak dapat menambah menu. Identitas penilai selalu berasal dari sesi. Transaksi mengunci jadwal, serta indeks unik `(catering_id, user_id)` mencegah rating ganda. Tidak ada rating buatan untuk data lama.

Migrasi `2026_10_09_000007_add_catering_menus_and_ratings` menambah `menu`, `penyedia`, `waktu_catering` pada tabel `catering` dan membuat tabel `catering_ratings`. Data lama tetap disimpan; kolom baru yang belum diketahui ditampilkan dengan fallback nama/keterangan lama atau Belum diatur. Admin memakai `GET/POST /api/asrama/catering`; user memakai `GET /api/asrama/user/catering` dan `POST /api/asrama/user/catering/{id}/rating` (`rating`). Mutasi Blade memakai jalur tanpa `/api/asrama`. Endpoint dilindungi role dan CSRF.

Tabel mendukung pencarian menu/penyedia, sorting termasuk rata-rata rating dan jumlah penilai, ukuran 10/50/100, dan navigasi awal/akhir tanpa reload. Nuxt memuat ulang data setiap 15 detik saat tab aktif tanpa menghapus isian form atau pilihan rating yang belum dikirim.

## Keluhan asrama di halaman Kamar

Bagian Keluhan asrama tersedia di `/user/kamar` dan `/kamar`, tanpa menu sidebar baru. User mengirim judul (maksimal 150 karakter) dan isi keluhan (maksimal 2.000 karakter, dapat menyebut lokasi), lalu melihat tabel keluhannya sendiri. Status awal adalah **Diajukan**. User tidak dapat mengubah status, melihat keluhan user lain, atau menyamar sebagai pemohon lain melalui input ID.

Admin melihat seluruh keluhan di tabel halaman Kamar dan menekan **Tandai Resolved** setelah penanganan selesai. Status menjadi **Resolved**, tombol penanganan disembunyikan, serta akun admin dan waktu penyelesaian dicatat dari server. Aksi berulang mempertahankan waktu penyelesaian awal. Waktu pengajuan dan penyelesaian disimpan UTC dan ditampilkan WIB.

Migrasi `2026_10_09_000008_create_keluhan_asrama_table` membuat tabel `keluhan_asrama`. API user: `GET/POST /api/asrama/user/keluhan`. API admin: `GET /api/asrama/keluhan`, `PATCH /api/asrama/keluhan/{id}` dengan `status: Resolved`. Mutasi Blade memakai jalur tanpa `/api/asrama` dan kembali ke halaman Kamar. Semua endpoint menerapkan sesi, role, dan CSRF.

Tabel keluhan mendukung pencarian, sorting kolom data, ukuran 10/50/100, navigasi awal/akhir, dan indikator halaman tanpa reload. Filter terpisah dari tabel kamar dan izin keluar. Nuxt memuat ulang data setiap 15 detik saat tab aktif tanpa menghapus isian form keluhan. Tabel contoh keluhan lama di halaman pengumuman tetap terpisah dari fitur ini.

## Modal feedback

Hasil aksi seluruh fitur ditampilkan dalam modal dengan tombol **OK**, termasuk login/logout, alokasi/profil kamar, check-in/check-out, izin keluar, laundry, catering/rating, dan keluhan. Pengajuan izin keluar menggunakan judul **Berhasil diajukan**. Pesan validasi atau kegagalan juga menggunakan modal; isian form yang gagal tetap tersedia untuk diperbaiki. Indikator pemuatan tabel tetap tampil saat data sedang dimuat.

Nuxt menggunakan modal global `FeedbackModal` dan antrean `useFeedback`, sehingga pesan tetap tersedia setelah navigasi login/logout. Mutasi melalui `useAsrama` menampilkan hasil secara otomatis. Blade menampilkan flash session dan error validasi melalui partial `feedback-modal`, `asrama-feedback.js`, dan `asrama-feedback.css`. Pesan ditampilkan sebagai teks, bukan HTML. Modal mendukung mode terang/gelap, fokus awal pada OK, navigasi keyboard, Escape untuk menutup, serta pengembalian fokus setelah ditutup.

## Verifikasi

`php artisan test`

Pengujian mencakup halaman Blade, API JSON, login/logout, akses tamu, validasi status, penyimpanan data, serta pencarian. Pengujian migrasi memeriksa pemindahan data dan timestamp, rollback dengan ID yang sama di layanan berbeda, serta perlindungan kategori lama yang tidak dikenal. API diuji agar pembaruan status pada satu layanan tidak mengubah layanan lain. Verifikasi frontend melalui typecheck dan build Nuxt.

Dengan Nuxt dan Laravel berjalan, jalankan `npm --prefix frontend run test:smoke` (Node sesuai persyaratan) untuk memeriksa halaman, logo, proxy Laravel, cookie sesi, CSRF, dan akses tamu melalui HTTP. Skrip tidak menambah pengguna atau data layanan. Set `ASRAMA_URL` jika frontend berjalan pada port lain.

`npm --prefix frontend run test:browser` memeriksa pengalih tema, persistensi setelah reload, preferensi perangkat, sinkronisasi antar-tab, penyimpanan yang tidak tersedia, tampilan seluler, dan tema Blade. Pada Windows pengujian memakai Microsoft Edge yang terpasang; gunakan `PLAYWRIGHT_CHANNEL` untuk mengganti browser. Pada Linux pasang Chromium Playwright dengan `npx --prefix frontend playwright install chromium`. Screenshot hasil pengujian tersedia di `frontend/test-results/` (diabaikan Git).
