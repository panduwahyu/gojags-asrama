---
name: gojags-asrama-architecture
description: Arsitektur TARGET (struktur yang seharusnya) untuk repo gojags-asrama — Laravel 10 API + Nuxt 4. Gunakan skill ini SETIAP KALI menulis, me-rewrite, merapikan, me-refactor, me-review, atau menambah fitur di repo ini (kamar, alokasi, check-in/out, laundry, catering, izin keluar, keluhan, login, tabel, komponen Vue, controller, model, migrasi, rute, test), juga saat pengguna bilang kodenya "kotor", "berantakan", "tidak readable", "rapikan", "rewrite", "struktur yang benar", atau bertanya "sebaiknya ditaruh di mana". Kode yang ada sekarang BUKAN contoh yang boleh ditiru; skill ini adalah sumber kebenaran untuk struktur, penamaan, skema, dan kontrak API.
---

# Arsitektur target Gojags Asrama

Repo ini sedang di-rewrite. Kode lama berfungsi tetapi sulit dibaca, jadi **jangan meniru pola kode yang
sudah ada** — tiru pola di skill ini. Kode lama hanya dipakai sebagai sumber *perilaku* (aturan bisnis),
bukan sumber *struktur*.

Tujuan tunggal: orang yang baru membuka repo bisa menemukan dan memahami satu fitur dalam beberapa menit.
Setiap aturan di bawah melayani tujuan itu; kalau suatu saat aturan terasa bertentangan dengan tujuan
tersebut, menangkan tujuannya dan jelaskan alasannya kepada pengguna.

## Berkas referensi

Baca sesuai kebutuhan — tidak perlu semuanya sekaligus.

| Berkas | Baca ketika |
|---|---|
| `references/backend.md` | Menulis apa pun di `app/`, `routes/`, `database/`, `tests/` |
| `references/frontend.md` | Menulis apa pun di `frontend/` |
| `references/domain.md` | Menyentuh logika fitur — berisi aturan bisnis dan teks pesan yang wajib bertahan |
| `references/schema.md` | Menulis migrasi, model, factory, atau Resource |
| `references/testing.md` | Menulis atau menjalankan tes apa pun (PHPUnit, Vitest, Playwright tiruan, Playwright E2E) |
| `references/migration-map.md` | Memindahkan kode lama: peta nama lama → baru dan daftar yang dihapus |

## Keputusan yang sudah diambil

Ini sudah diputuskan bersama pemilik proyek; jangan dibuka ulang tanpa diminta.

1. **Laravel hanya API JSON, Nuxt satu-satunya antarmuka.** Blade, `public/css`, `public/js`, dan cabang
   `expectsJson()` dihapus. Dua antarmuka di atas controller yang sama adalah sumber kekusutan terbesar.
2. **Eloquent model**, bukan `DB::table()`. Relasi, cast, dan scope menggantikan join dan konversi manual.
3. **Identifier berbahasa Inggris, teks yang dibaca pengguna berbahasa Indonesia.** Tabel, kolom, kelas,
   variabel, URL, nama berkas: Inggris mengikuti konvensi Laravel/Nuxt (sehingga framework bekerja tanpa
   konfigurasi). Label, pesan validasi, judul halaman: Indonesia. Tidak ada campuran seperti
   `waktu_keluar` di samping `checked_in_at`.
4. **Skema dan kontrak API didesain ulang** (lihat `schema.md`, `backend.md`). Data lama tidak dimigrasikan;
   basis data dibangun ulang dengan `php artisan migrate:fresh --seed`.
5. **Autentikasi tetap sesi cookie + CSRF lewat proxy Nuxt.** Tanpa Sanctum/token.
6. **Peran bernama `admin` dan `resident`** (bukan `user`, agar tidak rancu dengan model `User`).

## Struktur target

