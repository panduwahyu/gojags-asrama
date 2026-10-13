# Peta lama → baru

Dipakai saat memindahkan kode lama. Kolom kiri adalah yang ada di repo sekarang; kolom kanan adalah
targetnya.

## Tabel dan kolom

| Lama | Baru |
|---|---|
| `users.role` = `user` | `users.role` = `resident` |
| `users.angkatan` / `gender` / `agama` | `users.cohort` / `gender` (`male`/`female`) / `religion` |
| `kamar` (`name`, `lantai`, `status`, `date`) | `rooms` (`number`, `floor`) — `status` dihitung, `date` dihapus |
| `kamar_slots` (`kamar_id`, `nomor`, `user_id`, `checked_in_at`) | `room_slots` (`room_id`, `position`, `user_id`, `checked_in_at`) |
| `kamar_stays` (`kamar_id`, `user_name`, `kamar_name`, `lantai`, …) | `stays` (`room_id`, `user_name`, `room_number`, `floor`, …) |
| `laundry` (`name`, `detail`, `jumlah_stel`, `biaya`, `date`, `status`) | `laundry_orders` (`user_id`, `items`, `set_count`, `cost`, `status`) — nama dari relasi `user`, tanggal dari `created_at` |
| `catering` (`name`, `detail`, `menu`, `penyedia`, `waktu_catering`, `date`, `status`) | `catering_menus` (`menu_number`, `provider`, `served_at`, `status`) |
| `catering_ratings.catering_id` | `catering_ratings.catering_menu_id` |
| `izin_keluar` (`waktu_keluar`, `waktu_kembali`, `alasan`) | `leave_permits` (`leaves_at`, `returns_at`, `reason`) |
| `keluhan_asrama` (`judul`, `isi`) | `complaints` (`title`, `body`) |

## Nilai status

| Lama | Baru (`value`) |
|---|---|
| `Kosong` / `Assigned` / `Terisi` / `Penuh` | `empty` / `assigned` / `occupied` / `full` |
| `Diajukan` / `Disetujui` / `Ditolak` (izin) | `pending` / `approved` / `rejected` |
| `Diajukan` / `Resolved` (keluhan) | `pending` / `resolved` |
| `Diajukan` / `Selesai` (laundry) | `pending` / `done` |
| `Terjadwal` / `Disiapkan` / `Selesai` (catering) | `scheduled` / `preparing` / `done` |

Teks lama tetap muncul di layar lewat `label()`.

## Endpoint

Awalan `/backend/api/asrama/` (browser) dan `/api/asrama/` (Laravel) menjadi `/api/` di keduanya.

| Lama | Baru |
|---|---|
| `GET session`, `POST login`, `POST logout` | sama (`SessionController`) |
| `GET dashboard`, `GET welcome` | `GET dashboard` (admin); sambutan penghuni memakai `user` dari sesi |
| `GET kamar` (kamar + monitoring + `allocation_users`) | `GET rooms` (kamar + monitoring) dan `GET residents` |
| `POST kamar/allocate` | `POST rooms/allocate` |
| `PATCH allocation-users/{id}` | `PATCH residents/{resident}` |
| `GET user/kamar`, `POST user/kamar/check-in`, `…/check-out` | `GET my-room`, `POST my-room/check-in`, `…/check-out` |
| `GET izin-keluar`, `GET user/izin-keluar` | `GET leave-permits` |
| `POST user/izin-keluar`, `PATCH user/izin-keluar/{id}` | `POST leave-permits`, `PATCH leave-permits/{permit}` |
| `PATCH izin-keluar/{id}` (tinjau) | `PATCH leave-permits/{permit}/review` |
| `GET keluhan`, `GET user/keluhan`, `POST user/keluhan` | `GET complaints`, `POST complaints` |
| `PATCH keluhan/{id}` | `PATCH complaints/{complaint}/resolve` |
| `GET laundry`, `GET user/laundry`, `POST user/laundry` | `GET laundry-orders`, `POST laundry-orders` |
| `PATCH laundry/{id}` | `PATCH laundry-orders/{order}` |
| `GET catering`, `GET user/catering`, `POST catering` | `GET catering-menus`, `POST catering-menus` |
| `PATCH catering/{id}` | `PATCH catering-menus/{menu}` |
| `POST user/catering/{id}/rating` | `PUT catering-menus/{menu}/rating` |

Bentuk respons daftar berubah dari `{ records: { data, current_page, last_page } }` menjadi
`{ data, meta: { current_page, last_page, total } }`.

## Backend

