#set document(
  title: "Panduan Pengguna Penghuni Asrama — Gojags Asrama",
  author: "Tim Pengembang Gojags Asrama",
)

#set page(
  paper: "a4",
  margin: (x: 2cm, top: 2.5cm, bottom: 2.2cm),
  header: align(right)[
    #text(size: 8.5pt, fill: rgb("#64748b"))[Gojags Asrama • Panduan Penghuni (Resident)]
  ],
  numbering: "1 / 1",
)

#set text(
  font: ("Segoe UI", "Arial"),
  size: 10pt,
  lang: "id",
)

#set heading(numbering: "1.1")
#show heading: it => block(above: 1.3em, below: 0.8em)[#it]

#let note(body) = block(
  fill: rgb("#f0fdf4"),
  stroke: (left: 3pt + rgb("#16a34a")),
  radius: (right: 4pt),
  inset: (x: 10pt, y: 8pt),
  width: 100%,
  below: 0.8em,
  [#text(weight: "bold", fill: rgb("#166534"))[Catatan Penting:] #body]
)

#let tip(body) = block(
  fill: rgb("#eff6ff"),
  stroke: (left: 3pt + rgb("#2563eb")),
  radius: (right: 4pt),
  inset: (x: 10pt, y: 8pt),
  width: 100%,
  below: 0.8em,
  [#text(weight: "bold", fill: rgb("#1d4ed8"))[Tips Praktis:] #body]
)

#let warning(body) = block(
  fill: rgb("#fffbeb"),
  stroke: (left: 3pt + rgb("#d97706")),
  radius: (right: 4pt),
  inset: (x: 10pt, y: 8pt),
  width: 100%,
  below: 0.8em,
  [#text(weight: "bold", fill: rgb("#b45309"))[Perhatian / Tata Tertib:] #body]
)

#let step(num, title, body) = block(
  fill: rgb("#f8fafc"),
  stroke: rgb("#e2e8f0"),
  radius: 6pt,
  inset: (x: 10pt, y: 8pt),
  width: 100%,
  below: 0.8em,
  [
    #grid(
      columns: (auto, 1fr),
      gutter: 10pt,
      circle(radius: 9pt, fill: rgb("#0f172a"))[
        #align(center + horizon)[#text(fill: white, weight: "bold", size: 8pt)[#num]]
      ],
      [
        #text(weight: "bold", size: 10pt, fill: rgb("#0f172a"))[#title] \
        #v(2pt)
        #body
      ]
    )
  ]
)

#let screenshot(img_path, caption_text) = figure(
  rect(
    stroke: 0.5pt + rgb("#cbd5e1"),
    radius: 4pt,
    inset: 0pt,
    outset: 0pt,
  )[#image(img_path, width: 88%)],
  caption: caption_text,
)

// ==================== HALAMAN SAMPUL RESMI ====================
#page(header: none, footer: none)[
  #v(2cm)
  #align(center)[
    #block(
      fill: rgb("#0f172a"),
      radius: 6pt,
      inset: (x: 14pt, y: 6pt),
      [
        #text(size: 9pt, weight: "bold", fill: white)[
          SISTEM MANAJEMEN OPERASIONAL ASRAMA
        ]
      ]
    )

    #v(1.5cm)
    #text(size: 26pt, weight: "bold", fill: rgb("#0f172a"))[
      Buku Panduan Penghuni Asrama
    ]
    #v(0.5em)
    #text(size: 13pt, fill: rgb("#16a34a"), weight: "medium")[
      Gojags Asrama — Manual Portal Mandiri Mahasiswa & Penghuni
    ]

    #v(1.5cm)
    #line(length: 40%, stroke: 1.5pt + rgb("#cbd5e1"))
    #v(1.5cm)

    #text(size: 10pt, fill: rgb("#64748b"))[
      Panduan Praktis Layanan Hunian: Konfirmasi Check-in, Riwayat Menginap, \
      Cuci Pakaian Laundry, Evaluasi Katering, Izin Keluar, dan Laporan Kerusakan
    ]

    #v(3cm)

    #rect(
      fill: rgb("#f8fafc"),
      stroke: rgb("#e2e8f0"),
      radius: 8pt,
      width: 75%,
      inset: (x: 16pt, y: 12pt),
    )[
      #grid(
        columns: (1fr, 1fr),
        gutter: 12pt,
        align: left,
        [
          #text(weight: "bold", size: 9pt)[Penerbit & Pemilik Sistem:] \
          #text(size: 9pt)[Tim Pengembang Gojags Asrama] \
          #text(size: 9pt)[Versi Sistem: 1.0 (Stabil)]
        ],
        [
          #text(weight: "bold", size: 9pt)[Target Pengguna:] \
          #text(size: 9pt)[Penghuni / Mahasiswa (`resident`)] \
          #text(size: 9pt)[Verifikasi: E2E Playwright Suite]
        ]
      )
    ]
  ]
]

