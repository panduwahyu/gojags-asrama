# Audit Kesesuaian Kode dengan Keputusan Stakeholder

Tanggal: 2026-10-15 · Commit yang diaudit: fd7cac6

## Ringkasan

Dari 61 item teknis yang diturunkan dari keputusan no. 1–20 (§ 5 `docs/notes/DEDUKSI_JAWABAN_STAKEHOLDER.md`): **Ada 22**, **Sebagian 1**, **Tidak ada 31**, **Bertentangan 7**. Tabel § 5 menulis kolom "Yang Dibangun" seolah semuanya sudah ada; kenyataannya sebagian besar fitur baru (`is_active`, `Religion`, penempatan manual, impor Excel, batal izin/laundry, tanggapan keluhan, ekspor, kata sandi) belum ada di kode. Ada **12 item** yang bertentangan dengan `domain.md`; pemilik harus memilih salah satu sebagai sumber kebenaran (daftar di bawah).

Status: `Ada` = lengkap; `Sebagian` = ada tetapi kurang; `Tidak ada` = tidak ditemukan; `Bertentangan` = kode melakukan kebalikannya. Semua nomor baris merujuk commit yang diaudit.

## Temuan

| No | Keputusan (ringkas) | Item teknis | Status | Bukti | Bertentangan dengan domain.md? |
|---|---|---|---|---|---|
| 1 | Tandai kamar rusak/perbaikan | Kolom `rooms.is_active` (boolean, bawaan true) | Tidak ada | `grep is_active` di app/database/routes/frontend/tests: 0 hasil; `create_rooms_table.php` tidak memuatnya | Ya — domain.md § 5 baris 53–54 (inventaris tetap 78 kamar) |
| 1 | Tandai kamar rusak/perbaikan | Alokasi otomatis melewati kamar nonaktif | Tidak ada | `AllocateRooms.php:156-163` (`canHouse`) hanya memeriksa slot kosong, gender, agama | Ya — domain.md § 7 baris 103–104 (kandidat hanya ditentukan slot, gender, agama) |
| 1 | Tandai kamar rusak/perbaikan | Saklar aktif/nonaktif di antarmuka admin | Tidak ada | `php artisan route:list --path=api`: kamar hanya `GET api/rooms` dan `POST api/rooms/allocate`; tidak ada rute ubah kamar | Tidak |
| 1 | Tandai kamar rusak/perbaikan | Nilai `RoomStatus::inactive` | Tidak ada | `app/Enums/RoomStatus.php:79-82`: hanya empty, assigned, occupied, full | Ya — domain.md § 5 baris 56–64 (empat status) |
| 1 | Kapasitas tetap 2 dari konfigurasi | `config('asrama.room_capacity')` = 2 | Ada | `config/asrama.php:6`; `app/Models/Room.php:47`; `RoomResource.php:31` | Tidak |
| 2 | Penempatan di lantai mana saja yang kosong | Cari mulai lantai asal ke bawah (5→4→3) | Ada | `AllocateRooms.php:137-140` | Tidak |
| 2 | Penempatan di lantai mana saja yang kosong | Meluas ke lantai lain (termasuk di atas lantai asal) bila masih ada slot kosong | Bertentangan | `AllocateRooms.php:138-139`: `if ($floor > $homeFloor) continue;` — kamar kosong di lantai lebih atas tidak pernah dipakai, penghuni masuk `waiting` padahal gedung belum penuh | Ya — domain.md § 7 baris 101–102 (lewati lantai di atas lantai asal) |
| 3 & 4 | Berbagi lantai/kamar boleh; seagama diusahakan; beda gender dilarang | Syarat mutlak jenis kelamin sama | Ada | `AllocateRooms.php:160` | Tidak |
| 3 & 4 | Berbagi lantai/kamar boleh; seagama diusahakan; beda gender dilarang | Syarat mutlak kapasitas < 2 | Ada | `app/Support/RoomOccupancy.php:62-65` (`hasFreeSlot`), dipakai di `AllocateRooms.php:158` | Tidak |
| 3 & 4 | Berbagi lantai/kamar boleh; seagama diusahakan; beda gender dilarang | Satu angkatan diutamakan satu kamar; angkatan lain boleh bila perlu | Ada | `AllocateRooms.php:166-177` (`fillPriority`: se-angkatan 0, angkatan lain 1, kamar kosong 2) | Tidak |
| 3 & 4 | Berbagi lantai/kamar boleh; seagama diusahakan; beda gender dilarang | Agama hanya diutamakan, boleh berbeda bila mendesak | Bertentangan | `AllocateRooms.php:161`: `religion` harus sama, tanpa putaran pencarian yang melonggarkan agama | Ya — domain.md § 7 baris 103–104, 109–110 |
| 3 & 4 | Berbagi lantai/kamar boleh; seagama diusahakan; beda gender dilarang | Pencarian bertingkat 4 tahap (lantai rombongan+se-angkatan+seagama → … → lantai lain) | Sebagian | Hanya satu tahap: urutan prioritas angkatan di `fillPriority`; tidak ada tahap toleransi agama/lantai lain | Ya — domain.md § 7 baris 101–110 |
| 3 & 4 | Berbagi lantai/kamar boleh; seagama diusahakan; beda gender dilarang | Pengelompokan lantai/sayap per jenis kelamin | Tidak ada | `AllocateRooms.php:105-129` (`homeFloorFor`) tidak memakai gender | Tidak |
| 3 & 4 | Berbagi lantai/kamar boleh; seagama diusahakan; beda gender dilarang | Penempatan manual: peringatan beda agama/angkatan, blokir beda gender | Tidak ada | Tidak ada endpoint penempatan manual (lihat no. 5) | Tidak |
| 5 | Petugas memilihkan kamar, memindahkan, membatalkan alokasi | Action `AssignRoomSlot` | Tidak ada | `grep AssignRoomSlot`: 0 hasil; tidak ada rute | Tidak |
| 5 | Petugas memilihkan kamar, memindahkan, membatalkan alokasi | Action `MoveResidentRoom` | Tidak ada | `grep MoveResidentRoom`: 0 hasil; tidak ada rute | Tidak (domain.md § 7 baris 93 hanya melarang pemindahan oleh alokasi otomatis) |
| 5 | Petugas memilihkan kamar, memindahkan, membatalkan alokasi | Action `UnassignRoomSlot` | Tidak ada | `grep UnassignRoomSlot`: 0 hasil; tidak ada rute | Tidak |
| 5 | Petugas memilihkan kamar, memindahkan, membatalkan alokasi | Transaksi dengan `RoomLocks` untuk Action yang sudah ada | Ada | `CheckIn.php:15`, `CheckOut.php:54`, `UpdateResidentProfile.php:35`, `AllocateRooms.php` (`RoomLocks::acquireAll`) | Tidak |
| 6 | Impor Excel atau daftar satu per satu; agama 6 pilihan; format angkatan baku | Impor Excel/CSV massal | Tidak ada | `grep -iE "xlsx\|csv\|Excel"` di app/routes/frontend/app: 0 hasil | Tidak |
| 6 | Impor Excel atau daftar satu per satu; agama 6 pilihan; format angkatan baku | Formulir tambah akun satu per satu | Tidak ada | Rute `residents` hanya `GET` dan `PATCH {resident}` | Tidak |
| 6 | Impor Excel atau daftar satu per satu; agama 6 pilihan; format angkatan baku | Enum `Religion` (6 agama) | Tidak ada | `app/Enums/`: tidak ada `Religion`; `UpdateResidentRequest.php:20` `'religion' => string max:50`; `User.php:80-83` menormalisasi teks bebas | Ya — domain.md § 6 baris 75–77 (teks bebas dinormalisasi huruf kecil) |
| 6 | Impor Excel atau daftar satu per satu; agama 6 pilihan; format angkatan baku | Validasi format angkatan | Tidak ada | `UpdateResidentRequest.php:19` hanya `string max:100` | Tidak |
| 7 | Petugas boleh memperbaiki profil kapan saja | Kunci edit profil setelah dialokasikan dilepas | Bertentangan | `UpdateResidentProfile.php:37-41` masih menolak: "Profil penghuni yang sudah dialokasikan tidak dapat diubah." | Ya — domain.md § 6 baris 79–80 |
| 7 | Petugas boleh memperbaiki profil kapan saja | Peringatan di antarmuka bila tidak cocok dengan teman sekamar | Tidak ada | Tidak ada kode pembanding teman sekamar di `UpdateResidentProfile` maupun `frontend/app` | Tidak |
| 8 | Check-in/out mandiri; petugas mencatatkan check-out | Check-in/check-out oleh penghuni | Ada | Rute `POST api/my-room/check-in` dan `check-out`; `CheckIn.php`, `CheckOut.php` | Tidak |
| 8 | Check-in/out mandiri; petugas mencatatkan check-out | Endpoint dan tombol check-out oleh admin | Tidak ada | `route:list`: tidak ada rute admin untuk check-out | Tidak (domain.md § 8 hanya membahas penghuni) |
| 9 | Boleh dialokasikan lagi; konfirmasi check-out; petugas batalkan check-out | Hapus pengecualian "pernah check-out" dari alokasi | Bertentangan | `AllocateRooms.php:91-101`: `$departed` (stays dengan `checked_out_at`) masih mengecualikan penghuni | Ya — domain.md § 7 baris 87–88 dan § 8 baris 131 |
| 9 | Boleh dialokasikan lagi; konfirmasi check-out; petugas batalkan check-out | Dialog konfirmasi pada tombol check-out | Tidak ada | `grep -iE "modal\|dialog\|yakin\|confirm"` di `frontend/app`: 0 hasil | Tidak |
| 9 | Boleh dialokasikan lagi; konfirmasi check-out; petugas batalkan check-out | Action `CancelCheckOut` | Tidak ada | `grep CancelCheckOut`: 0 hasil; tidak ada rute | Tidak |
| 10 | Izin: tanpa waktu lampau, tanpa tumpang tindih, hanya penghuni menginap | `leaves_at >= now()` | Tidak ada | `SaveLeavePermitRequest.php:18` hanya `DateFormat::UTC`; `grep after_or_equal`: 0 hasil | Tidak (domain.md § 9 baris 136 diam) |
| 10 | Izin: tanpa waktu lampau, tanpa tumpang tindih, hanya penghuni menginap | `returns_at > leaves_at` | Ada | `SaveLeavePermitRequest.php:19` (`after:leaves_at`), pesan `:27` | Tidak |
| 10 | Izin: tanpa waktu lampau, tanpa tumpang tindih, hanya penghuni menginap | Larangan izin tumpang tindih (pending/approved) | Tidak ada | Tidak ada kueri irisan waktu di `SaveLeavePermitRequest` maupun `LeavePermitController.php` | Tidak |
| 10 | Izin: tanpa waktu lampau, tanpa tumpang tindih, hanya penghuni menginap | Hanya penghuni aktif menginap (berlaku juga untuk laundry & rating) | Tidak ada | `grep -E "stays\|checked_in"` di `app/Http/Requests` dan `LeavePermitController.php`: 0 hasil; penghuni tanpa kamar bisa mengajukan | Tidak |
| 11 | Jam aktual keluar/kembali tidak dicatat | Tidak ada kolom waktu aktual pada `leave_permits` | Ada | `create_leave_permits_table.php:13-21` hanya `leaves_at`, `returns_at`, `reviewed_*` | Tidak |
| 12 | Petugas ubah keputusan; penghuni batalkan saat pending; penolakan wajib alasan | Endpoint batal oleh penghuni (status `cancelled`) | Tidak ada | `PermitStatus.php:60-62` tanpa `cancelled`; rute hanya `PATCH leave-permits/{permit}` dan `/review` | Tidak |
| 12 | Petugas ubah keputusan; penghuni batalkan saat pending; penolakan wajib alasan | Kolom `rejection_reason` wajib saat menolak | Tidak ada | `grep rejection_reason`: 0 hasil; `ReviewLeavePermitRequest.php` hanya memvalidasi `status` | Tidak |
| 12 | Petugas ubah keputusan; penghuni batalkan saat pending; penolakan wajib alasan | Petugas dapat mengubah keputusan yang sudah diambil | Bertentangan | `LeavePermitController.php:61` memanggil `ensurePending`: "Pengajuan ini sudah diproses." | Ya — domain.md § 9 baris 140–142 |
| 13 | Balasan, status sedang ditangani, buka kembali | Kolom `complaints.response` | Tidak ada | `grep "'response'"`: 0 hasil | Tidak |
| 13 | Balasan, status sedang ditangani, buka kembali | Status `in_progress` | Tidak ada | `ComplaintStatus.php:43-44` hanya pending/resolved | Tidak |
| 13 | Balasan, status sedang ditangani, buka kembali | Pembukaan kembali keluhan | Tidak ada | `ComplaintController.php:42-57` hanya `resolve`; tidak ada rute buka kembali | Ya — domain.md § 10 baris 154–155 (resolve idempoten, tidak ada jalur kembali) |
| 14 | Biaya diketik petugas; tanpa status bayar; tanpa kuota | Biaya manual, wajib saat `done` | Ada | `UpdateLaundryOrderRequest.php:20-27` | Tidak (domain.md § 11 baris 163–164 sama) |
| 14 | Biaya diketik petugas; tanpa status bayar; tanpa kuota | Tanpa kolom status pembayaran | Ada | `create_laundry_orders_table.php:13-19` | Tidak |
| 14 | Biaya diketik petugas; tanpa status bayar; tanpa kuota | Tanpa batas kuota stel | Ada | `grep -iE "kuota\|quota"` di app/database/config/routes: 0 hasil | Tidak |
| 15 | Pembatalan laundry; selesai bersifat final | Status `cancelled` | Tidak ada | `LaundryStatus.php:26-27` hanya pending/done | Tidak |
| 15 | Pembatalan laundry; selesai bersifat final | `done` tidak dapat kembali ke `pending` | Bertentangan | `UpdateLaundryOrderRequest.php:19` menerima enum apa pun; `LaundryOrderController::update` tanpa guard transisi, sehingga `done` → `pending` diterima | Tidak (domain.md § 11 diam soal transisi) |
| 15 | Pembatalan laundry; selesai bersifat final | Penghuni membatalkan saat `pending` | Tidak ada | Rute `laundry-orders`: hanya `GET`, `POST`, `PATCH {order}` (admin) | Tidak |
| 16 | Menu wajib nama & waktu makan; status dihapus; cegah menu ganda | Kolom `dish_name` dan `meal_time` | Ada | `create_catering_menus_table.php:14-15`; `app/Enums/MealTime.php` | Tidak |
| 16 | Menu wajib nama & waktu makan; status dihapus; cegah menu ganda | Hapus kolom/enum `status` dan endpoint ubah status | Bertentangan | `create_catering_menus_table.php:18`; `CateringStatus.php:7-9`; `UpdateCateringMenuRequest.php:26`; `CateringMenuController.php:48` (cabang "Status berhasil diperbarui.") | Ya — domain.md § 12 baris 171–173 |
| 16 | Menu wajib nama & waktu makan; status dihapus; cegah menu ganda | Edit dan hapus menu oleh admin | Ada | Rute `PATCH` dan `DELETE api/catering-menus/{menu}`; `CateringMenuController.php:44-58` | Tidak |
| 16 | Menu wajib nama & waktu makan; status dihapus; cegah menu ganda | Kolom `served_on` + `UNIQUE(served_on, meal_time)` | Tidak ada | `grep served_on`: 0 hasil; migrasi catering_menus tanpa indeks unik | Tidak |
| 17 | Rating sebelum hidang ditolak; bisa diganti sampai 23.59 WIB; tanpa komentar | Tolak rating sebelum `served_at` | Ada | `CateringRatingController.php:18-22` | Tidak (domain.md § 12 diam) |
| 17 | Rating sebelum hidang ditolak; bisa diganti sampai 23.59 WIB; tanpa komentar | Rating terkunci 23.59 WIB hari hidang | Ada | `CateringRatingController.php:24-28`; `CateringMenu.php:53-58` (`endOfDay` zona `Asia/Jakarta`) | Tidak (domain.md § 12 diam) |
| 17 | Rating sebelum hidang ditolak; bisa diganti sampai 23.59 WIB; tanpa komentar | Rating dapat diganti, satu per penghuni per menu | Ada | `CateringRatingController.php:30-33` (`updateOrCreate`); unik `(catering_menu_id, user_id)` di `create_catering_ratings_table.php:18` | Tidak |
| 17 | Rating sebelum hidang ditolak; bisa diganti sampai 23.59 WIB; tanpa komentar | Tanpa kolom komentar | Ada | Tabel `catering_ratings` tanpa kolom komentar | Tidak |
| 17 | Rating sebelum hidang ditolak; bisa diganti sampai 23.59 WIB; tanpa komentar | Hanya penghuni yang menginap boleh menilai (dari no. 10) | Tidak ada | `CateringRatingController.php:13-37` tidak memeriksa slot/stays | Tidak |
| 18 | 156 penghuni, 100–150 login serentak, multi-device | Rate limit login per email + IP dan batas per IP | Ada | `RouteServiceProvider.php` (limiter `login`); `config/asrama.php` `login_attempts_per_ip`; commit `80a129c` | Tidak (domain.md § 2 baris 24 sudah diperbarui di Tugas 18) |
| 18 | 156 penghuni, 100–150 login serentak, multi-device | Multi-device tanpa batas sesi tunggal | Ada | `grep -E "logoutOtherDevices\|AuthenticateSession"` di app/bootstrap/routes/config: 0 hasil | Tidak |
| 18 | 156 penghuni, 100–150 login serentak, multi-device | Ukuran halaman tabel 10/50/100 | Ada | `config/asrama.php:7`; `app/Support/TableQuery.php` | Tidak (domain.md § 4 sama) |
| 19 | Ganti/lupa kata sandi; unduh Excel/PDF | Formulir ubah dan alur lupa kata sandi | Tidak ada | `route:list`: tidak ada rute kata sandi; `grep password` hanya di `LoginRequest`, `User`, `Handler`, `TrimStrings` | Tidak |
| 19 | Ganti/lupa kata sandi; unduh Excel/PDF | Ekspor tabel ke Excel/PDF | Tidak ada | `grep -iE "xlsx\|csv"`: 0 hasil; hit `export` hanya sintaks modul TypeScript | Tidak |
| 20 | Data lama tidak dimigrasi | Tanpa skrip migrasi data historis | Ada | Hanya migrasi `2026_10_13_*`; tidak ada skrip migrasi data | Tidak |

