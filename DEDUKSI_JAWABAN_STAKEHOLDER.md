# Deduksi dari jawaban pemangku kepentingan

Pendamping `PERTANYAAN_STAKEHOLDER.md`, **untuk tim pengembang** (tidak perlu diberikan ke pengisi).
Daftar awal berisi 44 pertanyaan; yang diberikan hanya 20. Dokumen ini menunjukkan bahwa 24 sisanya
terjawab oleh salah satu dari tiga cara: tergabung ke pertanyaan lain, bisa disimpulkan dari jawaban,
atau cukup diputuskan pengembang.

## 1. Dari jawaban ke keputusan pembangunan

Tiap baris: jawaban yang mungkin → apa yang dibangun → hal lain yang ikut terjawab.

| No | Jawaban | Yang dibangun | Ikut terjawab |
|---|---|---|---|
| 1 | Inventaris berbeda / kapasitas tidak seragam | Kapasitas pindah dari konfigurasi ke kolom per kamar; `RoomStatus` menghitung dari kapasitas kamar | Admin perlu mengelola kamar sendiri → ya |
| 1 | Perlu menonaktifkan kamar | Kolom aktif/nonaktif pada kamar; alokasi melewatinya; status monitoring baru | Admin perlu mengelola kamar sendiri → ya, minimal aktif/nonaktif |
| 1 | Sudah benar, tidak perlu nonaktif | Tidak ada perubahan | Pengelolaan inventaris oleh admin → tidak perlu |
| 2 | Lantai mana pun | Cari kamar di lantai asal ke bawah, lalu lantai di atasnya | Urutan pengisian dari lantai atas tetap dipakai sebagai urutan utama |
| 2 | Tetap menunggu | Tidak ada perubahan | Daftar tunggu tersimpan → perlu (lihat § 2) |
| 3 | Berbagi kamar tidak boleh | Syarat kandidat kamar ditambah "se-angkatan" | — |
| 3 | Berbagi lantai tidak boleh | Angkatan tanpa lantai kosong masuk daftar tunggu | Daftar tunggu tersimpan → perlu |
| 3 | Jumlah angkatan ≤ 3 | Aturan berbagi jarang terpakai; cukup dipertahankan | — |
| 4 | Agama "diutamakan" | Dua putaran pencarian: cocok agama dulu, lalu tanpa syarat agama | Siapa berwenang melonggarkan → sistem otomatis, admin melihat laporannya |
| 4 | Harus beda lantai | Lantai asal ditentukan per (angkatan, jenis kelamin) | Jawaban no. 3 "berbagi lantai" otomatis menjadi "tidak antar-jenis-kelamin" |
| 5 | Salah satu dicentang | Action penempatan/pemindahan/pencabutan memakai `RoomLocks` | Penghuni tidak datang → admin mencabut manual |
| 5 | Ada batas hari | Tugas terjadwal yang mencabut alokasi tanpa check-in | — |
| 6 | Akun dari SSO/impor | Tidak perlu layar buat akun; perlu sinkronisasi/impor | Lupa kata sandi (no. 19) tidak relevan bila SSO; keterkaitan dengan GOJAGS → ya, data peserta |
| 6 | Akun dibuat admin | Layar kelola akun (buat, nonaktifkan) | Nasib akun setelah diklat → dinonaktifkan admin |
| 6 | Daftar agama & format angkatan | Agama menjadi enum; angkatan divalidasi formatnya | Normalisasi teks bebas tidak lagi diperlukan |
| 6 | Diisi penghuni sendiri | Form profil di sisi penghuni | No. 7 berlaku juga untuk penghuni |
| 7 | Admin boleh memperbaiki | Kunci profil dihapus; peringatan bila tak cocok dengan teman sekamar | Wajib ada pemindahan manual (no. 5), walau tidak dicentang |
| 8 | Konfirmasi petugas / hanya petugas | Check-in menjadi dua langkah atau pindah ke sisi admin | Bukti kehadiran → petugas itulah buktinya; serah-terima kunci bisa dicatat di langkah ini |
| 8 | Penghuni sendiri | Tidak ada perubahan | Bukti kehadiran & serah-terima kunci → tidak dicatat |
| 9 | Boleh dialokasikan lagi | Hapus pengecualian "pernah check-out" dari alokasi | Edit profil penghuni yang sudah check-out menjadi wajar |
| 9 | Harus akun baru / tidak pernah | Aturan sekarang dipertahankan; profil penghuni yang sudah check-out ikut dikunci | — |
| 9 | Admin bisa membatalkan | Action batal check-out (mengembalikan slot bila masih kosong) | — |
| 10 | Tiap batasan | Aturan validasi di `SaveLeavePermitRequest` + pesan Indonesia | Jenis izin berbeda-beda → tidak, satu aturan untuk semua |
| 11 | Catat keluar/kembali | Kolom waktu aktual + status "Keluar"/"Kembali"/"Terlambat" | Bila dicatat petugas → perlu peran petugas (no. 19) |
| 12 | Tiap pilihan | Endpoint batal; kolom alasan penolakan; (opsional) ubah keputusan | Jenjang persetujuan → tidak ada, kecuali ditulis di catatan |
| 13 | Tanggapan / status antara / buka kembali | Kolom tanggapan; status `in_progress`; transisi kembali ke `pending` | Kategori & lampiran → tidak sekarang |
| 14 | Per stel / per kilo | Biaya dihitung otomatis dari tarif di konfigurasi | Batas biaya manual tidak diperlukan |
| 14 | Gratis | Kolom biaya disembunyikan | Status lunas tidak relevan |
| 14 | Perlu status lunas | Kolom status pembayaran | Laporan biaya laundry (no. 19) menjadi relevan |
| 15 | Empat tahap | Enum `LaundryStatus` diperluas; transisi satu arah | Admin mencatat laundry atas nama penghuni → tidak perlu |
| 16 | Status tidak dibutuhkan | Hapus `CateringStatus`, kolom, dan endpoint ubah status | — |
| 16 | Nama hidangan / waktu makan | Kolom baru pada menu; kunci unik (waktu makan, tanggal) | Mencegah menu ganda → ya, lewat kunci unik itu |
| 17 | Ditolak sebelum disajikan | Validasi `served_at <= now()` | Hanya yang menginap boleh menilai → ikuti jawaban no. 10 butir terakhir |
| 18 | Angka pengguna | Ukuran halaman tabel, batas login per akun, kebutuhan paginasi daftar profil | Kecukupan 156 tempat tidur; apakah daftar tunggu sering terjadi |
| 19 | Tiap centang | Masuk ruang lingkup rilis pertama | Yang tidak dicentang → ditunda, bukan dibatalkan |
| 20 | Ada data nyata | Skrip migrasi data lama → skema baru | Bila tidak ada → `migrate:fresh` aman |

