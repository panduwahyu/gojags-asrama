#set document(
  title: "Panduan Pengguna Administrator — Gojags Asrama",
  author: "Tim Pengembang Gojags Asrama",
)

#set page(
  paper: "a4",
  margin: (x: 2cm, top: 2.5cm, bottom: 2.2cm),
  header: align(right)[
    #text(size: 8.5pt, fill: rgb("#64748b"))[Gojags Asrama • Panduan Administrator]
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
  [#text(weight: "bold", fill: rgb("#1d4ed8"))[Tips Efisiensi:] #body]
)

#let warning(body) = block(
  fill: rgb("#fffbeb"),
  stroke: (left: 3pt + rgb("#d97706")),
  radius: (right: 4pt),
  inset: (x: 10pt, y: 8pt),
  width: 100%,
  below: 0.8em,
  [#text(weight: "bold", fill: rgb("#b45309"))[Peringatan Operasional:] #body]
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
      Buku Panduan Administrator
    ]
    #v(0.5em)
    #text(size: 13pt, fill: rgb("#2563eb"), weight: "medium")[
      Gojags Asrama — Edisi Standar Operasional Prosedur (SOP)
    ]

    #v(1.5cm)
    #line(length: 40%, stroke: 1.5pt + rgb("#cbd5e1"))
    #v(1.5cm)

    #text(size: 10pt, fill: rgb("#64748b"))[
      Dokumentasi Lengkap Alur Kerja: Manajemen Kamar, Alokasi Cerdas, \
      Layanan Laundry, Katering, Izin Keluar, dan Penanganan Fasilitas
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
          #text(weight: "bold", size: 9pt)[Verifikasi Penjaminan Mutu:] \
          #text(size: 9pt)[Suite E2E Playwright (15/15 Passed)] \
          #text(size: 9pt)[Hak Akses: Administrator (`admin`)]
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

// ==================== BAB 1: GAMBARAN UMUM ====================
= Gambaran Umum & Arsitektur Keamanan

Sistem *Gojags Asrama* dirancang dengan arsitektur modern memisahkan backend Laravel 10 (REST API)
dan frontend Nuxt 4 (Single Page Application). Seluruh komunikasi data dilindungi oleh sesi berbasis
cookie terenkripsi, proteksi token CSRF otomatis, serta pembatasan peran (*Role-Based Access Control*).

== Matriks Peran & Hak Akses Pengguna

#table(
  columns: (1.2fr, 2fr, 2.8fr),
  fill: (col, row) => if row == 0 { rgb("#f1f5f9") } else { none },
  stroke: 0.5pt + rgb("#cbd5e1"),
  inset: 6pt,
  align: (col, row) => if row == 0 { center + horizon } else { left + horizon },
  [*Peran*], [*Ruang Lingkup*], [*Hak Akses & Batasan*],
  [Administrator (`admin`)],
  [Seluruh Gedung & Modul Asrama],
  [Akses penuh `/admin/*`: monitoring kamar, eksekusi alokasi otomatis, persetujuan izin keluar,
   penyelesaian keluhan, penentuan tarif laundry, dan manajemen menu katering.],
  [Penghuni (`resident`)],
  [Kamar & Layanan Pribadi],
  [Akses portal mandiri `/resident/*`: melihat alokasi ranjang, check-in/out, pengajuan laundry,
   penilaian katering, pengajuan izin keluar, dan pelaporan kerusakan.]
)

== Prosedur Akses Masuk (Login)

#step("1", "Membuka Halaman Login", [
  Arahkan peramban ke alamat `http://127.0.0.1:3000/login`. Pastikan antarmuka formulir telah tampil sempurna.
])

#step("2", "Memasukkan Kredensial Administrator", [
  Ketikkan alamat email dinas `admin@example.id` dan kata sandi `admin123`.
])

#step("3", "Menekan Tombol Masuk", [
  Klik tombol *Masuk*. Sistem melakukan autentikasi dengan verifikasi kata sandi `bcrypt` serta mencatat sesi.
])

#warning[
  Sistem dilengkapi dengan *Rate Limiter* ketat. Apabila terjadi kegagalan login sebanyak 5 kali berturut-turut
  dalam durasi 1 menit, sistem otomatis mengunci akses login untuk sementara waktu (HTTP 429 Too Many Requests).
]

#screenshot(
  "screenshots/panduan/admin/01-login-admin-light.png",
  [Tampilan Halaman Autentikasi Administrator (Mode Siang)],
)

#screenshot(
  "screenshots/panduan/admin/02-login-admin-dark.png",
  [Tampilan Halaman Autentikasi Administrator dengan Mode Malam Aktif],
)