## Keputusan yang bertentangan dengan `domain.md`

Pemilik perlu memutuskan, per butir, apakah `domain.md` diubah mengikuti keputusan stakeholder (lalu kode dibangun) atau keputusan dikoreksi. Butir:

- **No. 1**
  - Kolom `rooms.is_active` (boolean, bawaan true) → domain.md § 5 baris 53–54 (inventaris tetap 78 kamar)
  - Alokasi otomatis melewati kamar nonaktif → domain.md § 7 baris 103–104 (kandidat hanya ditentukan slot, gender, agama)
  - Nilai `RoomStatus::inactive` → domain.md § 5 baris 56–64 (empat status)
- **No. 2**
  - Meluas ke lantai lain (termasuk di atas lantai asal) bila masih ada slot kosong → domain.md § 7 baris 101–102 (lewati lantai di atas lantai asal)
- **No. 3 & 4**
  - Agama hanya diutamakan, boleh berbeda bila mendesak → domain.md § 7 baris 103–104, 109–110
  - Pencarian bertingkat 4 tahap (lantai rombongan+se-angkatan+seagama → … → lantai lain) → domain.md § 7 baris 101–110
- **No. 6**
  - Enum `Religion` (6 agama) → domain.md § 6 baris 75–77 (teks bebas dinormalisasi huruf kecil)
