# Skema target

Satu migrasi `create_<tabel>_table` per tabel, tanpa menyisipkan data (inventaris kamar ada di
`RoomSeeder`). Semua tabel memiliki `id` dan `timestamps()`. Semua waktu disimpan dalam **UTC**.
Tabel bawaan Laravel (`sessions`, `cache`, `jobs`, `password_reset_tokens`) tidak berubah.

## users

| Kolom | Tipe | Catatan |
|---|---|---|
| name, email (unik), password | string | bawaan Laravel |
| role | string(20), default `resident` | enum `Role`: `admin`, `resident` |
| cohort | string(100), nullable | angkatan; disimpan ternormalisasi (lihat `domain.md`) |
| gender | string(10), nullable | enum `Gender`: `male`, `female` |
| religion | string(50), nullable | agama; disimpan ternormalisasi |

## rooms

| Kolom | Tipe | Catatan |
|---|---|---|
| number | string(10), unik | `301` … `526` |
| floor | unsignedTinyInteger, index | 3, 4, atau 5 |

Tidak ada kolom `status` — status dihitung dari `room_slots`.

## room_slots

| Kolom | Tipe | Catatan |
|---|---|---|
| room_id | FK rooms, cascadeOnDelete | |
| position | unsignedTinyInteger | 1 atau 2 |
| user_id | FK users, nullable, **unik**, nullOnDelete | penghuni slot; satu user maksimal satu slot |
| checked_in_at | timestamp, nullable | |

Unik: `(room_id, position)`.

## stays

Riwayat menginap. Menyimpan salinan nama agar riwayat tetap terbaca bila user/kamar dihapus.

| Kolom | Tipe | Catatan |
|---|---|---|
| user_id | FK users, nullable, nullOnDelete | |
| room_id | FK rooms, nullable, nullOnDelete | |
| user_name, room_number | string | salinan saat check-in |
| floor | unsignedTinyInteger | salinan saat check-in |
| checked_in_at | timestamp | |
| checked_out_at | timestamp, nullable | |

Index: `(user_id, checked_out_at)`.

## laundry_orders

| Kolom | Tipe | Catatan |
|---|---|---|
| user_id | FK users, cascadeOnDelete | pemesan |
| items | string(255) | deskripsi pakaian/barang |
| set_count | unsignedInteger | jumlah stel |
| status | string(20), default `pending` | enum `LaundryStatus`: `pending`, `done` |
| cost | decimal(12,2), nullable | wajib saat `done` |

Tanggal pengajuan = `created_at` (tidak ada kolom `date` terpisah). Index: `(user_id, status)`.

## catering_menus

| Kolom | Tipe | Catatan |
|---|---|---|
| menu_number | unsignedInteger | ditampilkan sebagai "Menu 3" |
| provider | string(255) | penyedia |
| served_at | dateTime | waktu catering |
| status | string(20), default `scheduled` | enum `CateringStatus`: `scheduled`, `preparing`, `done` |

## catering_ratings

| Kolom | Tipe | Catatan |
|---|---|---|
| catering_menu_id | FK catering_menus, cascadeOnDelete | |
| user_id | FK users, cascadeOnDelete | |
| rating | unsignedTinyInteger | 1–5 |

Unik: `(catering_menu_id, user_id)`.

## leave_permits

| Kolom | Tipe | Catatan |
|---|---|---|
| user_id | FK users, cascadeOnDelete | pemohon |
| leaves_at, returns_at | dateTime | `returns_at` > `leaves_at` |
| reason | text | |
| status | string(20), default `pending` | enum `PermitStatus`: `pending`, `approved`, `rejected` |
| reviewed_by | FK users, nullable, nullOnDelete | admin |
| reviewed_at | timestamp, nullable | |

Index: `(user_id, status)`.

## complaints

| Kolom | Tipe | Catatan |
|---|---|---|
| user_id | FK users, cascadeOnDelete | pelapor |
| title | string(150) | |
| body | text | |
| status | string(20), default `pending` | enum `ComplaintStatus`: `pending`, `resolved` |
| resolved_by | FK users, nullable, nullOnDelete | admin |
| resolved_at | timestamp, nullable | |

Index: `(user_id, status)`.

## Enum dan label

| Enum | Nilai → label |
|---|---|
| `Role` | `admin` → Admin · `resident` → Penghuni |
| `Gender` | `male` → Laki-laki · `female` → Perempuan |
| `RoomStatus` (dihitung) | `empty` → Kosong · `assigned` → Assigned · `occupied` → Terisi · `full` → Penuh |
| `LaundryStatus` | `pending` → Diajukan · `done` → Selesai |
| `CateringStatus` | `scheduled` → Terjadwal · `preparing` → Disiapkan · `done` → Selesai |
| `PermitStatus` | `pending` → Diajukan · `approved` → Disetujui · `rejected` → Ditolak |
| `ComplaintStatus` | `pending` → Diajukan · `resolved` → Resolved |

## Relasi model

- `User` hasOne `RoomSlot`; hasMany `Stay`, `LaundryOrder`, `LeavePermit`, `Complaint`, `CateringRating`
- `Room` hasMany `RoomSlot` (`slots`), hasMany `Stay`
- `RoomSlot` belongsTo `Room`, belongsTo `User`
- `CateringMenu` hasMany `CateringRating` (`ratings`)
- `LeavePermit` belongsTo `User` (`user`), belongsTo `User` (`reviewer`, FK `reviewed_by`)
- `Complaint` belongsTo `User` (`user`), belongsTo `User` (`resolver`, FK `resolved_by`)
