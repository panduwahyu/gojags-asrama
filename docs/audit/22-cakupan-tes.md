# Audit Kualitas dan Cakupan Tes

Tanggal: 2026-10-15 · Commit yang diaudit: e6e7f96

## Ringkasan

Semua tes lulus: **PHPUnit 124**, **Vitest 30**, **Playwright tiruan 39** (E2E tidak dijalankan sesuai rencana). Dari 78 aturan dan pesan di `domain.md` yang dinilai di bawah, **62 Kuat**, **4 Lemah**, dan **12 tanpa tes**. Pesan teks di sisi backend hampir seluruhnya diperiksa persis; celah utama ada pada (1) perilaku sesi (`regenerate`/`invalidate`), (2) batas panjang dan batas nilai tertentu, (3) teks tampilan sisi klien (`Menu n`, `4,5 / 5 ★`, `Belum ada rating`, `Belum check-in`), (4) kondisi "aksi sedang berjalan" pada polling dan tombol nonaktif saat sibuk, serta (5) urutan kunci dan pengulangan transaksi. Cakupan kode tidak diukur (xdebug/pcov tidak terpasang).

Definisi: **Kuat** = tes memeriksa hasil (isi JSON, perubahan basis data, teks persis) atau, untuk aturan akses, kode status yang memang merupakan aturannya. **Lemah** = tes ada tetapi tidak membuktikan seluruh aturan. `—` = tidak ada tes.

## Temuan

Temuan ringkas (rinci per aturan ada di tabel berikut):

| # | Tingkat | Temuan | Bukti | Saran perbaikan |
|---|---|---|---|---|
| 1 | Sedang | Tidak ada tes yang membuktikan `session()->regenerate()` saat login maupun `invalidate()` + `regenerateToken()` saat logout | `SessionTest::test_logout_clears_authenticated_user` hanya memeriksa `user` null; `grep -E "regenerate\|assertGuest\|assertAuthenticated" tests frontend/tests` kosong | Tambah tes: ID sesi berubah setelah login; sesi dan token lama tidak berlaku setelah logout |
| 2 | Sedang | Batas nilai laundry sebagian tak teruji: `max:9999999999.99` dan `min:0` tidak ada tesnya; hanya "wajib saat done" dan 3 desimal | `LaundryOrderTest.php:79,85` (wajib, `15000.123`); tidak ada nilai negatif/di atas batas | Tambah kasus negatif dan di atas batas |
| 3 | Sedang | Urutan kunci `rooms → users → room_slots` dan pengulangan transaksi 3× tidak punya tes; satu-satunya bukti adalah tes tidak mengalami deadlock | Lihat audit 21 (5/5 lulus); tidak ada tes konkurensi | Tes yang memeriksa urutan `lockForUpdate` (query log) atau dua proses bersamaan |
| 4 | Rendah | Batas panjang profil (`cohort` 100, `religion` 50) tidak diuji | `str_repeat` hanya di `CateringTest`, `ComplaintTest`, `TableQueryTest` | Tambah kasus batas di `ResidentProfileTest` |
| 5 | Rendah | Pengurutan menu catering menurut rata-rata rating, jumlah penilai, dan rating saya tidak diuji | `CateringTest.php:273-302` hanya `sort=meal_time` | Tambah kasus sort untuk tiga kolom turunan |
| 6 | Rendah | Format keluaran waktu (ISO-8601 UTC) tidak diperiksa eksplisit; hanya format masukan | `grep -E "Z'" tests/Feature` hanya menampilkan data masukan | Tambah `assertJsonPath` pada satu respons yang memuat waktu |
| 7 | Rendah | Teks tampilan sisi klien di `domain.md` § 12 dan § 5 tak punya tes | `grep -F "Belum ada rating"`, `"Belum check-in"`, `"Menu "` di `tests frontend/tests`: kosong | Tambah ke spec tiruan katalog catering dan daftar kamar |
| 8 | Rendah | Tombol nonaktif saat sibuk dan penghentian polling saat ada aksi berjalan tidak diuji (hanya tab tersembunyi) | `frontend/tests/browser/polling.spec.ts:24` (hanya `document.hidden`); `grep -E "disabled\|busy\|Memproses" frontend/tests` kosong | Tambah kasus di `polling.spec.ts` dan satu spec formulir |
| 9 | Rendah | `frontend/tests/unit/samples.test.ts` menguji pembuat data contoh untuk tes itu sendiri, bukan perilaku aplikasi; nilainya rendah | `samples.test.ts:13-68` (7 tes `generates sample…`) | Hapus atau pertahankan hanya bila `samples.ts` kompleks |
| 10 | Rendah | Satu user satu slot (`room_slots.user_id` unik) dan invarian DB lain tidak punya tes yang berdiri sendiri | Dibuktikan hanya lewat probe sementara di audit 21 | Tes `UniqueConstraintViolationException` untuk slot ganda |