- **No. 7**
  - Kunci edit profil setelah dialokasikan dilepas → domain.md § 6 baris 79–80
- **No. 9**
  - Hapus pengecualian "pernah check-out" dari alokasi → domain.md § 7 baris 87–88 dan § 8 baris 131
- **No. 12**
  - Petugas dapat mengubah keputusan yang sudah diambil → domain.md § 9 baris 140–142
- **No. 13**
  - Pembukaan kembali keluhan → domain.md § 10 baris 154–155 (resolve idempoten, tidak ada jalur kembali)
- **No. 16**
  - Hapus kolom/enum `status` dan endpoint ubah status → domain.md § 12 baris 171–173

Catatan tambahan: `domain.md` tidak menyebut aturan waktu izin (no. 10), tolak rating sebelum hidang dan batas 23.59 WIB (no. 17), serta transisi laundry (no. 15); ketiganya perlu ditambahkan agar tes dan dokumen sejalan.

## Dugaan (belum terbukti)

- Item no. 10 butir "hanya penghuni aktif menginap" kemungkinan juga perlu mencakup laundry dan rating (keputusan no. 17 merujuknya). Cakupan persisnya (apakah penghuni yang belum check-in tetap boleh) belum dikonfirmasi pemilik.
- Teks tabel § 5 no. 2 ("meluas ke lantai lain") tidak tegas apakah termasuk lantai di atas lantai asal; saya menilai ini `Bertentangan` karena kode melewati lantai di atasnya.