#pagebreak()

// ==================== DAFTAR ISI & GAMBAR ====================
#outline(title: "Daftar Isi", indent: 1.5em)

#v(1em)

#outline(title: "Daftar Gambar Panduan", target: figure.where(kind: image))

#pagebreak()

// ==================== BAB 1: AKSES AKUN ====================
= Akses Akun & Autentikasi Penghuni

Portal *Gojags Asrama* menyediakan antarmuka mandiri bagi seluruh penghuni asrama untuk mengelola seluruh
kebutuhan operasional kamar dan fasilitas secara digital tanpa perlu antre fisik di kantor sekretariat.

== Prosedur Masuk ke Sistem (Login)

#step("1", "Mengakses Halaman Login", [
  Arahkan browser ke alamat `http://127.0.0.1:3000/login`.
])

#step("2", "Memasukkan Email dan Kata Sandi", [
  Gunakan email terdaftar (contoh: `user@example.id`) serta kata sandi akun asrama Anda.
])

#step("3", "Klik Tombol Masuk", [
  Klik tombol *Masuk*. Sesi akan dibuat secara aman dengan enkripsi cookie dan perlindungan CSRF.
])

#screenshot(
  "screenshots/panduan/resident/01-login-penghuni-light.png",
  [Tampilan Antarmuka Halaman Login Penghuni (Mode Siang)],
)

== Penanganan Kesalahan Kredensial (Validasi Error)
Apabila alamat email atau kata sandi yang diketikkan tidak cocok dengan pangkalan data, sistem akan
menampilkan pesan peringatan berwarna merah (*Kredensial yang diberikan tidak sesuai dengan data kami*).

#warning[
  Demi keamanan akun, sistem menerapkan batas maksimal 5 kali percobaan login per menit. Jika melebihi batas,
  akses login akan ditangguhkan selama 60 detik secara otomatis.
]

#screenshot(
  "screenshots/panduan/resident/02-login-validasi-error.png",
  [Pemberitahuan Peringatan Validasi Saat Kredensial Tidak Sesuai],
)

#pagebreak()

// ==================== BAB 2: DASHBOARD ====================
= Dashboard Mandiri Penghuni

Setelah proses masuk berhasil, penghuni akan langsung disambut pada rute `/resident`. Dashboard dirancang
untuk menampilkan status hunian Anda secara ringkas dan memberikan akses 1-klik ke layanan utama.

== Komponen Utama Dashboard
1. *Salam Personal*: Menyapa nama lengkap penghuni sesuai data profil di sistem.
2. *Status Kamar*: Menampilkan nomor kamar aktif dan status check-in Anda saat ini.
3. *Kartu Pintasan Layanan*:
   - *Kamar & Check-in*: Mengakses manajemen penempatan kamar, tombol check-in/out, dan riwayat menginap.
   - *Cuci Pakaian*: Mengajukan cucian laundry baru dan memantau status pengerjaan.
   - *Menu & Rating*: Memeriksa jadwal hidangan makanan katering dan memberikan evaluasi bintang.

#screenshot(
  "screenshots/panduan/resident/03-dashboard-penghuni-overview.png",
  [Tampilan Dashboard Utama Penghuni dengan Salam Personal dan Kartu Layanan],
)

#screenshot(
  "screenshots/panduan/resident/04-dashboard-penghuni-dark.png",
  [Tampilan Dashboard Penghuni dalam Mode Malam (Dark Mode)],
)

#tip[
  Gunakan tombol sakelar tema matahari/bulan di sudut kanan atas untuk berganti antara mode siang dan mode malam.
  Pengaturan tema ini akan tetap tersimpan meskipun Anda menutup atau memuat ulang halaman peramban.
]

#pagebreak()

// ==================== BAB 3: KAMAR & SIKLUS TINGGAL ====================
= Layanan Kamar, Check-in, & Check-out

Pengelolaan masa tinggal dilakukan secara mandiri melalui menu *Kamar Saya* di rute `/resident/room`.

