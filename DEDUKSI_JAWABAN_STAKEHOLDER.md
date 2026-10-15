# Deduksi dari jawaban pemangku kepentingan

Pendamping `konfirmasi.md`, **untuk tim pengembang** (tidak perlu diberikan ke pengisi).
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

## 4. Taksonomi Aturan: Penolakan Mutlak dan Peringatan

Bagian ini merumuskan taksonomi validasi antarmuka dan backend agar sistem bekerja secara terprediksi (*predictable*), tidak melakukan perubahan drastis secara diam-diam, dan membedakan secara tegas antara penolakan mutlak dengan konfirmasi peringatan.

### A. Penolakan Mutlak oleh Sistem
Tindakan berikut melanggar logika fisik asrama, norma dasar, atau integritas data. Sistem menolak permintaan dengan pesan validasi yang jelas:

1. **Pemisahan jenis kelamin sekamar** *(Berdasarkan No. 4b)*: Laki-laki dan perempuan dilarang ditempatkan di kamar yang sama.
2. **Kapasitas kamar** *(Berdasarkan No. 1a)*: Kapasitas tetap 2 orang per kamar (posisi 1 dan 2); sistem menolak penambahan penghuni ketiga.
3. **Kamar nonaktif** *(Berdasarkan No. 1b)*: Kamar yang ditandai nonaktif/rusak (`is_active = false`) dilewati oleh alokasi otomatis dan tidak dapat dipilih untuk penempatan.
4. **Check-in tanpa alokasi kamar** *(Keputusan Pengembang / Integritas Data)*: Penghuni yang belum memiliki kamar atau slot aktif dilarang melakukan check-in.
5. **Validasi waktu izin keluar** *(Berdasarkan No. 10a, 10e, dan 10f)*:
   - Waktu kembali harus setelah waktu keluar.
   - Waktu keluar tidak boleh mendahului waktu pengajuan (tidak boleh izin untuk waktu lampau).
   - Waktu izin tidak boleh bertabrakan dengan izin lain yang sedang aktif (`PermitStatus IN ('pending', 'approved')`) milik penghuni yang sama.
   - Hanya penghuni yang sedang aktif menginap (sudah check-in dan belum check-out) yang dapat mengajukan izin.
6. **Penilaian catering sebelum hidang** *(Berdasarkan No. 17a)*: Penghuni dilarang menilai menu sebelum waktu makan tiba (`served_at <= now()`).
7. **Batas waktu penilaian catering** *(Berdasarkan No. 17b)*: Penilaian terkunci setelah pukul 23.59 WIB pada hari menu dihidangkan.
8. **Penyelesaian laundry wajib biaya** *(Deduksi No. 14a)*: Karena bukan laundry gratis dan tidak ada tarif otomatis per stel, biaya diketik manual oleh petugas dan wajib diisi sebelum status laundry diubah ke `done`.
9. **Status akhir laundry** *(Berdasarkan No. 15b)*: Laundry yang sudah berstatus selesai tidak dapat dikembalikan menjadi diajukan.
10. **Penolakan izin wajib alasan** *(Berdasarkan No. 12c)*: Petugas wajib mencantumkan alasan penolakan secara tertulis saat menolak permohonan izin (termasuk saat mengubah keputusan dari disetujui menjadi ditolak).
11. **Pembatalan izin oleh penghuni** *(Berdasarkan No. 12b)*: Penghuni hanya dapat membatalkan izin selama statusnya masih diajukan (`pending`), yang mengubah status menjadi `cancelled`.
12. **Pembatalan laundry oleh penghuni** *(Berdasarkan No. 15c)*: Penghuni hanya dapat membatalkan pesanan laundry selama statusnya masih diajukan (`pending`), yang mengubah status menjadi `cancelled`.
13. **Pilihan agama terstandar** *(Berdasarkan No. 6c)*: Pilihan agama wajib dipilih dari enam agama resmi yang baku; sistem menolak teks bebas.
14. **Pencegahan menu catering ganda** *(Berdasarkan No. 16a & Deduksi Bagian 1)*: Kombinasi tanggal hidang WIB (`served_on`, bertipe DATE) dan waktu makan (`meal_time`) memiliki kunci unik `UNIQUE(served_on, meal_time)` untuk mencegah duplikasi menu pada waktu makan yang sama di hari yang sama.