## Dampak ke naskah skripsi

Perintah: `grep -nE "diimpor|unggah|ekspor|ubah kata sandi|nonaktif|is_active|CancelCheckOut|in_progress|served_on|tarif|pembayaran|PDF" skripsi/chapters/0[1-6]*.typ` (pola `impor` polos dihindari karena menangkap `#import` Typst). Naskah **tidak diubah**. Kalimat yang menyebut fitur berstatus `Tidak ada`/`Sebagian`:

| Berkas:baris | Isi naskah (ringkas) | Fitur terkait | Status |
|---|---|---|---|
| `04-analisis-perancangan.typ:189` | Panitia "mengunggah data nominatif peserta diklat ke dalam sistem GOJAGS" | Impor Excel/CSV massal (no. 6) | Tidak ada |
| `04-analisis-perancangan.typ:189` | Modul alokasi "semi-otomatis" mengelompokkan peserta | Penempatan manual/pemindahan (no. 5) | Tidak ada (hanya otomatis) |
| `04-analisis-perancangan.typ:247` | Kondisi awal: "data nominatif ... telah diimpor dan kamar asrama berstatus aktif" | Impor (no. 6); status kamar aktif (no. 1) | Tidak ada |
| `02-tinjauan-pustaka.typ:54` | Peran "Penyelenggara Pelatihan (Bidang DPK/DTF)" berwenang mengunggah draf penempatan | Peran selain admin/penghuni (no. 19) | Tidak ada (`Role.php:7-8` hanya admin, resident) |
| `04-analisis-perancangan.typ:38` | Penatu gratis dua potong per hari; kelebihan dikenai tarif tambahan | Kuota dan tarif laundry (no. 14 memutuskan tanpa kuota) | Tidak ada; menggambarkan kondisi lapangan, bukan fitur — periksa apakah dipakai sebagai kebutuhan sistem |

