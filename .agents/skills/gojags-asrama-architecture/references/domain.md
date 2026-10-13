# Aturan bisnis yang wajib bertahan

Rewrite mengubah struktur, bukan perilaku. Setiap butir di bawah berasal dari kode lama dan harus tetap
berlaku serta tercakup oleh tes. Nama memakai istilah target (lihat `migration-map.md` untuk padanannya).
Teks pesan dalam tanda kutip dipertahankan apa adanya, kecuali kata "user" pada pesan boleh tetap.

## Isi

1. Peran dan akses · 2. Sesi dan login · 3. Waktu dan zona · 4. Tabel · 5. Kamar dan status ·
6. Profil penghuni · 7. Alokasi otomatis · 8. Check-in / check-out · 9. Izin keluar · 10. Keluhan ·
11. Laundry · 12. Catering · 13. Dashboard · 14. Data awal

## 1. Peran dan akses

- Dua peran: `admin` dan `resident`. Otorisasi sebenarnya ada di backend (`auth` + `role:<peran>`);
  pengalihan di middleware Nuxt hanya untuk kenyamanan.
- Peran yang salah → 403 "Anda tidak memiliki izin untuk halaman ini." Belum login → 401.
- Penghuni hanya melihat data miliknya sendiri (izin, keluhan, laundry, kamar). Admin melihat semuanya.
  Penghuni yang mengakses id milik orang lain mendapat 404, bukan 403 (tidak membocorkan keberadaan).

## 2. Sesi dan login

- Login: `email` + `password`. Gagal → error validasi pada `email`: "Email atau kata sandi tidak sesuai."
- Login dibatasi 5 percobaan per menit (`throttle:5,1`).
- Berhasil login → `session()->regenerate()`. Logout → logout, `invalidate()`, `regenerateToken()`.
- `GET /api/session` mengembalikan `{ csrfToken, user }` (`user` null bila belum login). Login dan logout
  mengembalikan bentuk yang sama, supaya klien tidak perlu memanggil ulang.
- Semua respons API berheader `Cache-Control: no-store`.
- Semua permintaan tulis wajib CSRF (`X-CSRF-TOKEN`).

## 3. Waktu dan zona

- Disimpan UTC. Dikirim ke klien sebagai ISO-8601 UTC. Ditampilkan dan diisi pengguna dalam **WIB**
  (`Asia/Jakarta`), dengan akhiran " WIB".
- Klien mengubah isian `datetime-local` (WIB) menjadi ISO UTC sebelum mengirim; API hanya menerima
  format `Y-m-d\TH:i:s.v\Z` (keluaran `Date.toISOString()`). Lihat `backend.md` § Waktu untuk alasannya.

## 4. Tabel

- Parameter: `q` (maks 100 karakter), `sort`, `direction` (`asc`|`desc`), `per_page` (hanya 10, 50, 100),
  `page`. Nilai di luar itu → 422.
- Bawaan: `sort=id`, `direction=desc`, `per_page=10`.
- Kolom `sort` di-whitelist per tabel. Bila `sort` bukan `id`, `id` menjadi pengurut kedua dengan arah
  yang sama (urutan stabil antar halaman).
- Pencarian memakai `LIKE %q%` pada kolom yang ditentukan per tabel.
- Di klien: pencarian di-*debounce* 300 ms; mengganti pencarian, urutan, atau `per_page` kembali ke
  halaman 1; mengeklik kolom yang sama membalik arah, kolom baru mulai dari `asc`.
- Tabel yang datanya bisa berubah oleh pihak lain dimuat ulang tiap 15 detik, hanya saat tab terlihat
  dan tidak ada aksi yang sedang berjalan.

## 5. Kamar dan status

- Inventaris tetap: lantai 3, 4, 5 × 26 kamar = **78 kamar**, bernomor `301`…`326`, `401`…`426`,
  `501`…`526`. Tiap kamar 2 slot (kapasitas 2).
- Satu user paling banyak menempati satu slot (`room_slots.user_id` unik).
- Status kamar dihitung dari slot. Dengan `assigned` = slot berpenghuni dan `checkedIn` = slot
  berpenghuni yang sudah check-in:

  | Kondisi (dievaluasi berurutan) | Status |
  |---|---|
  | `assigned == 0` | `empty` (Kosong) |
  | `assigned > checkedIn` | `assigned` (Assigned) — ada penghuni yang belum check-in |
  | `checkedIn >= 2` | `full` (Penuh) |
  | selain itu | `occupied` (Terisi) |