== Alur Masa Tinggal Asrama
#align(center)[
  `Alokasi Kamar (Assigned)` $arrow.r$ `Check-in Mandiri` $arrow.r$ `Aktif Menginap` $arrow.r$ `Check-out Mandiri`
]

== Memahami Alokasi Kamar (Status Assigned)
Setelah administrator menjalankan proses alokasi otomatis, sistem akan menempatkan Anda pada kamar tertentu
(Lantai 5, 4, atau 3) dan slot ranjang (Slot A atau Slot B). Pada tahap ini, status kamar adalah *Assigned*
(menunggu kehadiran fisik Anda di asrama).

#screenshot(
  "screenshots/panduan/resident/05-kamar-menunggu-checkin.png",
  [Status Kamar Assigned Menunggu Kedatangan Fisik dan Konfirmasi Check-in],
)

== Melakukan Konfirmasi Check-in
Saat Anda telah tiba di gedung asrama dan membawa perlengkapan pribadi:
#step("1", "Membuka Menu Kamar Saya", [
  Masuk ke menu *Kamar Saya* (`/resident/room`).
])
#step("2", "Menekan Tombol Check-in", [
  Klik tombol *Check-in*. Status kamar Anda seketika berubah menjadi *Sudah check-in*, dan pencatatan masa
  tinggal resmi dimulai pada basis data asrama.
])

#screenshot(
  "screenshots/panduan/resident/06-kamar-berhasil-checkin.png",
  [Konfirmasi Berhasil Check-in pada Kamar dan Slot Ranjang yang Dialokasikan],
)

== Melakukan Check-out & Riwayat Menginap (Stay History)
Ketika semester perkuliahan berakhir atau masa studi di asrama selesai, penghuni wajib melakukan check-out:
#step("1", "Memastikan Barang Bawaan Lengkap", [
  Pastikan kamar telah dibersihkan dan tidak ada barang pribadi yang tertinggal.
])
#step("2", "Menekan Tombol Check-out", [
  Pada menu *Kamar Saya*, klik tombol *Check-out*. Slot kasur Anda akan kembali berstatus kosong untuk
  ditempati oleh penghuni angkatan baru.
])
#step("3", "Melihat Riwayat Masa Tinggal", [
  Seluruh riwayat masa tinggal Anda (tanggal check-in dan tanggal check-out) tercatat permanen pada tabel
  *Riwayat check-in dan check-out* dengan status *Selesai*.
])

#screenshot(
  "screenshots/panduan/resident/07-kamar-riwayat-checkout.png",
  [Dokumentasi Lengkap Riwayat Tinggal Penghuni Setelah Proses Check-out],
)

#pagebreak()

// ==================== BAB 4: LAUNDRY ====================
= Layanan Cuci Pakaian (Laundry)

Asrama menyediakan fasilitas laundry terpadu yang dapat diajukan melalui menu *Laundry* (`/resident/laundry`).

== Mengajukan Cucian Pakaian Baru
#step("1", "Mengisi Rincian Pakaian", [
  Ketikkan deskripsi pakaian yang diserahkan (misal: *3 kemeja, 2 celana kain, 1 jaket*) pada kolom
  *Pakaian / barang yang dilaundry*.
])
#step("2", "Memasukkan Jumlah Stel", [
  Ketikkan jumlah stel pakaian pada kolom *Jumlah stel* (contoh: `3`).
])
#step("3", "Menekan Tombol Ajukan Laundry", [
  Klik tombol *Ajukan laundry*. Permohonan Anda akan tercatat dengan status awal *Diajukan* (`pending`).
])

#screenshot(
  "screenshots/panduan/resident/08-laundry-formulir-pengajuan.png",
  [Formulir Pengajuan Permohonan Cuci Pakaian Laundry Baru],
)

== Pelacakan Pengerjaan & Pelunasan Biaya
Petugas laundry akan mencuci, mengeringkan, dan menyetrika pakaian Anda. Setelah pengerjaan selesai,
status pada tabel cucian Anda otomatis berubah menjadi *Selesai*, lengkap dengan rincian biaya resmi
(misalnya: *Rp 30.000*).

#note[
  Saat mengambil pakaian di ruang laundry asrama, tunjukkan halaman ini kepada petugas dan selesaikan
  administrasi pembayaran sesuai nominal biaya yang tertera.
]

#screenshot(
  "screenshots/panduan/resident/09-laundry-status-selesai.png",
  [Status Cucian Berubah Menjadi Selesai Beserta Rincian Biaya Layanan],
)