Bab V (`05-implementasi-evaluasi.typ`) tidak memuat pola di atas, sehingga tidak ada klaim fitur `Tidak ada` yang terdeteksi di sana dengan pola ini. Pengecekan manual klaim fitur di naskah tetap disarankan.

## Perintah yang dijalankan

- `git rev-parse --short HEAD` → `fd7cac6`
- `php artisan route:list --path=api` → 26 rute; tidak ada rute penempatan manual, impor, kata sandi, batal izin/laundry, atau check-out oleh admin
- `grep` kata kunci (`is_active`, `Religion`, `AssignRoomSlot`, `MoveResidentRoom`, `UnassignRoomSlot`, `CancelCheckOut`, `in_progress`, `rejection_reason`, `'response'`, `served_on`, `after_or_equal`, `xlsx`, `export`, `inactive`, kuota/tarif) di `app database routes frontend/app tests` → hasil kosong, kecuali `password` (LoginRequest, User, Handler, TrimStrings) dan `cancelled`/`unique` yang hanya muncul di migrasi bawaan Laravel/factory
- Pembacaan langsung: `AllocateRooms.php`, `UpdateResidentProfile.php`, `CheckIn.php`, `CheckOut.php`, `LeavePermitController.php`, `ComplaintController.php`, `CateringRatingController.php`, `CateringMenuController.php`, `SaveLeavePermitRequest.php`, `UpdateLaundryOrderRequest.php`, migrasi `2026_10_13_*`, enum di `app/Enums/`
- `grep` naskah di `skripsi/chapters/0[1-6]*.typ` (lihat bagian di atas)