## Cakupan per aturan `domain.md`

| Bagian domain.md | Aturan / pesan | Tes yang mencakup (berkas::method) | Kuat/Lemah | Catatan |
|---|---|---|---|---|
| § 1 | 403 "Anda tidak memiliki izin untuk halaman ini." | `RoleAccessTest::test_admin_endpoints_reject_resident_with_403_and_guest_with_401`, `::test_resident_endpoints_reject_admin_with_403_and_guest_with_401`, `RoomTest::test_resident_cannot_access_rooms` | Kuat | Pesan diperiksa persis |
| § 1 | 401 bila belum login | tes yang sama + `ComplaintTest::test_guest_cannot_access_complaints`, `LaundryOrderTest::test_guest_cannot_access_laundry_orders`, `RoomTest::test_guest_cannot_access_rooms`, `SessionTest::test_logout_without_login_returns_401` | Kuat | |
| § 1 | Penghuni hanya melihat data miliknya | `LeavePermitTest::test_visibility_search_and_user_name_sorting_by_role`, `ComplaintTest::test_visibility_by_role_and_idempotent_admin_resolve`, `LaundryOrderTest::test_visibility_by_role_and_admin_status_update_with_cost_validation`, `StayHistoryTest::test_other_resident_cannot_see_different_user_stays` | Kuat | |
| § 1 | Id milik orang lain → 404 (bukan 403) | `LeavePermitTest::test_resident_can_edit_own_pending_permit_only` | Kuat | Hanya jalur edit izin memakai 404 kepemilikan |
| § 1 | Pengalihan middleware Nuxt per peran | `browser/roles.spec.ts` (3 tes), `unit/navigation.test.ts` | Kuat | |
| § 2 | Login gagal: "Email atau kata sandi tidak sesuai." | `SessionTest::test_invalid_login_returns_422_with_exact_message` | Kuat | |
| § 2 | Batas 5 percobaan per menit per email + IP, plus batas per IP | `SessionTest::test_sixth_login_attempt_within_a_minute_returns_429`, `::test_failed_logins_from_another_ip_do_not_lock_out_the_account_owner`, `::test_many_emails_from_one_ip_hit_the_per_ip_limit` | Kuat | Perilaku 422 → 429 → 200 adalah aturannya |
| § 2 | Login → `session()->regenerate()` | — | — | Temuan #1 |
| § 2 | Logout → `invalidate()`, `regenerateToken()` | `SessionTest::test_logout_clears_authenticated_user` | Lemah | Hanya memeriksa `user` null |
| § 2 | `GET /api/session` → `{ csrfToken, user }`; login/logout bentuk sama | `SessionTest::test_guest_session_returns_null_user_and_csrf_token_with_no_store_header`, `::test_valid_login_returns_user_with_role`, `::test_logout_clears_authenticated_user` | Kuat | |
| § 2 | Semua respons API berheader `Cache-Control: no-store` | `SessionTest::test_guest_session_returns_null_user_and_csrf_token_with_no_store_header` | Lemah | Hanya satu endpoint yang diperiksa |
| § 2 | Permintaan tulis wajib CSRF (419) | `RoleAccessTest::test_post_without_csrf_token_returns_419`, `browser/csrf.spec.ts` | Kuat | Termasuk ulang sekali saat 419 |
| § 3 | API hanya menerima format `Y-m-d\TH:i:s.v\Z` | `LeavePermitTest::test_returns_at_must_be_after_leaves_at_and_timestamps_require_z_suffix`, `DateFormatTest::test_date_format_utc_constant` | Kuat | |
| § 3 | Disimpan UTC dan dikirim ISO-8601 UTC | — | — | Temuan #6 (hanya format masukan) |
| § 3 | Tampil/diisi WIB berakhiran " WIB"; konversi `datetime-local` | `unit/datetime.test.ts` (5 tes), `browser/leave-permit.spec.ts` ("waktu dikirim sebagai UTC") | Kuat | |
| § 4 | `per_page` hanya 10/50/100, selain itu 422 | `TableQueryTest::test_invalid_per_page_returns_422` | Kuat | |
| § 4 | `sort` di-whitelist, selain itu 422 | `TableQueryTest::test_unwhitelisted_sort_column_returns_422` | Kuat | |
| § 4 | `q` maks 100 karakter | `TableQueryTest::test_search_query_exceeding_100_characters_returns_422` | Kuat | |
| § 4 | Bawaan `sort=id`, `direction=desc`, `per_page=10` | `TableQueryTest::test_default_sort_is_id_descending` | Lemah | Bawaan `per_page=10` tidak terlihat dalam nama/isi tes |
| § 4 | `id` sebagai pengurut kedua | `TableQueryTest::test_non_id_sort_uses_id_as_secondary_tiebreaker` | Kuat | |
| § 4 | Pencarian `LIKE` dengan escape wildcard | `TableQueryTest::test_search_escapes_like_wildcards`, `::test_search_by_enum_label_matches_cases_and_ignores_raw_values` | Kuat | |
| § 4 | Urutan status mengikuti alur kerja | `TableQueryTest::test_sort_by_status_follows_workflow_case_order`, `LaundryOrderTest::test_laundry_orders_sort_by_status_follows_workflow_order` | Kuat | |
| § 4 | Debounce 300 ms; ganti cari/urut/`per_page` → halaman 1; kolom sama membalik arah | `browser/data-table.spec.ts` (5 tes) | Kuat | |
| § 4 | Muat ulang 15 detik hanya saat tab terlihat | `browser/polling.spec.ts` | Kuat | |
| § 4 | …dan tidak ada aksi yang sedang berjalan | — | — | Temuan #8 |
| § 5 | 78 kamar bernomor 301–526, 2 slot per kamar | `SeederTest::test_seeders_create_rooms_slots_and_demo_accounts_idempotently` | Kuat | 78 kamar, 156 slot |
| § 5 | Satu user paling banyak satu slot (unik) | — | — | Temuan #10 |
| § 5 | Status kamar dihitung dari slot (empat kondisi) | `RoomStatusTest::test_from_counts_returns_expected_status`, `RoomModelTest::test_room_status_accessor_reflects_counts`, `::test_room_status_accessor_with_custom_capacity`, `RoomTest::test_admin_can_list_rooms_with_monitoring_and_derived_statuses` | Kuat | |
| § 5 | Daftar kamar admin: urut menurut nomor, lantai, nama penghuni, jumlah, status; cari nomor | `RoomTest::test_rooms_can_be_sorted_by_status_and_searched_by_number`, `::test_rooms_can_be_sorted_by_resident_and_assigned_count` | Kuat | Pencarian "lantai" tidak terlihat secara eksplisit |
| § 5 | Ringkasan monitoring: empat status selalu muncul | `RoomTest::test_admin_can_list_rooms_with_monitoring_and_derived_statuses` | Kuat | Memeriksa angka per status (`RoomTest.php:65-68`) |
| § 5 | Label "Belum check-in" di kolom penghuni | — | — | Temuan #7 |
| § 6 | `cohort`/`religion` dinormalisasi (spasi, trim, huruf kecil) | `ResidentProfileTest::test_admin_can_list_and_update_resident_profile_with_normalized_values`, `::test_update_resident_profile_action_normalizes_cohort_and_religion` | Kuat | |
| § 6 | Hasil kosong → "Angkatan dan agama wajib diisi." | `ResidentProfileTest::test_whitespace_only_cohort_returns_422_with_domain_message`, `::test_updating_profile_requires_all_fields` | Kuat | |
| § 6 | Ketiga kolom wajib; gender tidak dikenal → 422 | `ResidentProfileTest::test_updating_profile_requires_all_fields`, `::test_unknown_gender_returns_422` | Kuat | |
| § 6 | `cohort` maks 100, `religion` maks 50 | — | — | Temuan #4 |
| § 6 | Hanya `resident` yang bisa diubah (admin → 404) | `ResidentProfileTest::test_updating_admin_user_returns_404`, `::test_update_resident_profile_action_aborts_404_when_user_is_admin` | Kuat | |
| § 6 | Profil yang sudah punya slot terkunci + pesan | `ResidentProfileTest::test_resident_who_already_has_a_slot_cannot_have_profile_updated` | Kuat | Aturan ini bertentangan dengan keputusan stakeholder no. 7 (audit 19) |
| § 6 | Berhasil → "Profil alokasi berhasil disimpan." | `ResidentProfileTest::test_admin_can_list_and_update_resident_profile_with_normalized_values` | Kuat | |
| § 7 | Lantai asal cohort (3 tingkat prioritas) | `RoomAllocationTest::test_new_cohort_is_placed_on_highest_empty_floor`, `::test_second_cohort_gets_next_unused_floor_instead_of_mixing_on_floor_five`, `::test_cohort_with_existing_resident_on_floor_four_continues_on_floor_four`, `::test_new_cohort_assigned_to_highest_floor_with_available_slots_when_no_empty_floor` | Kuat | |
| § 7 | Teman sekamar sama gender dan agama | `RoomAllocationTest::test_roommates_always_share_gender_and_religion` | Kuat | |
| § 7 | Prioritas kamar (se-cohort, cohort lain, kosong; seri menurut nomor) | `RoomAllocationTest::test_partially_occupied_compatible_room_is_filled_before_empty_room`, `::test_room_priority_on_same_floor` | Kuat | |
| § 7 | Tidak dialokasikan: profil belum lengkap → `incomplete` | `RoomAllocationTest::test_incomplete_profile_is_reported_in_incomplete_and_gets_no_slot` | Kuat | |
| § 7 | Tidak ada kandidat → `waiting` | `RoomAllocationTest::test_resident_without_matching_room_is_placed_in_waiting`, `::test_cohort_with_home_floor_five_overflows_to_floor_four_when_full` | Kuat | |
| § 7 | Pernah check-out tidak dialokasikan ulang | `RoomAllocationTest::test_user_who_has_checked_out_is_not_reallocated` | Kuat | Bertentangan dengan keputusan stakeholder no. 9 (audit 19) |
| § 7 | Penghuni yang sudah berkamar tidak dipindahkan | `RoomAllocationTest::test_already_assigned_resident_is_not_moved_on_subsequent_allocation` | Kuat | |
| § 7 | Cohort diproses menurut `SORT_NATURAL`; spasi dinormalisasi | `RoomAllocationTest::test_cohorts_are_processed_in_natural_sort_order`, `::test_cohort_with_whitespace_is_normalized_to_same_cohort` | Kuat | |
| § 7 | Anggota diproses menurut `id` | — | — | Tidak ada tes yang menyatakannya |
| § 7 | Slot baru tidak mewarisi `checked_in_at` lama | `RoomAllocationTest::test_slot_does_not_inherit_stale_checked_in_at` | Kuat | |
| § 7 | Hasil dan pesan "N user dialokasikan. M profil belum lengkap. K user menunggu kamar yang sesuai." | `RoomAllocationTest::test_summary_message_format_and_authorization` | Kuat | |
| § 7 | Urutan kunci `rooms → users → room_slots`; transaksi diulang 3× | — | — | Temuan #3 |
| § 8 | Check-in: tanpa slot → "Anda belum memiliki alokasi kamar." | `MyRoomTest::test_unallocated_resident_sees_null_room_and_cannot_check_in`, `::test_check_in_action_throws_validation_exception_when_resident_has_no_room_slot` | Kuat | |
| § 8 | Check-in dua kali → "Anda sudah check-in." | `MyRoomTest::test_check_in_action_throws_validation_exception_when_already_checked_in`, `::test_check_in_and_check_out_lifecycle_updates_stays_slots_and_room_status` | Kuat | |
| § 8 | Check-in → `stays` berisi salinan nama, nomor kamar, lantai; "Check-in berhasil." | `MyRoomTest::test_check_in_and_check_out_lifecycle_updates_stays_slots_and_room_status` | Kuat | |
| § 8 | Check-out sebelum check-in → "Anda harus check-in sebelum check-out." | `MyRoomTest::test_check_out_action_throws_validation_exception_when_not_checked_in` | Kuat | |
| § 8 | Tanpa `stays` aktif → "Riwayat check-in aktif tidak ditemukan." | `MyRoomTest::test_check_out_without_active_stay_returns_422_and_retains_slot` | Kuat | |
| § 8 | Check-out melepas slot; pesan "…Slot kamar telah dilepaskan." | `MyRoomTest::test_check_in_and_check_out_lifecycle_updates_stays_slots_and_room_status` | Kuat | |
| § 8 | Riwayat `stays` terbaru lebih dulu; penghuni lain tak melihat | `StayHistoryTest::test_stays_history_returns_ordered_stays_descending`, `::test_other_resident_cannot_see_different_user_stays` | Kuat | |
| § 8 | Halaman "Kamar saya" dan "Belum ada alokasi kamar" | `browser/my-room.spec.ts` (3 tes) | Kuat | |
| § 9 | Pengajuan: status `pending`, 201 "Izin keluar berhasil diajukan." | `LeavePermitTest::test_resident_can_submit_leave_permit_and_starts_pending` | Kuat | |
| § 9 | `returns_at` setelah `leaves_at` + pesan | `LeavePermitTest::test_returns_at_must_be_after_leaves_at_and_timestamps_require_z_suffix` | Kuat | |
| § 9 | Edit hanya milik sendiri dan `pending`; pesan edit | `LeavePermitTest::test_resident_can_edit_own_pending_permit_only` | Kuat | |
| § 9 | Tinjau hanya `approved`/`rejected`, hanya `pending`; isi `reviewed_by`/`reviewed_at`; pesan | `LeavePermitTest::test_admin_can_review_pending_permit_once_and_resident_cannot_review` | Kuat | |
| § 9 | Pengajuan mengabaikan `status`, `user_id`, `reviewed_by` | `LeavePermitTest::test_submitting_leave_permit_ignores_status_user_id_and_reviewed_by` | Kuat | |
| § 9 | Edit dan tinjau mengunci baris (`lockForUpdate`) | — | — | Temuan #3 |
| § 9 | Klien: form di-reset + "Pengajuan sudah diproses admin dan tidak dapat diedit." | — | — | Pesan klien tidak ada di tes mana pun |
| § 10 | Keluhan: batas `title` 150, `body` 2000; 201 + pesan | `ComplaintTest::test_resident_can_submit_complaint_and_length_limits_are_enforced` | Kuat | |
| § 10 | Resolve idempoten; waktu dan admin pertama dipertahankan; pesan | `ComplaintTest::test_visibility_by_role_and_idempotent_admin_resolve` | Kuat | |
| § 11 | Pengajuan laundry: batas `set_count` 1–1000, status `pending`, `cost` null, pesan | `LaundryOrderTest::test_resident_can_submit_laundry_order_and_set_count_bounds_are_enforced`, `::test_submitting_laundry_order_ignores_status_and_cost` | Kuat | |
| § 11 | `cost` wajib bila `done`; maks 2 desimal; pesan | `LaundryOrderTest::test_visibility_by_role_and_admin_status_update_with_cost_validation`, `::test_admin_can_reset_cost_to_null_and_omitted_cost_preserves_existing_cost` | Lemah | Batas `0` dan `9999999999.99` tidak diuji (temuan #2) |
| § 11 | Tampilan Rupiah dan "Belum ditentukan" | `unit/currency.test.ts` (2 tes) | Kuat | |
| § 12 | Tambah menu + pesan; `menu_number` 1–10000; status awal `scheduled` | `CateringTest::test_admin_can_create_catering_menu_with_dish_name_and_meal_time`, `::test_dish_name_and_meal_time_validation_bounds` | Kuat | |
| § 12 | Ubah status menu + pesan | `CateringTest::test_admin_can_create_and_update_catering_menu_status`, `::test_admin_can_patch_only_status_leaving_other_attributes_intact` | Kuat | Aturan ini bertentangan dengan keputusan stakeholder no. 16 (audit 19) |
| § 12 | Satu rating per penghuni per menu; kirim ulang menimpa; pesan | `CateringTest::test_resident_rating_bounds_overwrite_and_aggregates` | Kuat | |
| § 12 | `rating_average` (2 desimal), `rating_count`, `my_rating` | `CateringTest::test_resident_rating_bounds_overwrite_and_aggregates` | Kuat | |
| § 12 | Urut menurut rata-rata rating, jumlah penilai, rating saya | — | — | Temuan #5 |
| § 12 | Tampilan "Menu n", "4,5 / 5 ★", "Belum ada rating" | — | — | Temuan #7 |
| § 13 | Dashboard admin: tiga hitungan; penghuni: sambutan | `RoleAccessTest::test_dashboard_returns_three_counts_for_admin`, `browser/dashboard.spec.ts` (2 tes) | Kuat | |
| § 14 | Seeder kamar/slot/akun idempoten; profil terisi tidak ditimpa; akun demo tak dibuat di produksi | `SeederTest::test_seeders_create_rooms_slots_and_demo_accounts_idempotently`, `::test_demo_accounts_are_not_seeded_in_production` | Kuat | |

Aturan yang ada di kode tetapi tidak disebut `domain.md` juga punya tes (tidak dihitung di atas): tolak rating sebelum hidang dan batas 23.59 WIB (`CateringTest::test_rating_before_served_at_is_rejected`, `::test_rating_window_lifecycle_and_boundaries`, `::test_late_night_menu_rating_closes_at_end_of_wib_day_not_utc_day`), `CateringTest::test_menu_list_exposes_can_rate_for_all_three_states`, `CateringMenuModelTest`.

## "Minimum per fitur" (`testing.md` § 10)

Daftar periksa: feature test (berhasil, tiap aturan+pesan, 401/403/404), data contoh di `samples.ts`, spec tiruan (per peran, tiap aksi tulis, satu error server), spec E2E lintas peran, tes Vitest untuk fungsi `utils/`.

| Fitur | Butir yang belum terpenuhi |
|---|---|
| Login/sesi | Aturan `regenerate`/`invalidate` tanpa tes; `no-store` hanya satu endpoint (temuan #1) |
| Kamar & alokasi | Urutan kunci dan transaksi ulang tanpa tes; label "Belum check-in" tanpa tes UI |
| Check-in/out | Tidak ada; spec E2E tercakup di `e2e/rooms.spec.ts` |
| Profil penghuni | Batas panjang 100/50 tanpa tes |
| Izin keluar | Pesan klien "Pengajuan sudah diproses admin…" tanpa tes; `lockForUpdate` tanpa tes |
| Keluhan | Tidak ada |
| Laundry | Batas `cost` 0 dan 9999999999.99 tanpa tes |
| Catering | Sort kolom turunan rating tanpa tes; teks tampilan rating tanpa tes |
| Dashboard | Tidak ada |
| `utils/` | Semua fungsi memiliki tes; `samples.test.ts` bernilai rendah (temuan #9) |

## Dugaan (belum terbukti)

- Pencarian kamar menurut lantai (`domain.md` § 5) kemungkinan tercakup oleh `RoomTest` tetapi tidak terlihat dari nama atau potongan yang dibaca; penilaian "Kuat" pada baris itu bergantung pada pembacaan nama tes.
- Tiga tes `test_*_action_throws_validation_exception_*` di `MyRoomTest` dan `ResidentProfileTest` memakai nama sebagai pernyataan; metrik assertion tidak menangkap `expectException`, sehingga dinilai Kuat berdasarkan nama.
- Cakupan baris sebenarnya tidak diketahui (tanpa driver coverage).

## Perintah yang dijalankan

- `php artisan test` → 124 lulus (564 assertion)
- `npm --prefix frontend run test:unit` → 6 berkas, 30 lulus
- `npm --prefix frontend run test:browser` → 39 lulus
- `php -m | grep -iE "xdebug|pcov"` → kosong; **coverage tidak diukur (xdebug/pcov tidak terpasang)**
- Skrip penghitung jenis assertion per metode PHPUnit (di scratchpad, di luar repositori) dan `grep` pesan dari `domain.md` di `tests` dan `frontend/tests`