#pagebreak()

// ==================== BAB 5: CATERING ====================
= Jadwal & Penilaian Menu Catering

Layanan konsumsi asrama menyajikan makanan tiga kali sehari dan dikelola transparan di `/resident/catering`.

== Jadwal Santap Harian
#table(
  columns: (1.2fr, 2.2fr, 2.6fr),
  fill: (col, row) => if row == 0 { rgb("#f1f5f9") } else { none },
  stroke: 0.5pt + rgb("#cbd5e1"),
  inset: 6pt,
  [*Waktu Santap*], [*Jam Layanan (WIB)*], [*Ketentuan Pengambilan*],
  [Sarapan (`breakfast`)], [06.00 – 08.00 WIB], [Diambil di ruang makan lantai dasar sebelum perkuliahan.],
  [Makan Siang (`lunch`)], [11.30 – 13.30 WIB], [Disajikan prasmanan atau boks sesuai jadwal harian.],
  [Makan Malam (`dinner`)], [18.00 – 20.00 WIB], [Pengambilan hidangan malam setelah waktu salat Magrib.]
)

== Memberikan Penilaian Rating Katering
Penghuni berhak mengevaluasi kualitas rasa, higienitas, dan porsi makanan:
#step("1", "Memeriksa Menu Hari Ini", [
  Buka halaman `/resident/catering`. Tabel menyajikan daftar menu, waktu santap, dan nama mitra penyedia.
])
#step("2", "Memilih Bintang Rating", [
  Pada kolom rating menu terkait, pilih nilai bintang dari *1 (Kurang)* hingga *5 (Sangat Baik)*.
])
#step("3", "Menyimpan Rating", [
  Klik tombol *Simpan rating*. Rating Anda langsung tercatat dan memperbarui skor kepuasan agregat menu.
])

#screenshot(
  "screenshots/panduan/resident/10-catering-daftar-dan-form-rating.png",
  [Daftar Menu Katering Harian Dilengkapi Formulir Penilaian Bintang],
)

#tip[
  Jendela pemberian rating dibuka tepat saat waktu hidangan tiba (`served_at`) dan ditutup pada pukul 23.59 WIB
  pada hari yang sama. Pastikan Anda memberikan penilaian sebelum waktu penutupan harian berakhir.
]

#screenshot(
  "screenshots/panduan/resident/11-catering-rating-tersimpan.png",
  [Penilaian Rating Berhasil Tersimpan dan Berkontribusi pada Evaluasi Katering],
)

#pagebreak()

// ==================== BAB 6: IZIN KELUAR ====================
= Permohonan Izin Keluar Asrama (Leave Permits)

Penghuni yang berencana meninggalkan asrama di luar jam operasional atau menginap di luar wajib mengajukan
izin keluar melalui bagian *Izin keluar* di rute `/resident/room`.

== Ketentuan Pengajuan Izin
1. *Ketepatan Waktu*: Waktu rencana kembali (`returns_at`) wajib ditentukan sesudah waktu rencana keluar (`leaves_at`).
2. *Alasan yang Jelas*: Cantumkan keterangan yang akurat
   (contoh: *Pulang kampung libur semester*, *Tugas dinas lapangan*).

== Langkah Pengajuan Izin
#step("1", "Mengisi Form Izin", [
  Isi waktu keluar, estimasi waktu kembali, dan ketik alasan bepergian.
])
#step("2", "Kirim Permohonan", [
  Klik tombol *Ajukan izin*. Permohonan akan berstatus *Diajukan* (`pending`).
])

#screenshot(
  "screenshots/panduan/resident/12-izin-keluar-pengajuan-berhasil.png",
  [Permohonan Izin Keluar Berhasil Diajukan dan Menunggu Peninjauan Admin],
)

== Menyunting Alasan Izin Sebelum Ditinjau
Selama status permohonan masih *Diajukan*, Anda dapat mengubah alasan apabila terjadi revisi rencana dengan
mengklik tombol *Edit*, memperbarui teks alasan, lalu mengklik *Simpan perubahan izin*.

#screenshot(
  "screenshots/panduan/resident/13-izin-keluar-edit-alasan.png",
  [Fasilitas Penyuntingan Alasan Permohonan Selama Status Masih Pending],
)

== Memantau Keputusan Admin (Disetujui / Ditolak)
- *Disetujui*: Jika permohonan diterima pengurus asrama, status akan bertuliskan *Disetujui* dengan lencana hijau.
  Tombol edit akan dikunci secara otomatis.