## 2. Nasib 24 pertanyaan yang tidak ditanyakan

| Pertanyaan awal | Cara terjawab |
|---|---|
| Kamar rusak/nonaktif | **Digabung** ke no. 1 |
| Admin mengelola inventaris sendiri | **Deduksi** dari no. 1: perlu hanya bila inventaris berbeda-beda atau ada kamar nonaktif |
| Pemisahan laki-laki/perempuan per lantai | **Digabung** ke no. 4 |
| Urutan pengisian dari lantai atas | **Diputuskan pengembang**: dipertahankan; tidak ada dampak bagi pengguna |
| Penghuni yang tidak pernah datang | **Digabung** ke no. 5 |
| Daftar tunggu tersimpan | **Deduksi** dari no. 2, 3, 18: perlu hanya bila jawabannya membuat orang menunggu padahal kapasitas cukup |
| Penulisan angkatan/agama baku | **Digabung** ke no. 6 |
| Data tambahan penghuni (NIP, kontak) | **Deduksi** dari no. 6: bila akun dari SSO/impor, data itu ikut; bila tidak, ditunda |
| Bukti kehadiran saat check-in | **Deduksi** dari no. 8 |
| Check-out oleh admin / otomatis | **Digabung** ke no. 8; otomatis pada akhir diklat **ditunda** (butuh tanggal diklat, lihat no. 6) |
| Serah-terima kunci | **Deduksi** dari no. 8: hanya masuk akal bila petugas terlibat |
| Siapa yang boleh mengajukan izin | **Digabung** ke no. 10 |
| Jenjang persetujuan & jenis izin | **Diputuskan pengembang**: satu jenjang, satu jenis; ditulis di catatan bila pengisi keberatan |
| Kategori, lokasi, lampiran keluhan | **Diputuskan pengembang**: ditunda; tidak mengubah alur |
| Edit/tarik keluhan | **Deduksi** dari no. 12: perlakuan sama dengan pembatalan izin |
| Pembatalan laundry | **Digabung** ke no. 15 |
| Laundry dicatatkan admin | **Diputuskan pengembang**: tidak; versi lama punya dan tidak terpakai |
| Menu ganda | **Deduksi** dari no. 16 |
| Status menu | **Digabung** ke no. 16 |
| Konfirmasi makan, alergi | **Diputuskan pengembang**: ditunda; fitur baru, bukan aturan yang ambigu |
| Lupa/ganti kata sandi, peran lain, pemberitahuan, pengumuman, laporan | **Digabung** ke no. 19 |
| Ketentuan data pribadi (agama) | **Deduksi** dari no. 4 dan 6: bila agama tidak dipakai untuk penempatan, kolomnya dihapus; bila dipakai, hanya admin yang melihat |
| Riwayat tindakan admin | **Diputuskan pengembang**: catat pelaku pada alokasi dan perubahan profil; murah dan tidak mengubah alur |
| Perangkat, zona waktu, kaitan GOJAGS | **Diputuskan pengembang**: antarmuka sudah responsif; WIB dipertahankan (Pusdiklat di Jakarta); kaitan GOJAGS **deduksi** dari no. 6 |

## 3. Jawaban yang saling bergantung

Periksa kombinasi ini saat menerima jawaban; bila bertentangan, tanyakan kembali hanya butir itu.

| Bila | Maka |
|---|---|
| No. 7 "admin boleh memperbaiki" tetapi no. 5 tanpa "memindahkan" | Pemindahan tetap dibangun — perbaikan data bisa membuat teman sekamar tidak cocok |
| No. 9 "boleh dialokasikan lagi" tetapi no. 6 "akun dari impor per angkatan" | Tanyakan: apakah peserta yang kembali memakai akun lama atau akun baru |
| No. 4 "harus beda lantai" dan no. 3 jumlah angkatan ≥ 2 | Tiga lantai tidak cukup untuk memisah angkatan sekaligus jenis kelamin; tanyakan mana yang diutamakan |
| No. 8 "hanya petugas" atau no. 11 "dicatat petugas" tetapi no. 19 tanpa "peran lain" | Petugas memakai akun admin; konfirmasi bahwa itu dapat diterima |
| No. 14 "gratis" tetapi no. 19 meminta laporan biaya | Tanyakan maksud laporannya (jumlah stel, bukan rupiah?) |
| No. 2 "tetap menunggu" dan no. 18 penghuni mendekati 156 | Daftar tunggu akan sering terjadi → jadikan daftar tunggu tersimpan |
| No. 10 "hanya yang menginap" dan no. 9 "tidak pernah kembali" | Penghuni yang sudah check-out kehilangan akses izin, laundry, dan rating; konfirmasi akunnya dinonaktifkan |