### B. Peringatan dan Konfirmasi (Petugas Tetap Dapat Melanjutkan)
Tindakan berikut menyimpang dari preferensi ideal namun dapat terjadi karena kebutuhan penataan di lapangan. Sistem memunculkan dialog konfirmasi dan tidak mengunci tindakan petugas:

1. **Penempatan di luar lantai rombongan** *(Berdasarkan No. 2 dan keleluasaan No. 5a)*: Bila peserta ditempatkan di luar lantai utama rombongan angkatannya, sistem menampilkan dialog konfirmasi penempatan sebelum disimpan.
2. **Teman sekamar beda angkatan** *(Berdasarkan No. 3b)*: Diizinkan bila peserta ganjil atau kuota mendesak. Sistem menampilkan konfirmasi peringatan perbedaan angkatan, dan petugas dapat menyetujui.
3. **Teman sekamar beda agama** *(Berdasarkan No. 4a)*: Diutamakan seagama, namun diperbolehkan berbeda bila ketersediaan kamar mendesak. Sistem menampilkan konfirmasi perbedaan agama tanpa membatalkan penempatan.
4. **Perbaikan profil setelah penempatan** *(Berdasarkan No. 7)*: Petugas dapat memperbaiki data penghuni kapan saja. Bila perubahan menyebabkan ketidakcocokan kriteria dengan teman sekamar, sistem memunculkan peringatan tanpa mengunci data atau membatalkan kamar.
5. **Konfirmasi tindakan berdampak luas**:
   - **Check-out mandiri** *(Berdasarkan No. 9b)*: Tombol check-out menampilkan dialog penegasan ("Apakah Anda yakin?") agar kamar tidak terlepas tanpa sengaja.
   - **Pencabutan alokasi kamar** *(Berdasarkan No. 5a)*: Menampilkan dialog konfirmasi sebelum petugas mengosongkan kamar peserta.

### C. Kemudahan Operasional Pengguna
1. **Akses masuk banyak perangkat** *(Berdasarkan No. 18)*: Penghuni dan petugas dapat masuk secara bersamaan melalui ponsel dan komputer tanpa pembatasan jumlah perangkat per akun. Pengamanan percobaan kata sandi salah (*rate limiting*) dibatasi per kombinasi `email + IP` agar peserta di bawah Wi-Fi asrama yang sama (shared public IP) tidak saling mengunci.
2. **Pemulihan salah check-out** *(Berdasarkan No. 9b)*: Petugas memiliki fungsi untuk membatalkan check-out yang keliru sehingga penghuni tidak kehilangan kamarnya.
3. **Pendaftaran dan impor massal** *(Berdasarkan No. 6a)*: Petugas mendaftarkan penghuni melalui unggahan berkas Excel sekaligus maupun pendaftaran manual satu per satu oleh petugas.

---

## 5. Keputusan Final per Nomor Pertanyaan (Tabel Implementasi)

Tabel berikut merangkum seluruh keputusan final dari 20 butir pertanyaan di `konfirmasi.md` yang telah diisi per 15 Oktober 2026 dan bagaimana keputusan tersebut diterapkan pada kode sumber:

| No | Jawaban Terpilih (`konfirmasi.md`) | Yang Dibangun / Dampak Teknis |
|---|---|---|
| **1** | Data 78 kamar sudah benar; perlu menandai kamar rusak/perbaikan | Kolom `is_active` (boolean, bawaan `true`) pada tabel `rooms`; alokasi otomatis melewati kamar nonaktif; saklar aktif/nonaktif pada antarmuka admin. Status monitoring kamar (`RoomStatus`) ditambah nilai `inactive` untuk kamar nonaktif. Kapasitas tetap 2 orang per kamar dari konfigurasi. |
| **2** | Ditempatkan di lantai mana saja yang masih kosong | `AllocateRooms::findRoomFor()` mencari kamar di lantai asal ke bawah (5 → 4 → 3), lalu meluas ke lantai lain yang masih memiliki slot kosong; tidak membiarkan peserta menunggu jika gedung masih memiliki kamar kosong. |
| **3 & 4** | Boleh berbagi lantai; boleh berbagi kamar antar-angkatan (diutamakan se-angkatan); diusahakan seagama; gender cukup beda kamar (diutamakan pengelompokan lantai/sayap per jenis kelamin; laki-laki & perempuan sekamar dilarang keras); peserta khusus di lantai bawah | `AllocateRooms` menjalankan pencarian kamar bertingkat dengan syarat mutlak di setiap tahap: **jenis kelamin sama**, **kamar aktif (`is_active = true`)**, dan **kapasitas belum penuh (`< 2`)**.<br/>Urutan pencarian:<br/>1. Lantai rombongan + se-angkatan + seagama<br/>2. Lantai rombongan + se-angkatan (toleran agama)<br/>3. Lantai rombongan (toleran angkatan & agama)<br/>4. Lantai lain yang masih memiliki slot kosong.<br/>Penempatan manual admin memberi peringatan bila beda agama atau beda angkatan, dan memblokir mutlak bila beda gender. Peserta berkebutuhan khusus/lansia/hamil diatur via plotting penempatan manual admin. |
| **5** | Petugas bisa memilihkan kamar, memindahkan (swap/pindah slot), dan membatalkan alokasi; pembatalan tidak otomatis | Action penempatan manual (`AssignRoomSlot`), pemindahan/tukar slot (`MoveResidentRoom`), dan pencabutan alokasi (`UnassignRoomSlot`) menggunakan transaksi dengan `RoomLocks`. Pembatalan alokasi dilakukan manual oleh petugas bila peserta tidak hadir. |
| **6** | Petugas mengimpor Excel sekaligus atau mendaftarkan satu per satu; agama 6 pilihan resmi; format angkatan terstandar | Endpoint & antarmuka unggah impor Excel/CSV untuk pendaftaran massal akun & profil peserta; formulir tambah akun satu per satu oleh petugas; enum `Religion` (6 agama resmi); validasi format string angkatan pada FormRequest. |
| **7** | Petugas boleh memperbaiki data profil kapan saja | Kunci edit profil setelah dialokasikan kamar dilepas; `UpdateResidentProfile` dapat dipanggil kapan saja oleh admin; jika data baru berbeda dengan teman sekamar, sistem memunculkan peringatan di antarmuka tanpa membatalkan kamar. |
| **8** | Penghuni check-in/out mandiri; petugas bisa mencatatkan check-out atas nama penghuni | Penghuni menekan check-in dan check-out dari antarmuka mandiri; endpoint & tombol check-out oleh admin untuk mencatatkan kepulangan penghuni yang lupa melapor. |
| **9** | Boleh dialokasikan lagi dengan akun yang sama; dialog konfirmasi check-out; petugas bisa batalkan check-out | Pengecualian "pernah check-out" dihapus dari `AllocateRooms::pendingResidents()`; dialog modal konfirmasi ("Apakah Anda yakin?") pada tombol check-out penghuni; Action `CancelCheckOut` bagi admin untuk memulihkan slot kamar yang salah check-out (selama slot belum diisi orang lain). |
| **10** | Izin waktu lampau dilarang; waktu bebas; durasi bebas; jam malam bebas; dilarang izin tumpang tindih; hanya penghuni aktif menginap | Validasi di `SaveLeavePermitRequest`: `leaves_at >= now()`, `returns_at > leaves_at`, tidak ada irisan waktu dengan izin aktif lain (`PermitStatus IN ('pending', 'approved')`). Validasi membatasi pengajuan hanya untuk penghuni berstatus aktif (`stays.checked_in_at IS NOT NULL AND stays.checked_out_at IS NULL`). |
| **11** | Jam aktual keluar/kembali tidak perlu dicatat di aplikasi | Tidak ada penambahan kolom waktu aktual keluar/kembali pada tabel `leave_permits`; status izin tetap `PermitStatus` standar; penegakan disiplin keterlambatan ditangani secara lisan/manual oleh pengelola. |
| **12** | Petugas bisa ubah keputusan; penghuni bisa batalkan izin selama pending; penolakan wajib alasan | Endpoint pembatalan izin oleh penghuni dengan syarat `status = 'pending'`, mengubah status menjadi `cancelled`; kolom `rejection_reason` wajib diisi saat menolak izin (termasuk bila petugas mengubah keputusan dari disetujui menjadi ditolak); petugas memiliki wewenang mengubah keputusan status izin bila terjadi kondisi mendesak. |
| **13** | Petugas bisa tulis balasan; ada status sedang ditangani; keluhan bisa dibuka kembali | Kolom `response` (text nullable) pada tabel `complaints`; enum `ComplaintStatus` diperluas dengan `in_progress`; transisi status: `pending` → `in_progress` → `resolved`; pembukaan kembali keluhan (`resolved` → `pending` / `in_progress`) dapat dilakukan oleh penghuni (pelapor) maupun petugas. |
| **14** | Biaya laundry diketik petugas; pembayaran tidak dicatat; tanpa batas kuota laundry | Input biaya manual pada saat petugas menyelesaikan laundry (`status = done`); tidak ada kolom status pembayaran (diselesaikan langsung secara fisik di luar sistem); tidak ada pembatasan kuota stel laundry. |
| **15** | Laundry dengan opsi pembatalan; selesai tidak bisa kembali ke diajukan; penghuni bisa membatalkan saat pending | Enum `LaundryStatus` memuat status `pending`, `done`, dan `cancelled`; transisi status selesai bersifat final (`done` tidak dapat kembali ke `pending`); penghuni dapat membatalkan pesanan selama status masih `pending`, mengubah status menjadi `cancelled`. |
| **16** | Menu wajib nama makanan & waktu makan; tahapan status menu dihapus; petugas bisa edit/hapus menu; cegah menu ganda | Kolom `dish_name` dan `meal_time` (enum `MealTime`: `breakfast`, `lunch`, `dinner`) pada `catering_menus`; kolom `status` (`CateringStatus`) dihapus; endpoint `PATCH` (edit) dan `DELETE` (hapus) menu untuk admin; kolom `served_on` (DATE dalam WIB `Asia/Jakarta`) dengan indeks unik `UNIQUE(served_on, meal_time)` untuk mencegah menu ganda pada waktu makan yang sama di hari yang sama. |
| **17** | Rating sebelum hidang ditolak; rating bisa diganti sampai 23.59 WIB hari penyajian; tanpa komentar | Validasi `served_at <= now()` pada penyimpanan rating; method `ratingClosesAt()` mengunci rating pada pukul 23.59 WIB tanggal hidang (zona `Asia/Jakarta`); kolom komentar tidak ditambahkan (cukup rating bintang 1–5). |
| **18** | Kapasitas 156 penghuni, 100–150 login serentak; multi-device bebas | Batasan percobaan login (*rate limiting*) dibatasi per kombinasi `email + IP` agar lonjakan login peserta di bawah satu Wi-Fi asrama (shared IP) tidak saling memblokir; sesi login multi-device diizinkan (bebas buka di ponsel dan komputer tanpa pembatasan sesi tunggal). |
| **19** | Fasilitas rilis awal: ganti/lupa kata sandi dan unduh data Excel/PDF | Formulir ubah kata sandi dan alur reset kata sandi; tombol ekspor data tabel ke format Excel/PDF; fitur pemberitahuan, pengumuman, dan peran pengguna lain ditunda untuk rilis berikutnya. |
| **20** | Data aplikasi versi lama tidak dimigrasi | Basis data dimulai dari kondisi bersih (`migrate:fresh` aman untuk pengembangan dan pengujian); tidak diperlukan skrip migrasi data historis. |



