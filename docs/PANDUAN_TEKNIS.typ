#set document(
  title: "Panduan Teknis & Arsitektur Perangkat Lunak — Gojags Asrama",
  author: "Tim Pengembang Gojags Asrama",
)

#set page(
  paper: "a4",
  margin: (x: 2cm, top: 2.5cm, bottom: 2.2cm),
  header: align(right)[
    #text(size: 8.5pt, fill: rgb("#64748b"))[Gojags Asrama • Panduan Teknis & Arsitektur]
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
  [#text(weight: "bold", fill: rgb("#166534"))[Catatan Arsitektur:] #body]
)

#let tip(body) = block(
  fill: rgb("#eff6ff"),
  stroke: (left: 3pt + rgb("#2563eb")),
  radius: (right: 4pt),
  inset: (x: 10pt, y: 8pt),
  width: 100%,
  below: 0.8em,
  [#text(weight: "bold", fill: rgb("#1d4ed8"))[Praktik Terbaik:] #body]
)

#let warning(body) = block(
  fill: rgb("#fffbeb"),
  stroke: (left: 3pt + rgb("#d97706")),
  radius: (right: 4pt),
  inset: (x: 10pt, y: 8pt),
  width: 100%,
  below: 0.8em,
  [#text(weight: "bold", fill: rgb("#b45309"))[Peringatan Teknis:] #body]
)

// ==================== HALAMAN SAMPUL ====================
#page(header: none, footer: none)[
  #v(2cm)
  #align(center)[
    #block(
      fill: rgb("#0f172a"),
      radius: 6pt,
      inset: (x: 14pt, y: 6pt),
      [
        #text(size: 9pt, weight: "bold", fill: white)[
          DOKUMENTASI REKAYASA PERANGKAT LUNAK
        ]
      ]
    )

    #v(1.5cm)
    #text(size: 26pt, weight: "bold", fill: rgb("#0f172a"))[
      Buku Panduan Teknis & Arsitektur
    ]
    #v(0.5em)
    #text(size: 13pt, fill: rgb("#2563eb"), weight: "medium")[
      Gojags Asrama — Laravel 10 API + Nuxt 4 SPA + Tailwind CSS v4
    ]

    #v(1.5cm)
    #line(length: 40%, stroke: 1.5pt + rgb("#cbd5e1"))
    #v(1.5cm)

    #text(size: 10pt, fill: rgb("#64748b"))[
      Spesifikasi Skema Basis Data, Alur Transaksi Bebas Deadlock, \
      Desain API Endpoints, Otomatisasi Pengujian E2E, & Standar Kualitas
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
          #text(weight: "bold", size: 9pt)[Tim Pengembang:] \
          #text(size: 9pt)[Gojags Engineering Team] \
          #text(size: 9pt)[Status: Production Ready]
        ],
        [
          #text(weight: "bold", size: 9pt)[Cakupan Pengujian:] \
          #text(size: 9pt)[125 Backend Tests (562 assertions)] \
          #text(size: 9pt)[15 E2E Playwright Scenarios]
        ]
      )
    ]
  ]
]

#pagebreak()

// ==================== DAFTAR ISI ====================
#outline(title: "Daftar Isi", indent: 1.5em)

#pagebreak()

// ==================== BAB 1: ARSITEKTUR ====================
= Arsitektur Perangkat Lunak & Teknologi

Aplikasi *Gojags Asrama* mengadopsi pola modern pemisahan tanggung jawab (*Separation of Concerns*)
antara backend RESTful API dan frontend Single Page Application (SPA).

== Komponen Teknologi Utama (Tech Stack)
#table(
  columns: (1.5fr, 1.8fr, 2.7fr),
  fill: (col, row) => if row == 0 { rgb("#f1f5f9") } else { none },
  stroke: 0.5pt + rgb("#cbd5e1"),
  inset: 6pt,
  [*Lapisan*], [*Teknologi & Versi*], [*Peran & Fungsi*],
  [Backend Core], [Laravel 10 (PHP 8.2+)],
  [Menyediakan controller REST tipis, Actions untuk logika bisnis murni, FormRequests untuk validasi,
   dan JsonResources untuk transformasi respons.],
  [Frontend SPA], [Nuxt 4 (`ssr: false`) + Vue 3.5],
  [Menghadirkan UI reaktif berbasis Composition API dengan TypeScript strict typing dan Tailwind CSS v4.],
  [Database Engine], [MySQL 8.0],
  [Penyimpanan data relasional transaksi dengan isolasi teruji dan penguncian pesimistik.],
  [API Proxy], [Nitro Server Route],
  [Meneruskan request `/api/*` dari client Nuxt ke backend Laravel secara aman.],
  [Quality Suite], [Playwright + Vitest + PHPUnit + Pint],
  [Pengujian menyeluruh mulai dari unit backend, unit frontend, hingga integrasi visual E2E.]
)

#pagebreak()

// ==================== BAB 2: BASIS DATA ====================
= Skema & Relasi Basis Data (Database Schema)

Seluruh struktur basis data terdokumentasi dalam format DBML (`dbdiagram.txt`) serta dikelola melalui migrasi
Laravel di `database/migrations/`. Semua stempel waktu disimpan dalam standar UTC.