```
app/
  Enums/              Role, Gender, RoomStatus, LaundryStatus, CateringStatus, PermitStatus, ComplaintStatus
  Models/             User, Room, RoomSlot, Stay, LaundryOrder, CateringMenu, CateringRating,
                      LeavePermit, Complaint
  Models/Concerns/    VisibleToOwner
  Actions/            AllocateRooms, CheckIn, CheckOut, UpdateResidentProfile
  Support/            TableQuery, DateFormat, RoomLocks, RoomOccupancy
  Http/
    Controllers/      SessionController, DashboardController, RoomController, RoomAllocationController,
                      ResidentController, MyRoomController, LaundryOrderController,
                      CateringMenuController, CateringRatingController, LeavePermitController,
                      ComplaintController
    Requests/         satu FormRequest per aksi tulis (StoreLeavePermitRequest, ReviewLeavePermitRequest, …)
    Resources/        satu Resource per model yang dikirim ke klien
    Middleware/       RequireRole
config/asrama.php     lantai, kamar per lantai, kapasitas, zona waktu tampilan, pilihan per_page
routes/api.php        SATU daftar rute; web.php hanya berisi /up
database/
  migrations/         satu migrasi "create" per tabel, tanpa data
  factories/          satu factory per model
  seeders/            RoomSeeder (78 kamar), AccountSeeder (akun demo), DatabaseSeeder
tests/Unit/           logika murni (RoomStatusTest)
tests/Feature/        satu berkas per fitur: LeavePermitTest, RoomAllocationTest, …
.env.e2e              lingkungan Laravel khusus E2E (basis data berakhiran _e2e)

frontend/
  server/routes/api/[...path].ts     proxy ke Laravel
  app/
    pages/            login.vue, admin/{index,rooms,laundry,catering}.vue,
                      resident/{index,room,laundry,catering}.vue
    components/
      ui/             DataTable, StatusBadge, AlertMessage, PageHeader, BerakhlakLogo,
                      SortableHeader, TablePagination, TableSearch
      layout/         AppSidebar, AppHeader, AppFooter, ThemeToggle, AppIcon
      auth/ dashboard/   komponen khusus halaman login & dasbor
      leave-permit/   LeavePermitSection, LeavePermitForm, LeavePermitTable
      complaint/ laundry/ catering/ room/     pola yang sama
    composables/      useSession, useApi, useTable, useAction, usePolling
    types/            satu berkas per fitur + api.ts (Paginated<T>, ApiMessage)
    utils/            datetime.ts, currency.ts, errors.ts, navigation.ts

    middleware/       auth.global.ts
  playwright.config.ts / playwright.e2e.config.ts / vitest.config.ts
  scripts/            smoke.mjs, reset-e2e-db.mjs
  tests/
    unit/             Vitest untuk utils/
    browser/          Playwright dengan API ditiru — satu spec per fitur
    e2e/              Playwright menembus Laravel — satu spec per alur utama
    support/          shared.ts, fixtures.ts, mock-api.ts, samples.ts
```

Sebuah fitur selalu berbentuk sama. Untuk "izin keluar":
`Enums/PermitStatus` → `Models/LeavePermit` → `Requests/*LeavePermitRequest` → `Resources/LeavePermitResource`
→ `Controllers/LeavePermitController` → baris di `routes/api.php` → `tests/Feature/LeavePermitTest` →
`types/leave-permit.ts` → `components/leave-permit/*` → dipasang di halaman. Kalau seseorang tahu letak
satu fitur, ia tahu letak semua fitur. Itu sebabnya **tidak ada controller/halaman generik** yang
menerima nama tabel sebagai parameter.

## Aturan keterbacaan

Tiap aturan disertai alasannya, supaya bisa dinilai dengan akal sehat pada kasus yang tidak tercakup.

**Satu berkas, satu tanggung jawab.** Kode lama menaruh login, dashboard, kamar, dan laundry dalam satu
method 60 baris. Pembaca harus bisa menebak isi berkas dari namanya.

**Controller tipis.** Method controller berbentuk: ambil input tervalidasi → panggil model/Action →
kembalikan Resource. Biasanya 3–10 baris. Tanpa `validate()` inline, tanpa SQL mentah, tanpa format
tanggal. Validasi di FormRequest, bentuk JSON di Resource, query di scope model, proses multi-langkah
di Action.

**Action hanya bila memang perlu.** Buat kelas di `Actions/` hanya untuk proses yang butuh transaksi,
kunci baris, atau menyentuh lebih dari satu model (alokasi, check-in/out). CRUD sederhana cukup di
controller — lapisan tambahan tanpa isi justru mengurangi keterbacaan.

**Status adalah enum.** String seperti `'Diajukan'` yang tersebar di controller, tes, dan template mudah
salah ketik dan sulit dicari. Enum menyimpan nilai (`pending`) dan `label()` Indonesia (`Diajukan`) di
satu tempat.

**Nilai turunan dihitung, bukan disalin.** Status kamar diturunkan dari slot; tidak ada kolom
`rooms.status` yang harus disinkronkan manual.