- Daftar kamar admin: kolom nomor, lantai, penghuni (nama + waktu check-in atau "Belum check-in"),
  kapasitas `n / 2`, status. Cari pada nomor dan lantai. Bisa diurutkan menurut nomor, lantai, nama
  penghuni pertama (abjad), jumlah penghuni, dan status (urutan Kosong < Assigned < Terisi < Penuh).
- Ringkasan monitoring: jumlah kamar per status, keempat status selalu muncul (nol bila tidak ada).

## 6. Profil penghuni (untuk alokasi)

- Admin mengisi `cohort`, `gender`, `religion` tiap penghuni. Ketiganya wajib; `cohort` maks 100,
  `religion` maks 50 karakter.
- `cohort` dan `religion` dinormalisasi saat disimpan: spasi berlebih diringkas jadi satu, di-`trim`,
  huruf kecil. Tujuannya agar "Islam", " islam " dan "ISLAM" dianggap sama saat mencocokkan teman
  sekamar. Bila hasilnya kosong → "Angkatan dan agama wajib diisi."
- Hanya user berperan `resident` yang bisa diubah (selain itu 404).
- Profil penghuni yang sudah punya slot terkunci: "Profil penghuni yang sudah dialokasikan tidak dapat
  diubah."
- Berhasil → "Profil alokasi berhasil disimpan."

## 7. Alokasi otomatis

Dijalankan admin, seluruhnya dalam satu transaksi.

**Siapa yang dialokasikan.** Penghuni (`resident`) yang belum punya slot **dan** belum pernah check-out
(tidak punya `stays` dengan `checked_out_at` terisi). Dari mereka:
- profil belum lengkap (salah satu dari cohort/gender/religion kosong) → dilaporkan `incomplete`;
- sisanya dikelompokkan per `cohort`, kelompok diproses menurut urutan alami nama cohort (`SORT_NATURAL`),
  anggota menurut `id`.

Penghuni yang sudah punya kamar tidak pernah dipindahkan.

**Lantai asal tiap cohort**, ditentukan sekali saat cohort itu mulai diproses (memperhitungkan penempatan
cohort sebelumnya), pilihan pertama yang ada:
1. lantai tertinggi yang sudah dihuni anggota cohort itu;
2. lantai tertinggi yang seluruh kamarnya masih kosong (cohort baru mendapat lantai utuh bila ada);
3. lantai tertinggi yang masih punya kamar berslot kosong.

**Penempatan tiap anggota.** Telusuri lantai dari atas ke bawah (5 → 4 → 3), lewati lantai di atas lantai
asal. Pada lantai pertama yang punya kandidat, pilih satu kamar:
- kandidat = kamar dengan penghuni < 2 **dan** semua penghuninya ber-`gender` dan `religion` sama dengan
  calon;
- prioritas: (0) kamar yang penghuni pertamanya se-cohort, (1) kamar terisi sebagian dari cohort lain,
  (2) kamar kosong; seri diselesaikan menurut nomor kamar menaik.

Artinya kamar terisi sebagian yang cocok selalu diisi sebelum membuka kamar kosong, dan teman sekamar
beda cohort diperbolehkan asal gender dan agamanya sama. Bila tidak ada kandidat di lantai mana pun →
dilaporkan `waiting`.

**Hasil.** `{ message, allocated: [{id, name, room, floor}], waiting: [{id, name}], incomplete: [{id, name}] }`
dengan pesan "`N` user dialokasikan. `M` profil belum lengkap. `K` user menunggu kamar yang sesuai."

**Urutan kunci.** Alokasi, simpan profil, check-in, dan check-out mengambil `lockForUpdate` dengan urutan
yang selalu sama: `rooms` → `users` → `room_slots`. Urutan yang konsisten mencegah *deadlock* antara
admin yang mengalokasikan dan penghuni yang check-in bersamaan. Pertahankan urutan ini di kode baru apa
pun yang mengunci tabel-tabel tersebut. Transaksi diulang hingga 3 kali.

## 8. Check-in / check-out (penghuni)

- Halaman "Kamar saya" menampilkan kamar yang dialokasikan (nomor, lantai, waktu check-in) atau
  "Belum ada alokasi kamar", ditambah riwayat `stays` terbaru lebih dulu.