| Lama | Baru |
|---|---|
| `AsramaController::login/logout` + closure `session` di rute | `SessionController` |
| `AsramaController::index` (beranda) / `welcome` | `DashboardController` |
| `AsramaController::index` (kamar) | `RoomController@index` + `ResidentController@index` |
| `AsramaController::index/update` (laundry) + `UserLaundryController` | `LaundryOrderController` |
| `AsramaController::update` (catering) + `CateringController` | `CateringMenuController`, `CateringRatingController` |
| `AsramaController::STATUSES` | enum di `app/Enums/` |
| `RoomAllocationController::allocate` + `Services/RoomAllocator` | `RoomAllocationController` (`__invoke`) + `Actions/AllocateRooms` |
| `RoomAllocationController::profile` | `ResidentController@update` + `Actions/UpdateResidentProfile` |
| `UserRoomController::show/checkIn/checkOut/transition` | `MyRoomController` + `Actions/CheckIn`, `Actions/CheckOut` |
| `Services/RoomStatus` (SQL mentah + `sync`) | `Enums/RoomStatus::fromCounts` + `Room::scopeWithOccupancy` |
| `LeavePermitController::query()`, `ComplaintController::query()` (statis) | `scopeVisibleTo` di model |
| Blok validasi `q/sort/direction/per_page` di tiap controller | `Support/TableQuery` |
| Tiga salinan baris `lockForUpdate` | `Support/RoomLocks` |
| `Carbon::createFromFormat(..., 'Asia/Jakarta')` di controller | klien mengirim ISO UTC; `Support/DateFormat::UTC` |
| Migrasi `prepare_room_allocation` (menyisipkan 78 kamar) | migrasi `create_rooms_table` + `RoomSeeder` |
| `AsramaAccountSeeder` + `AsramaUserSeeder` | `AccountSeeder` |

## Frontend

| Lama | Baru |
|---|---|
| `server/routes/backend/[...path].ts` | `server/routes/api/[...path].ts` |
| `useAsrama()` (`user`, `session`, `mutate`, `errorMessage`) | `useSession()`, `useApi()`, `utils/errors.ts` |
| `useTableSearch()` + salinannya di `LeavePermissions`/`RoomComplaints` | `useTable()` |
| `busy/error/success` + `try/catch/finally` di tiap komponen | `useAction()` |
| `setInterval(..., 15000)` di tiap komponen | `usePolling()` |
| `Intl.DateTimeFormat(...)` di tiap komponen | `utils/datetime.ts`, `utils/currency.ts` |
| `SortableHeader` + `TablePagination` + markup `<table>` berulang | `ui/DataTable` |
| `RoomStatusBadge` + `<span>` status ber-ternary | `ui/StatusBadge` |
| `LeavePermissions.vue` (prop `admin`) | `leave-permit/LeavePermitSection`, `…Form`, `…Table` |
| `RoomComplaints.vue` | `complaint/ComplaintSection`, `…Form`, `…Table` |
| `LaundryPanel.vue` | `laundry/LaundrySection`, `…Form`, `…Table` |
| `CateringPanel.vue` | `catering/CateringSection`, `CateringMenuForm`, `CateringTable` |
| `pages/kamar.vue` (92 baris, semua jadi satu) | `pages/admin/rooms.vue` + `room/RoomMonitoring`, `RoomAllocation`, `ResidentProfiles`, `RoomTable` |
| `pages/admin.vue`, `laundry.vue`, `catering.vue` | `pages/admin/index.vue`, `admin/laundry.vue`, `admin/catering.vue` |
| `pages/user/index.vue`, `user/kamar.vue`, `user/laundry.vue`, `user/catering.vue` | `pages/resident/index.vue`, `resident/room.vue`, `resident/laundry.vue`, `resident/catering.vue` |
| `layouts/default.vue` (satu berkas, baris sangat panjang) | `layouts/default.vue` + `layout/AppSidebar`, `AppHeader`, `AppFooter` |
| `public/js/asrama-theme.js` | `public/js/theme.js` |

## Dihapus tanpa pengganti

- `resources/views/**`, `resources/css`, `resources/js`, `vite.config.js`, `package.json` di root
  (hanya dipakai Blade).
- `public/css/asrama*.css`, `public/js/asrama-tables.js`, `public/js/asrama-theme.js`.
- Semua rute web non-API di `routes/web.php` (kecuali `/up`), termasuk `/pengumuman` dan `/checkin`
  yang hanya merender Blade.
- Semua cabang `$request->expectsJson()`, `view(...)`, `redirect(...)->with(...)`.
- Rute generik `/{page}`, `/{category}`, `/{category}/{id}` dan `AsramaController::store` — form admin
  "tambah laundry/catering" generik (`name`/`detail`/`status`/`date`) adalah sisa versi awal; laundry kini
  hanya diajukan penghuni dan catering ditambah lewat form menu.
- `pages/[category].vue`, `pages/user/[service].vue`.
- Kolom `kamar.status`, `kamar.date`, `laundry.name`, `laundry.date`, `catering.name`, `catering.detail`,
  `catering.date`.
- `tests/Feature/ExampleTest.php`, `tests/Unit/ExampleTest.php`, dan tes yang menguji respons Blade.

## Dokumen yang ikut diperbarui

`CLAUDE.md`, `AGENTS.md`, dan `dbdiagram.txt` di repo ini menggambarkan struktur lama. Setelah rewrite
selesai, perbarui ketiganya. Naskah skripsi (`../skripsi/`) juga memuat skema dan diagram lama — ingatkan
pengguna, tetapi jangan mengubah naskah tanpa diminta.