#pagebreak()

// ==================== BAB 2: DASHBOARD ====================
= Dashboard & Pemantauan Operasional

Dashboard admin (`/admin`) merupakan pusat kendali harian. Pada halaman ini, administrator dapat langsung
melihat kondisi menyeluruh asrama tanpa perlu berpindah-pindah menu.

== Kartu Metrik Operasional Utama
1. *Total Kamar*: Menampilkan total kamar yang terdaftar di seluruh lantai beserta status kapasitasnya.
2. *Pesanan Laundry*: Menunjukkan jumlah pesanan cucian yang sedang menunggu pengerjaan (`pending`).
3. *Jadwal Catering*: Menginformasikan menu makanan yang terjadwal aktif pada hari berjalan.

#screenshot(
  "screenshots/panduan/admin/03-dashboard-admin-overview.png",
  [Dashboard Administrator Menyajikan Ringkasan Metrik Terpadu],
)

#screenshot(
  "screenshots/panduan/admin/04-dashboard-admin-dark.png",
  [Tampilan Dashboard Administrator pada Mode Gelap (Dark Mode)],
)

#tip[
  Pengaturan tema siang/malam tersimpan secara persisten pada *local storage* peramban. Pengurus yang bertugas
  pada malam hari disarankan mengaktifkan mode gelap demi kenyamanan visual.
]

#pagebreak()

// ==================== BAB 3: KAMAR & ALOKASI ====================
= Manajemen Kamar, Profil Penghuni, & Alokasi Otomatis

Manajemen kamar merupakan inti dari sistem operasional asrama, dapat diakses pada menu *Kamar* (`/admin/rooms`).

== Struktur Gedung & Konsep Okupansi Kamar
Gedung asrama terdiri dari tiga lantai operasional: Lantai 5, Lantai 4, dan Lantai 3. Setiap kamar memiliki
kapasitas 2 slot kasur (Slot A dan Slot B).

#table(
  columns: (1fr, 1.2fr, 2.8fr),
  fill: (col, row) => if row == 0 { rgb("#f1f5f9") } else { none },
  stroke: 0.5pt + rgb("#cbd5e1"),
  inset: 6pt,
  [*Status Kamar*], [*Label UI*], [*Definisi Operasional*],
  [`empty`], [Kosong], [Kedua slot kamar belum ditempati maupun dialokasikan sama sekali.],
  [`assigned`], [Assigned], [Ada penghuni yang telah dialokasikan pada kamar ini namun belum melakukan check-in.],
  [`occupied`], [Terisi], [1 penghuni telah check-in dan tidak ada slot tersisa yang menunggu check-in.],
  [`full`], [Penuh], [Kedua slot terisi penuh oleh penghuni yang telah melakukan check-in.]
)

#screenshot(
  "screenshots/panduan/admin/05-manajemen-kamar-tabel.png",
  [Daftar Kamar Menyajikan Status Okupansi Real-Time per Lantai],
)

== Melengkapi Profil Penghuni Sebelum Alokasi
Algoritma alokasi hanya akan memproses penghuni yang datanya berstatus lengkap. Data yang wajib terisi adalah:
1. *Angkatan (Cohort)*: Mengelompokkan penempatan kamar satu angkatan.
2. *Jenis Kelamin (Gender)*: Memisahkan kamar pria (`male`) dan kamar wanita (`female`).
3. *Agama (Religion)*: Menjaga keselarasan dan kenyamanan teman sekamar (*roommates*).

#step("1", "Membuka Panel Profil Penghuni", [
  Pada halaman `/admin/rooms`, klik tombol accordion *Profil user untuk alokasi*.
])

#step("2", "Memperbarui Data Penghuni", [
  Lengkapi kolom agama, jenis kelamin, dan angkatan untuk user yang masih kosong, misalnya User 1.
])

#step("3", "Menyimpan Profil", [
  Klik tombol *Simpan profil User 1*. Sistem akan memvalidasi data dan menampilkan pesan konfirmasi hijau.
])

#screenshot(
  "screenshots/panduan/admin/06-profil-penghuni-edit.png",
  [Formulir Penyuntingan Profil Penghuni untuk Persyaratan Alokasi Otomatis],
)

== Algoritma Alokasi Kamar Cerdas
Alokasi otomatis (`AllocateRooms::handle()`) dijalankan dengan menekan tombol *Alokasikan kamar otomatis*.
Logika algoritma berjalan sesuai urutan berikut:
1. *Prioritas Lantai*: Alokasi memprioritaskan penempatan dari lantai atas ke bawah
   (Lantai 5 → Lantai 4 → Lantai 3).