== Daftar Tabel & Fungsi Domain
1. *users*: Menyimpan kredensial (`email`, `password`), peran (`role: admin | resident`), serta profil
   penghuni (`cohort`, `gender`, `religion`).
2. *rooms*: Menyimpan nomor kamar (`number`) dan lantai (`floor: 5, 4, 3`).
3. *room_slots*: Menyimpan slot kasur (`slot_number: A | B`) yang berelasi dengan `rooms` dan `users`.
4. *stays*: Mencatat riwayat masa tinggal (`checked_in_at`, `checked_out_at`) antara pengguna dan kamar.
5. *laundry_orders*: Mencatat pesanan cuci pakaian, jumlah stel, deskripsi pakaian, status, dan biaya.
6. *catering_menus*: Mengelola jadwal konsumsi (`meal_time: breakfast | lunch | dinner`), hidangan, dan waktu saji.
7. *catering_ratings*: Menyimpan evaluasi rating kepuasan penghuni (skala 1 s.d. 5) dengan konstrain unik per menu.
8. *leave_permits*: Mengelola izin keluar asrama (`leaves_at`, `returns_at`, `reason`, `status`).
9. *complaints*: Menampung laporan kerusakan fisik fasilitas asrama dan pencatatan staf penyelesai.

#note[
  Status kamar tidak disimpan sebagai kolom statis pada tabel `rooms`. Status dihitung secara dinamis
  melalui *Eloquent accessor* dari relasi `room_slots` demi mencegah inkonsistensi data riil.
]

#pagebreak()

// ==================== BAB 3: LOGIKA BISNIS & TRANSAKSI ====================
= Logika Bisnis & Pencegahan Deadlock

Operasi alokasi kamar, check-in, dan check-out melibatkan banyak tabel relasional secara konkuren.

== Algoritma Alokasi Otomatis (`AllocateRooms::handle()`)
Alokasi memproses seluruh penghuni berstatus `resident` yang belum pernah check-out dan berprofil lengkap:
1. Mengurutkan kohort secara natural (*natural sort order*).
2. Memprioritaskan lantai dari atas ke bawah: Lantai 5 $arrow.r$ Lantai 4 $arrow.r$ Lantai 3.
3. Menjamin pasangan sekamar (*roommates*) memiliki jenis kelamin (`gender`) dan agama (`religion`) yang sama persis.
4. Memaksimalkan efisiensi dengan mengisi kamar yang terisi 1 orang terlebih dahulu sebelum membuka kamar kosong baru.

== Mekanisme Penguncian Pesimistik (`RoomLocks`)
Untuk mencegah terjadinya kebuntuan (*deadlock*) pada MySQL saat banyak penghuni melakukan check-in
atau alokasi dieksekusi bersamaan, sistem menerapkan urutan penguncian ketat:
#align(center)[
  *Urutan Kunci Pesimistik:* `rooms` $arrow.r$ `users` $arrow.r$ `room_slots`
]

#warning[
  Setiap penulisan kode transaksi mutasi kamar baru WAJIB menggunakan pembungkus `RoomLocks` agar
  tidak terjadi pelanggaran urutan kunci (*lock ordering violation*).
]

#pagebreak()

// ==================== BAB 4: PENGUJIAN KUALITAS ====================
= Rangkaian Pengujian Kualitas (Testing Suite)

Sistem Gojags Asrama mengusung pendekatan *Quality First* dengan cakupan pengujian komprehensif.

== Arsitektur Pengujian
1. *Backend PHPUnit 10*:
   - 125 test cases dengan 562 assertions tanpa kegagalan.
   - Menguji isolasi role, validasi FormRequest, integritas alokasi, rate limiting, dan migrasi seeder.
2. *Frontend Vitest*:
   - 27 unit tests memverifikasi format mata uang Rupiah, parsing tanggal WIB, sanitasi error, dan navigasi.
3. *End-to-End Playwright*:
   - 15 skenario E2E mengotomatisasi pengujian alur kerja nyata di browser Chromium.
   - Mengambil tangkapan layar visual (*screenshot capture*) yang langsung dijadikan aset buku panduan resmi.

== Perintah Pengujian Mandiri
- Menjalankan test backend: `php artisan test`
- Menjalankan unit test frontend: `npm --prefix frontend run test:unit`
- Menjalankan typecheck: `npm --prefix frontend run typecheck`
- Menjalankan format style: `./vendor/bin/pint --test`
- Menjalankan pengujian E2E & tangkap screenshot: `npm --prefix frontend run test:e2e`
- Menyusun buku panduan: `typst compile docs/PANDUAN_ADMIN.typ` (juga `PANDUAN_PENGHUNI.typ`, `PANDUAN_TEKNIS.typ`).
  Gambar diambil dari `docs/screenshots/`, yang tidak disimpan di git; jalankan `test:e2e` terlebih dahulu pada klon baru.

#tip[
  Semua berkas kode PHP, Vue, dan TypeScript mematuhi aturan batas panjang baris maksimal 120 karakter.
]