- *Ditolak*: Jika alasan tidak memenuhi ketentuan, status bertuliskan *Ditolak* dengan lencana merah.

#screenshot(
  "screenshots/panduan/resident/14-izin-keluar-status-disetujui.png",
  [Permohonan Izin Keluar Telah Resmi Disetujui oleh Administrator],
)

#screenshot(
  "screenshots/panduan/resident/15-izin-keluar-status-ditolak.png",
  [Status Permohonan Izin Keluar yang Ditolak oleh Administrator],
)

#pagebreak()

// ==================== BAB 7: KELUHAN FASILITAS ====================
= Penyampaian Keluhan Fasilitas Asrama (Complaints)

Kenyamanan hunian adalah prioritas pengelola asrama. Apabila terjadi kendala pada fasilitas fisik (kelistrikan,
saluran air, pintu/jendela, kebersihan bersama), sampaikan laporan melalui bagian *Keluhan asrama* di `/resident/room`.

== Tata Cara Melaporkan Kerusakan
#step("1", "Menulis Judul Keluhan", [
  Ketikkan judul yang spesifik pada kolom *Judul keluhan* (contoh: *Lampu kamar mandi lantai 4 padam*).
])
#step("2", "Menjelaskan Detail Kendala", [
  Deskripsikan lokasi persis kendala dan kronologinya pada kolom *Isi keluhan*.
])
#step("3", "Mengirimkan Laporan", [
  Klik tombol *Kirim keluhan*. Laporan akan berstatus awal *Belum diselesaikan*.
])

#screenshot(
  "screenshots/panduan/resident/16-keluhan-formulir-pengajuan.png",
  [Formulir Pengajuan Laporan Keluhan Kerusakan Fasilitas Asrama],
)

== Pelacakan Hingga Status Resolved
Petugas teknisi asrama akan mengecek lokasi dan melakukan perbaikan fisik. Setelah selesai, administrator akan
menandai keluhan sebagai *Resolved* (*Sudah ditangani*).

#screenshot(
  "screenshots/panduan/resident/17-keluhan-status-resolved.png",
  [Pemberitahuan Status Keluhan Fasilitas yang Berhasil Diselesaikan (Resolved)],
)

#pagebreak()

// ==================== BAB 8: PANDUAN KENDALA ====================
= Panduan Penanganan Kendala & FAQ Penghuni

#table(
  columns: (1.5fr, 1.8fr, 2.7fr),
  fill: (col, row) => if row == 0 { rgb("#f1f5f9") } else { none },
  stroke: 0.5pt + rgb("#cbd5e1"),
  inset: 6pt,
  [*Pertanyaan / Masalah*], [*Penyebab Umum*], [*Solusi Mandiri Penghuni*],
  [Tidak bisa klik tombol *Check-in*],
  [Admin belum menjalankan alokasi otomatis atau profil Anda belum lengkap.],
  [Hubungi admin asrama untuk memastikan data jenis kelamin dan agama Anda sudah terisi.],
  [Tombol rating katering tidak muncul / terkunci],
  [Waktu santap belum dimulai atau tanggal sajian telah lewat batas pukul 23.59 WIB.],
  [Pastikan mengisi penilaian pada hari menu disajikan sebelum pukul 23.59 WIB.],
  [Tidak bisa mengedit permohonan izin keluar],
  [Permohonan telah diproses oleh admin (status Disetujui atau Ditolak).],
  [Permohonan yang telah diputuskan tidak dapat diedit kembali. Buat permohonan baru jika diperlukan.],
  [Keluar notifikasi *419 Page Expired*],
  [Sesi login kedaluwarsa karena tidak ada aktivitas cukup lama.],
  [Cukup muat ulang halaman (tekan F5 / Refresh). Sistem secara cerdas memperbarui token CSRF.]
)

= Glosarium Istilah Penting Penghuni

- *Check-in*: Prosedur pencatatan kehadiran resmi penghuni di kamar asrama yang telah ditentukan.
- *Check-out*: Prosedur penyelesaian masa tinggal yang membebaskan slot ranjang untuk penghuni berikutnya.
- *Stay History*: Rekam jejak seluruh masa tinggal yang pernah Anda jalani selama menempati asrama.
- *Pending*: Status permohonan yang telah masuk ke antrean sistem dan sedang menunggu peninjauan pengurus.
- *Resolved*: Status laporan kerusakan fasilitas yang telah selesai diperbaiki secara fisik oleh teknisi.