2. *Aturan Pasangan Kamar (Roommate Pairing)*: Penghuni yang berbagi satu kamar wajib memiliki gender yang sama
   serta agama yang sama.
3. *Pengisian Kamar Sebagian*: Kamar yang sudah terisi 1 orang akan diprioritaskan untuk dilengkapi sebelum membuka
   kamar kosong baru.
4. *Pencegahan Deadlock Basis Data*: Transaksi alokasi menerapkan penguncian pesimistik berurutan:
   `rooms` → `users` → `room_slots`.

#screenshot(
  "screenshots/panduan/admin/07-alokasi-kamar-berhasil.png",
  [Hasil Eksekusi Alokasi Kamar Otomatis Dilengkapi Rincian Penempatan],
)

#pagebreak()

// ==================== BAB 4: LAUNDRY ====================
= Manajemen Layanan Laundry

Pengelolaan cuci pakaian dilakukan di rute `/admin/laundry`. Modul ini mengelola siklus dari pakaian diserahkan
hingga selesai dicuci dan siap diambil oleh penghuni.

== Alur Transisi Status Laundry
#align(center)[
  `Diajukan (pending)` $arrow.r$ `Dikerjakan (proses)` $arrow.r$ `Selesai (done)`
]

== Prosedur Penanganan Cucian
#step("1", "Memeriksa Antrean Masuk", [
  Buka halaman `/admin/laundry`. Tabel akan menampilkan cucian baru yang dikirimkan penghuni, termasuk
  nama penghuni, deskripsi pakaian, serta jumlah stel yang diserahkan.
])

#screenshot(
  "screenshots/panduan/admin/08-laundry-antrean-masuk.png",
  [Antrean Permohonan Cuci Pakaian Masuk dari Penghuni],
)

#step("2", "Memperbarui Status Menjadi Selesai & Input Biaya", [
  Setelah cucian selesai dicuci dan disetrika:
  1. Pilih opsi status *Selesai* (`done`) pada baris pesanan terkait.
  2. Masukkan nominal biaya resmi pada kolom input biaya, contoh: `30000`.
  3. Klik tombol *Simpan*.
])

#screenshot(
  "screenshots/panduan/admin/09-laundry-selesai-dengan-biaya.png",
  [Penyelesaian Pesanan Laundry Beserta Rincian Biaya Layanan],
)

#note[
  Setelah status disimpan menjadi `Selesai`, penghuni dapat langsung melihat rincian biaya pada aplikasi mereka
  sehingga dapat menyelesaikan administrasi pembayaran saat pengambilan pakaian.
]

#pagebreak()

// ==================== BAB 5: CATERING ====================
= Manajemen Menu & Jadwal Catering

Admin bertanggung jawab mengelola jadwal konsumsi harian penghuni di `/admin/catering` untuk tiga jadwal:
sarapan (*breakfast*), makan siang (*lunch*), dan makan malam (*dinner*).

== Menambahkan Menu Konsumsi Baru
#step("1", "Mengisi Formulir Menu", [
  Ketikkan nomor menu, nama makanan (misal: *Ayam Bakar Madu*), pilih waktu makan (*lunch*), nama mitra
  penyedia catering, dan jadwal saji (WIB).
])

#step("2", "Menyimpan Menu", [
  Tekan tombol *Tambah menu*. Menu akan otomatis masuk ke daftar makanan aktif hari tersebut.
])

#screenshot(
  "screenshots/panduan/admin/10-catering-tambah-menu.png",
  [Formulir Penambahan Menu Katering Harian Baru],
)

== Meninjau Evaluasi Rating Penghuni
Setiap menu yang telah disajikan dapat dinilai oleh penghuni dengan skor 1 sampai 5 bintang. Admin dapat
memantau skor kepuasan secara agregat pada tabel menu.

#screenshot(
  "screenshots/panduan/admin/11-catering-lihat-rating.png",
  [Tampilan Rata-rata Rating Kepuasan Menu dari Seluruh Penghuni],
)

== Mengedit & Menghapus Menu
- *Edit Menu*: Klik tombol *Edit* pada baris yang diinginkan, ubah data hidangan, lalu klik *Simpan perubahan*.
- *Hapus Menu*: Klik tombol *Hapus*, lalu konfirmasi dengan menekan *Ya, hapus* pada tombol konfirmasi ganda.

#screenshot(
  "screenshots/panduan/admin/12-catering-edit-menu.png",
  [Penyuntingan Informasi Menu Makanan Katering],
)

#pagebreak()

// ==================== BAB 6: IZIN KELUAR ====================
= Peninjauan Permohonan Izin Keluar (Leave Permits)