**Tidak ada logika yang disalin antar-fitur.** Parameter tabel, pola `busy/error/success`, polling, dan
format tanggal WIB masing-masing hidup di tepat satu tempat (`TableQuery`, `useAction`, `usePolling`,
`utils/datetime.ts`). Jika hendak menulis hal yang sama untuk kedua kalinya, pindahkan ke tempat bersama.

**Baris maksimal 120 karakter; satu pernyataan per baris.** Kode lama memiliki baris >700 karakter dan
`if (...) throw ...;` sebaris. Di template Vue: satu elemen per baris, dan elemen dengan lebih dari dua
atribut memecah atributnya satu per baris.

**Kelas Tailwind yang berulang menjadi komponen `ui/` atau kelas di `main.css`.** Daftar kelas panjang
yang disalin di tiap tabel dan alert menenggelamkan struktur halaman.

**Nama menjelaskan diri.** `$permit`, `$room`, `leavesAt` — bukan `$row`, `$data`, `$q`, `e`. Komentar
hanya untuk *mengapa* (mis. urutan kunci), bukan *apa*.

**Angka ajaib masuk `config/asrama.php`.** Lantai `[5, 4, 3]`, kapasitas `2`, `per_page` `[10, 50, 100]`,
interval polling.

## Alur kerja rewrite

Kerjakan per fitur secara vertikal (backend → tes → frontend), bukan per lapisan. Dengan begitu aplikasi
selalu bisa dijalankan dan tiap langkah bisa ditinjau sendiri.

1. **Fondasi** (sekali): `config/asrama.php`, enum, migrasi + model + factory semua tabel, seeder,
   `TableQuery`, `RequireRole`, `SessionController`, kerangka `routes/api.php`; di frontend: proxy,
   `useSession`, `useApi`, `useTable`, `useAction`, `usePolling`, komponen `ui/`, layout, login.
2. **Per fitur**, urutan yang disarankan: kamar & alokasi → check-in/out → izin keluar → keluhan →
   laundry → catering → dashboard. Untuk tiap fitur:
   1. Baca bagian fitur itu di `domain.md` dan kode lamanya; catat setiap aturan dan pesan.
   2. Tulis ulang feature test-nya terhadap kontrak baru (lihat `testing.md`). Tes lama adalah
      daftar perilaku yang harus tetap tercakup.
   3. Tulis backend sampai tes hijau, lalu `vendor/bin/pint`.
   4. Tulis frontend beserta spec Playwright tiruannya, lalu `npm run typecheck` dan
      `npm run test:browser`. Tambah spec E2E bila fitur punya alur lintas peran.
3. **Pembersihan**: hapus semua yang ada di daftar "dihapus" pada `migration-map.md`, lalu perbarui
   `CLAUDE.md`, `AGENTS.md`, dan `dbdiagram.txt` agar menggambarkan struktur baru.

Jika pengguna hanya meminta satu bagian (mis. "rapikan komponen izin keluar") padahal fondasinya belum
ada, buat bagian fondasi yang dibutuhkan saja dan sebutkan apa yang dibuat — jangan menulis ulang
seluruh aplikasi tanpa diminta.

## Menambah fitur baru

Ikuti bentuk fitur di atas dari enum sampai komponen. Jangan menambah cabang `if` ke controller atau
komponen fitur lain, dan jangan membuat abstraksi baru hanya untuk satu pemakai.

## Definisi selesai

Sebelum menyatakan satu fitur selesai, periksa:

- [ ] Setiap aturan dan pesan fitur itu di `domain.md` tercakup oleh tes.
- [ ] `composer test` hijau; `vendor/bin/pint --test` bersih.
- [ ] `npm run typecheck`, `npm run test:unit`, dan `npm run test:browser` bersih.
- [ ] `npm run test:e2e` lulus (butuh PHP + basis data `*_e2e`; lihat `testing.md` § 7).
- [ ] Daftar "Minimum per fitur" di `testing.md` § 10 terpenuhi.
- [ ] Tidak ada `DB::table`, `expectsJson`, `view(`, atau `validate(` inline di kode baru.
- [ ] Tidak ada string status mentah di luar enum, dan tidak ada baris >120 karakter.
- [ ] Kode lama fitur tersebut sudah dihapus, bukan dibiarkan berdampingan.

Laporkan dengan jujur bila ada langkah yang tidak bisa dijalankan (mis. basis data E2E tidak tersedia) — jangan menyatakan lulus tanpa menjalankannya.