- **Check-in**: butuh slot ("Anda belum memiliki alokasi kamar."); tidak boleh dua kali ("Anda sudah
  check-in."). Mengisi `room_slots.checked_in_at` dan membuat `stays` baru berisi salinan nama user,
  nomor kamar, lantai. → "Check-in berhasil."
- **Check-out**: butuh slot; harus sudah check-in ("Anda harus check-in sebelum check-out."); harus ada
  `stays` aktif ("Riwayat check-in aktif tidak ditemukan."). Mengisi `stays.checked_out_at` lalu
  **melepas slot** (`user_id` dan `checked_in_at` jadi null). → "Check-out berhasil. Slot kamar telah
  dilepaskan."
- Setelah check-out, user tidak dialokasikan ulang oleh alokasi otomatis (lihat § 7).
- Keduanya memakai urutan kunci § 7.

## 9. Izin keluar

- Penghuni mengajukan: `leaves_at`, `returns_at` (wajib sesudah `leaves_at`: "Waktu kembali harus setelah
  waktu keluar."), `reason` (maks 2000). Status awal `pending`. → 201 "Izin keluar berhasil diajukan."
- Penghuni boleh mengedit izin **miliknya** selama masih `pending`; selain itu "Hanya izin berstatus
  Diajukan yang dapat diedit." → "Pengajuan izin berhasil diperbarui."
- Admin meninjau: `status` hanya `approved` atau `rejected`, dan hanya untuk izin `pending`; selain itu
  "Pengajuan ini sudah diproses." Mengisi `reviewed_by` dan `reviewed_at`. → "Izin keluar disetujui." /
  "Izin keluar ditolak."
- Edit dan tinjau mengunci baris (`lockForUpdate`) dalam transaksi, supaya edit penghuni dan keputusan
  admin yang bersamaan tidak saling menimpa.
- Tabel: admin melihat kolom nama pemohon. Cari pada nama pemohon, alasan, status. Urut menurut nama
  pemohon, waktu keluar, waktu kembali, alasan, status.
- Klien: bila izin yang sedang diedit ternyata sudah diproses admin (terlihat saat muat ulang), form
  di-reset dan muncul "Pengajuan sudah diproses admin dan tidak dapat diedit."

## 10. Keluhan

- Penghuni mengirim `title` (maks 150) dan `body` (maks 2000). Status awal `pending`. → 201 "Keluhan
  asrama berhasil dikirim."
- Admin menandai selesai → `resolved`, mengisi `resolved_by`, `resolved_at`. → "Keluhan ditandai
  Resolved." Permintaan berulang **tidak** mengubah waktu dan admin penyelesai pertama (idempoten).
- Tabel: cari pada judul, isi, status, nama pelapor. Urut menurut nama pelapor, judul, isi, status,
  waktu diajukan, waktu resolved.

## 11. Laundry

- Penghuni mengajukan `items` (maks 255) dan `set_count` (bilangan bulat 1–1000). Status awal `pending`,
  `cost` null. → 201 "Pengajuan laundry berhasil dikirim."
- Admin memperbarui `status` dan `cost`. `cost` wajib bila status `done`; angka 0–9999999999.99 dengan
  paling banyak 2 desimal. → "Status berhasil diperbarui."
- Tampilan: biaya dalam Rupiah (`id-ID`, IDR), atau "Belum ditentukan" bila null.
- Tabel: admin melihat kolom nama pemesan. Cari pada barang dan status (admin: juga nama pemesan). Urut
  menurut barang, jumlah stel, tanggal, status, biaya (admin: juga nama pemesan).

## 12. Catering

- Admin menambah menu: `menu_number` (bilangan bulat 1–10000), `provider` (maks 255), `served_at`. Status
  awal `scheduled`. → 201 "Menu catering berhasil ditambahkan."
- Admin dapat mengubah status menu (`scheduled` / `preparing` / `done`). → "Status berhasil diperbarui."
- Penghuni memberi rating 1–5 per menu; satu rating per penghuni per menu, mengirim ulang **menimpa**
  rating sebelumnya. → "Rating catering berhasil disimpan."
- Daftar menu menyertakan `rating_average` (dibulatkan 2 desimal, null bila belum ada), `rating_count`,
  dan `my_rating` (rating milik pemanggil, null bila belum menilai).
- Tabel: cari pada nomor menu dan penyedia. Urut menurut nomor menu, penyedia, waktu, rata-rata rating,
  jumlah penilai, rating saya.
- Tampilan: "Menu `n`", rata-rata sebagai "`4,5` / 5 ★" atau "Belum ada rating".

## 13. Dashboard

- Admin: jumlah kamar, pesanan laundry, dan menu catering.
- Penghuni: sambutan dengan nama user.

## 14. Data awal

- `RoomSeeder`: 78 kamar + 2 slot per kamar; aman dijalankan ulang.
- `AccountSeeder`: `admin@example.id` / `admin123` (admin), `user@example.id` / `user123` (penghuni),
  serta `user1..10@example.id` / `user123` — `user1..5` laki-laki cohort `64`, `user6..10` perempuan
  cohort `63`, dan `religion` **sengaja kosong** agar alur "profil belum lengkap" bisa didemokan.
  Menjalankan ulang seeder tidak menimpa profil yang sudah diisi.