Semua permohonan izin meninggalkan gedung asrama ditinjau oleh admin pada bagian *Izin keluar* di `/admin/rooms`.

== Standar Evaluasi Izin Keluar
Admin wajib memeriksa:
1. *Rentang Waktu*: Waktu rencana kembali (`returns_at`) wajib berada setelah waktu keluar (`leaves_at`).
2. *Alasan Bepergian*: Keterangan yang dicantumkan harus jelas dan masuk akal (misal: dinas luar, keperluan keluarga).

== Menyetujui Izin Keluar
Klik tombol *Setujui* pada baris permohonan. Status akan langsung berubah menjadi `Disetujui` berwarna hijau,
dan sistem mencatat ID administrator yang menyetujui beserta waktu persetujuan.

#screenshot(
  "screenshots/panduan/admin/13-izin-keluar-persetujuan.png",
  [Persetujuan Permohonan Izin Keluar Penghuni Asrama],
)

== Menolak Izin Keluar
Apabila permohonan tidak sesuai tata tertib atau berisiko, admin menekan tombol *Tolak*. Status berubah
menjadi `Ditolak` berwarna merah.

#screenshot(
  "screenshots/panduan/admin/14-izin-keluar-penolakan.png",
  [Penolakan Permohonan Izin Keluar yang Melanggar Ketentuan],
)

#pagebreak()

// ==================== BAB 7: KELUHAN FASILITAS ====================
= Penanganan Keluhan Fasilitas Asrama (Complaints)

Laporan kerusakan fasilitas fisik (lampu mati, keran bocor, AC rusak, kendala kebersihan) ditampung pada bagian
*Keluhan asrama* di `/admin/rooms`.

== Prosedur Penyelesaian Keluhan
#step("1", "Memeriksa Detail Keluhan", [
  Baca judul dan isi keluhan yang dilaporkan oleh penghuni untuk menentukan unit fasilitas yang bermasalah.
])

#step("2", "Tindak Lanjut Teknis Lapangan", [
  Tugaskan teknisi atau staf kebersihan asrama untuk melakukan perbaikan fisik di lokasi kamar penghuni.
])

#step("3", "Menandai Laporan Selesai (Resolved)", [
  Setelah perbaikan terverifikasi selesai di lapangan, klik tombol *Tandai Resolved*. Status berubah menjadi
  `Sudah ditangani` (`Resolved`) secara permanen.
])

#screenshot(
  "screenshots/panduan/admin/15-keluhan-penanganan-selesai.png",
  [Penyelesaian Keluhan Kerusakan Fasilitas Asrama Menjadi Resolved],
)

#pagebreak()

// ==================== BAB 8: PANDUAN MASALAH ====================
= Panduan Penanganan Masalah & FAQ (Troubleshooting)

#table(
  columns: (1.5fr, 1.8fr, 2.7fr),
  fill: (col, row) => if row == 0 { rgb("#f1f5f9") } else { none },
  stroke: 0.5pt + rgb("#cbd5e1"),
  inset: 6pt,
  [*Gejala / Masalah*], [*Penyebab Utama*], [*Tindakan Perbaikan Administrator*],
  [Muncul pesan *419 Page Expired*],
  [Sesi peramban kedaluwarsa atau token CSRF ter-reset.],
  [Muat ulang halaman (*refresh / F5*). Composable `useApi` otomatis memperbarui token CSRF.],
  [Gagal alokasi otomatis (*0 kamar dialokasikan*)],
  [Data angkatan, gender, atau agama penghuni belum lengkap.],
  [Buka accordion *Profil user untuk alokasi*, lengkapi seluruh profil yang kosong, lalu simpan ulang.],
  [Penghuni tidak bisa memberi rating katering],
  [Menu katering belum memasuki jadwal penyajian atau telah melewati batas hari (pukul 23.59 WIB).],
  [Pastikan waktu `served_at` sesuai dengan tanggal hari ini.],
  [Muncul status *429 Too Many Requests* saat login],
  [Percobaan login melebihi batas 5 kali dalam 1 menit.],
  [Tunggu 60 detik sebelum melakukan percobaan masuk berikutnya.]
)

= Glosarium Istilah Teknis Asrama

- *Room Slot*: Unit kasur tidur individual di dalam kamar (kapasitas 2 slot per kamar: Slot A dan Slot B).
- *Stay*: Rekam jejak riwayat tinggal penghuni dari saat check-in tercatat hingga saat check-out diselesaikan.
- *Room Locks*: Penguncian database terurut pesimistik untuk menjamin keutuhan data
  saat banyak user mengakses bersamaan.
- *Idempotent Action*: Operasi yang menghasilkan dampak data yang sama meskipun tombol diklik berulang kali.
