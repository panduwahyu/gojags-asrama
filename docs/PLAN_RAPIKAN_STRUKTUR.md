# Rencana Merapikan Struktur Gojags Asrama

Rencana ini ditulis agar bisa dikerjakan oleh AI agent mana pun (termasuk model murah) **tanpa perlu
menebak**. Kerjakan tugas **berurutan**, satu tugas = satu commit. Jangan melompat, jangan menggabungkan
tugas, jangan "sekalian merapikan" hal lain yang tidak disebut.

Semua perintah dijalankan dari root repo `gojags-asrama/` dengan Git Bash.

> **Status (2026-10-15):** Bagian 1 (Tugas 1–5), Bagian 2 (Tugas 6–15), Bagian 3 (Tugas 16–18, keamanan
> akses), dan Bagian 4 (Tugas 19–24, audit lanjutan) **sudah selesai**. Laporan audit ada di `docs/audit/`;
> perbaikan atas temuannya menunggu keputusan pemilik.

---

## 0. Aturan wajib (baca dulu, berlaku untuk semua tugas)

1. **Jangan sentuh berkas yang sedang dikerjakan pemilik.** Berkas berikut punya perubahan belum
   di-commit milik pemilik dan TIDAK boleh diubah, di-stage, di-commit, atau di-revert:
   - `frontend/playwright.e2e.config.ts`
   - `frontend/tests/e2e/*.spec.ts` (auth, catering, complaint, dashboard, laundry, leave-permit, rooms)
   - `frontend/tests/e2e/screenshots.ts`
   - `.agents/`, `.claude/skills/ui-ux-pro-max/`
2. **Jangan pernah** memakai `git add -A`, `git add .`, `git commit -a`, `git stash`, `git reset --hard`,
   `git checkout -- <file>`, atau `git clean`. Stage berkas **satu per satu dengan path eksplisit**.
3. **Jangan menghapus permanen** berkas yang belum ter-commit (untracked). Pindahkan saja (`mv`).
   Berkas yang ter-commit boleh dipindah dengan `git mv`.
4. **Jangan mengubah perilaku aplikasi.** Rencana ini murni struktur/penamaan. Tidak ada perubahan
   aturan bisnis, teks pesan, kontrak API, atau skema basis data.
5. Setelah tiap tugas jalankan **Verifikasi** pada tugas itu. Jika gagal dan kamu tidak yakin
   penyebabnya, **berhenti dan laporkan** — jangan menebak-nebak perbaikan di luar cakupan tugas.
6. Format pesan commit (ikuti gaya repo), diakhiri baris atribusi:
   ```
   <tipe>(<cakupan>): <ringkasan bahasa Indonesia>

   Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>
   ```
   Contoh: `chore(repo): pindahkan dokumen kerja ke docs/notes`.
7. Commit di branch `rewrite` (branch saat ini). Jangan push.

### Catatan kondisi awal (diukur 2026-10-15)

- `php artisan test` → **125 passed**. Pada 1 dari 4 percobaan muncul 18 kegagalan
  `SQLSTATE[40001] Deadlock found` (flaky, kemungkinan basis data `gojags_asrama_testing` sedang dipakai
  proses lain). **Jika kamu melihat error deadlock, jalankan ulang tes satu kali** sebelum menyimpulkan
  ada yang rusak. Jika gagal dua kali berturut-turut dengan error yang sama, berhenti dan laporkan.
- `vendor/bin/pint --test` → bersih.
- Butuh MySQL hidup di `127.0.0.1:3306` (user `root`, tanpa password). Jika MySQL mati, laporkan.

### Perintah verifikasi standar

```bash
# Backend
php artisan test                 # harus: Tests: N passed, 0 failed
vendor/bin/pint --test           # harus: {"result":"passed"}

# Frontend
npm --prefix frontend run typecheck
npm --prefix frontend run test:unit
```

`npm --prefix frontend run test:browser` hanya perlu dijalankan pada Tugas 2 (menyentuh `public/`).
**Jangan** jalankan `test:e2e` (butuh basis data khusus dan berkasnya sedang diubah pemilik).

---

## Tugas 1 — Pindahkan dokumen kerja & gambar sisa dari root ke `docs/`

**Tujuan:** root repo hanya berisi berkas proyek (README, CLAUDE.md, AGENTS.md, konfigurasi).

**Langkah:**

```bash
mkdir -p docs/notes docs/screenshots/archive

# Berkas ter-commit -> git mv
git mv REWRITE_PROMPT.md docs/notes/REWRITE_PROMPT.md
git mv REWRITE_TASKS.md docs/notes/REWRITE_TASKS.md
git mv DEDUKSI_JAWABAN_STAKEHOLDER.md docs/notes/DEDUKSI_JAWABAN_STAKEHOLDER.md

# Berkas untracked -> mv biasa (JANGAN dihapus)
mv FIX_TASKS.md docs/notes/FIX_TASKS.md
mv konfirmasi.md docs/notes/konfirmasi.md
mv audit_*.png preview_*.png gojags_training_screenshot.png docs/screenshots/archive/
```

Lalu perbaiki rujukan: `docs/notes/DEDUKSI_JAWABAN_STAKEHOLDER.md` menyebut `konfirmasi.md`. Karena
keduanya kini satu folder, rujukan tetap valid — **tidak perlu diubah**. Cek rujukan lain:

```bash
grep -rnE "REWRITE_PROMPT|REWRITE_TASKS|DEDUKSI_JAWABAN|FIX_TASKS" --include=*.md --include=*.ts \
  --include=*.php --include=*.mjs . | grep -v node_modules | grep -v "^./docs/notes/"
```

Untuk setiap baris yang muncul, ubah path-nya menjadi `docs/notes/<nama>`.

**Commit:** stage hanya rename tadi dan berkas yang rujukannya diubah. Berkas untracked yang dipindah
(`docs/notes/FIX_TASKS.md`, `docs/notes/konfirmasi.md`, `docs/screenshots/archive/*`) **tidak di-commit**
— itu milik pemilik, biarkan untracked.

```bash
git status --short          # pastikan yang ter-stage hanya 3 rename (+ berkas rujukan bila ada)
git commit -m "chore(repo): pindahkan dokumen kerja ke docs/notes ..."
```

**Verifikasi:** `ls` di root tidak lagi menampilkan `*.png`, `REWRITE_*`, `DEDUKSI_*`, `FIX_TASKS.md`,
`konfirmasi.md`. Tidak perlu menjalankan tes (tidak ada kode berubah).

---

## Tugas 2 — Keluarkan skrip scraping & aset tak terpakai dari `frontend/`

**Tujuan:** `frontend/scripts/` hanya berisi `smoke.mjs` dan `reset-e2e-db.mjs`; `frontend/public/`
hanya berisi aset yang benar-benar dirujuk aplikasi.

**Fakta yang sudah dicek:**
- Skrip yang tidak dipakai `package.json` maupun tes: `scrape-gojags.mjs`, `scrape-classroom-training.mjs`,
  `fetch-training-assets.mjs`, `screenshot-canva-guide.mjs`.
- Aset yang **DIPAKAI** (jangan dipindah): `public/images/gojags.png`, `public/images/corpu.png`,
  `public/images/bps.svg`, `public/js/theme.js` (dirujuk `nuxt.config.ts`), seluruh `public/fonts/`.
- Aset yang **tidak dirujuk** di `frontend/app`, `nuxt.config.ts`, maupun tes:
  `public/images/{classroom,gojags-logo,gojags-mobile,office,training}.png` dan seluruh
  `public/gojags-portal/`.

**Langkah 2a — cek ulang sebelum memindah** (wajib; kode bisa berubah sejak rencana ditulis):

```bash
for name in classroom gojags-logo gojags-mobile office training gojags-portal scraped_data \
            scrape-gojags scrape-classroom fetch-training-assets screenshot-canva; do
  echo "== $name"
  grep -rn "$name" frontend/app frontend/nuxt.config.ts frontend/package.json frontend/tests \
    frontend/scripts/smoke.mjs frontend/scripts/reset-e2e-db.mjs 2>/dev/null
done
```

Setiap nama harus **tanpa hasil** (selain judul `== name`). Jika ada hasil untuk suatu nama,
**jangan pindahkan berkas itu**, dan catat di laporan akhir.

**Langkah 2b — pindahkan** (pakai `git mv`, sehingga tetap bisa dipulihkan):

```bash
mkdir -p docs/archive/scripts docs/archive/public-images
git mv frontend/scripts/scrape-gojags.mjs            docs/archive/scripts/
git mv frontend/scripts/scrape-classroom-training.mjs docs/archive/scripts/
git mv frontend/scripts/fetch-training-assets.mjs    docs/archive/scripts/
git mv frontend/scripts/screenshot-canva-guide.mjs   docs/archive/scripts/
git mv frontend/public/gojags-portal                 docs/archive/gojags-portal
for img in classroom gojags-logo gojags-mobile office training; do
  git mv "frontend/public/images/$img.png" docs/archive/public-images/
done
```

Tambahkan `docs/archive/README.md` berisi tepat:

```markdown
# Arsip

Berkas di sini tidak dipakai aplikasi. Disimpan sebagai referensi (skrip pengambil aset portal GOJAGS
dan gambar yang pernah dipakai). Aman dihapus jika tidak lagi dibutuhkan.
```

**Verifikasi:**

```bash
npm --prefix frontend run typecheck
npm --prefix frontend run test:unit
npm --prefix frontend run test:browser
```

Semua harus lulus. Jika `test:browser` gagal karena gambar 404, kembalikan gambar yang dimaksud
(`git mv` balik) dan catat.

**Commit:** `chore(frontend): arsipkan skrip scraping dan aset yang tidak dipakai`
Stage: semua rename di atas + `docs/archive/README.md`.

---

## Tugas 3 — Satukan berkas tes yang tumpang tindih

**Tujuan:** satu fitur = satu berkas tes Feature; `tests/Unit/` hanya logika murni tanpa basis data;
nama berkas Unit tidak memakai akhiran `UnitTest`.

Cara memindahkan method tes ke berkas lain (berlaku untuk semua sub-tugas):
1. Salin seluruh method `public function test_...(): void { ... }` ke akhir kelas tujuan (sebelum `}`
   penutup kelas).
2. Salin baris `use ...;` yang dibutuhkan method itu dan belum ada di berkas tujuan. Urutkan blok `use`
   secara alfabetis.
3. Pastikan kelas tujuan memakai `use RefreshDatabase;` bila method itu menyentuh basis data.
4. Jika nama method sudah ada di kelas tujuan, ganti nama method yang dipindah agar unik.
5. Hapus berkas sumber dengan `git rm`.

### 3a. `ResidentProfileValidationTest` → `ResidentProfileTest`

- `tests/Feature/ResidentProfileValidationTest.php` berisi 2 method:
  `test_updating_profile_requires_all_fields` dan `test_updating_profile_with_invalid_gender_fails_validation`.
- `tests/Feature/ResidentProfileTest.php` sudah punya `test_unknown_gender_returns_422`.
- Bandingkan isi `test_updating_profile_with_invalid_gender_fails_validation` dengan
  `test_unknown_gender_returns_422`. Jika keduanya mengirim gender tidak valid dan sama-sama memeriksa
  status 422 + error pada `gender`, itu **duplikat → jangan dipindah**. Jika berbeda (mis. salah satu
  memeriksa teks pesan), pindahkan.
- Pindahkan `test_updating_profile_requires_all_fields` (tidak ada padanannya).
- `git rm tests/Feature/ResidentProfileValidationTest.php`.

### 3b. `CateringRatingIntegrationTest` → `CateringTest`

- `test_deleting_catering_menu_cascades_and_removes_all_ratings` menduplikasi
  `CateringTest::test_admin_can_delete_catering_menu_and_associated_ratings_are_removed`, tetapi memakai
  **dua** penghuni. Tindakan: di `CateringTest`, ubah method yang ada supaya membuat rating dari **dua**
  penghuni (tambahkan `$secondResident` dan rating kedua), lalu `assertSame(2, CateringRating::count())`
  sebelum hapus dan `assertSame(0, ...)` sesudahnya. Jangan pindahkan method duplikatnya.
- `test_admin_cannot_submit_rating`: baca `CateringTest::test_role_boundaries_for_catering_and_rating`.
  Jika di sana sudah ada admin yang `putJson('/api/catering-menus/{id}/rating', ...)` dan
  `assertForbidden()`, itu duplikat → jangan dipindah. Jika belum ada, pindahkan method ini.
- `git rm tests/Feature/CateringRatingIntegrationTest.php`.

### 3c. Tes "Unit" yang memakai basis data → pindah ke Feature

Berkas berikut memakai `RefreshDatabase`, jadi bukan unit test murni:

| Sumber | Tujuan |
|---|---|
| `tests/Unit/CheckInActionUnitTest.php` | method dipindah ke `tests/Feature/MyRoomTest.php` |
| `tests/Unit/CheckOutActionUnitTest.php` | method dipindah ke `tests/Feature/MyRoomTest.php` |
| `tests/Unit/UpdateResidentProfileUnitTest.php` | method dipindah ke `tests/Feature/ResidentProfileTest.php` |

Saat memindah, beri awalan nama method agar jelas bahwa yang diuji Action-nya langsung, contoh:
`test_throws_validation_exception_when_already_checked_in` →
`test_check_in_action_throws_when_already_checked_in`. Jangan ubah isi method selain nama.
Hapus ketiga berkas sumber dengan `git rm`.

### 3d. Seragamkan nama di `tests/Unit/`

```bash
git mv tests/Unit/CateringMenuUnitTest.php tests/Unit/CateringMenuModelTest.php
git mv tests/Unit/RoomModelUnitTest.php    tests/Unit/RoomModelTest.php
```

Lalu ganti nama kelas di dalamnya: `class CateringMenuUnitTest` → `class CateringMenuModelTest`,
`class RoomModelUnitTest` → `class RoomModelTest`.

Periksa bahwa `CateringMenuModelTest` dan `RoomModelTest` **tidak** memakai `RefreshDatabase` dan tidak
memanggil `::create(` / `->create(` / `factory()->create`. Jika memakai basis data, pindahkan ke
`tests/Feature/CateringTest.php` / `tests/Feature/RoomTest.php` dengan cara di atas alih-alih
mengganti nama.

### Verifikasi Tugas 3

```bash
php artisan test                  # jumlah "passed" boleh turun HANYA sebanyak duplikat yang tidak dipindah
vendor/bin/pint                   # rapikan format (ini mengubah berkas; itu diharapkan)
vendor/bin/pint --test
ls tests/Unit                     # tidak ada nama berakhiran UnitTest.php
grep -l RefreshDatabase tests/Unit/*.php   # harus kosong
```

Catat di laporan akhir: jumlah tes sebelum (125) dan sesudah, serta method mana yang dibuang karena
duplikat beserta alasannya.

**Commit:** `test: satukan berkas tes per fitur dan pisahkan unit test murni`

---

## Tugas 4 — Sederhanakan `app/Actions/AllocateRooms.php`

**Tujuan:** `handle()` terbaca sebagai alur "kunci → kelompokkan per cohort → cari kamar → tempatkan",
tanpa array state (`$occupants`, `$availableSlots`) yang dioper dengan referensi ke banyak method.

**Jaring pengaman:** `tests/Feature/RoomAllocationTest.php` (404 baris) menguji semua aturan alokasi.
**Jangan ubah berkas tes itu.** Jika tes itu gagal setelah refactor, refactor-nya yang salah.

**Langkah 4a — buat `app/Support/RoomOccupancy.php`** yang menyimpan state penempatan selama satu
proses alokasi. Isi tepat seperti ini (sesuaikan hanya bila `pint` meminta):

```php
<?php

namespace App\Support;

use App\Models\Room;
use App\Models\RoomSlot;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

/**
 * Peta penghuni dan slot kosong per kamar selama satu proses alokasi otomatis.
 * Data diambil dari baris yang sudah dikunci oleh RoomLocks.
 */
class RoomOccupancy
{
    /** @var array<int, list<User>> */
    private array $occupants = [];

    /** @var array<int, int> */
    private array $freeSlots = [];

    /** @var array<int, true> */
    private array $assignedUserIds = [];

    /**
     * @param  Collection<int, Room>  $rooms
     * @param  Collection<int, RoomSlot>  $slots
     * @param  \Illuminate\Support\Collection<int, User>  $usersById
     */
    public function __construct(
        private readonly Collection $rooms,
        private readonly Collection $slots,
        \Illuminate\Support\Collection $usersById,
    ) {
        foreach ($rooms as $room) {
            $this->freeSlots[$room->id] = 0;
        }

        foreach ($slots as $slot) {
            if ($slot->user_id !== null && isset($usersById[$slot->user_id])) {
                $this->occupants[$slot->room_id][] = $usersById[$slot->user_id];
                $this->assignedUserIds[$slot->user_id] = true;
            } elseif ($slot->user_id === null && isset($this->freeSlots[$slot->room_id])) {
                $this->freeSlots[$slot->room_id]++;
            }
        }
    }

    /** @return Collection<int, Room> */
    public function rooms(): Collection
    {
        return $this->rooms;
    }

    /** @return list<User> */
    public function occupantsOf(Room $room): array
    {
        return $this->occupants[$room->id] ?? [];
    }

    public function hasFreeSlot(Room $room): bool
    {
        return ($this->freeSlots[$room->id] ?? 0) > 0;
    }

    public function isAssigned(User $user): bool
    {
        return isset($this->assignedUserIds[$user->id]);
    }

    public function assign(User $resident, Room $room): void
    {
        $slot = $this->slots->first(
            fn (RoomSlot $slot) => $slot->room_id === $room->id && $slot->user_id === null
        );

        if ($slot === null) {
            throw new RuntimeException('Slot kamar tidak konsisten.');
        }

        $slot->update(['user_id' => $resident->id, 'checked_in_at' => null]);
        $this->occupants[$room->id][] = $resident;
        $this->assignedUserIds[$resident->id] = true;
        $this->freeSlots[$room->id] = max(0, $this->freeSlots[$room->id] - 1);
    }
}
```

Catatan penting: `$slot->update(...)` mengubah objek model di dalam koleksi `$slots`, sehingga slot itu
tidak terpilih lagi pada pemanggilan `assign()` berikutnya. Perilaku ini bergantung pada kelas memegang
**koleksi yang sama** dengan yang dikembalikan `RoomLocks::acquireAll()` — jangan menyalin, memfilter
ulang, atau `->values()` koleksi `$slots` sebelum dioper ke konstruktor.

**Langkah 4b — tulis ulang `AllocateRooms`** memakai `RoomOccupancy`. Aturan:

- Signature publik `handle(): array` dan bentuk array kembaliannya **tidak berubah** (docblock tetap).
- `DB::transaction(..., 3)` dan `RoomLocks::acquireAll()` tetap dipakai persis seperti sekarang.
- Normalisasi `cohort`/`religion` tetap dilakukan di awal, sebelum `new RoomOccupancy(...)`.
- Ganti setiap pemakaian:
  - `$occupants[$room->id] ?? []` → `$occupancy->occupantsOf($room)`
  - `($availableSlots[$room->id] ?? 0) > 0` → `$occupancy->hasFreeSlot($room)`
  - `isset($assignedIds[$user->id])` → `$occupancy->isAssigned($user)`
  - `$this->assign($resident, $room, $slots, $occupants, $availableSlots)` →
    `$occupancy->assign($resident, $room)`
- Method privat `homeFloorFor` dan `findRoomFor` menerima `RoomOccupancy $occupancy` sebagai pengganti
  tiga parameter `$rooms, $occupants, $availableSlots`; ambil kamar dengan `$occupancy->rooms()`.
- `pendingResidents` menerima `RoomOccupancy $occupancy` sebagai pengganti `array $assignedIds`.
- Hapus method privat `assign` dari `AllocateRooms` (sudah pindah).
- **Pertahankan kedua komentar "mengapa"** (`// Cadangkan satu lantai kosong utuh ...` dan
  `// Isi kamar terisi sebagian yang cocok ...`) di tempat yang sama.
- Jangan ubah urutan iterasi, urutan sort (`[$priority, $room->number]`), `SORT_NATURAL`, atau logika
  `hasCompleteProfile` / `normalize`.

Target: `AllocateRooms.php` ≤ 180 baris, tidak ada parameter `array &`.

**Verifikasi:**

```bash
php artisan test tests/Feature/RoomAllocationTest.php   # semua lulus
php artisan test                                          # semua lulus
vendor/bin/pint && vendor/bin/pint --test
grep -n "array &" app/Actions/AllocateRooms.php           # harus kosong
wc -l app/Actions/AllocateRooms.php                       # <= 180
```

Jika `RoomAllocationTest` gagal dan dalam 2 percobaan perbaikan belum lulus:
`git restore app/Actions/AllocateRooms.php` (hanya berkas ini), hapus `app/Support/RoomOccupancy.php`,
lewati Tugas 4, dan laporkan tes mana yang gagal.

**Commit:** `refactor(allocation): pindahkan state penempatan ke RoomOccupancy`

---

## Tugas 5 — Sinkronkan dokumentasi

Perbarui **hanya** bagian yang berubah. Jangan menulis ulang dokumen.

1. `CLAUDE.md`, bagian "Struktur Direktori":
   - Baris `Support/` → `# DateFormat, TableQuery, RoomLocks, RoomOccupancy` (lewati jika Tugas 4 dilewati).
   - Baris `components/` sudah menyebut `dashboard/`; tambahkan `auth/` ke daftar dalam kurung.
   - Baris `utils/` sudah benar (`navigation.ts` tercantum) — biarkan.
   - Bagian "Pengujian" → poin Unit PHP: ganti `menguji logika murni (RoomStatus::fromCounts)` menjadi
     `menguji logika murni tanpa basis data (enum, RoomStatus::fromCounts, accessor model)`.
2. `AGENTS.md`: cari rujukan ke berkas yang dipindah/dihapus pada Tugas 1–3
   (`grep -nE "ValidationTest|IntegrationTest|UnitTest|scrape|REWRITE|DEDUKSI" AGENTS.md README.md`)
   dan perbaiki path/nama yang muncul.
3. `.claude/skills/gojags-asrama-architecture/SKILL.md`, blok "Struktur target":
   - `Support/` → tambahkan `RoomOccupancy` (lewati jika Tugas 4 dilewati).
   - `components/` → tambahkan baris `auth/ dashboard/   komponen khusus halaman login & dasbor`.
   - `ui/` → tambahkan `BerakhlakLogo, SortableHeader, TablePagination, TableSearch` ke daftar.
   - `layout/` → tambahkan `AppIcon`.
   - `utils/` → tambahkan `navigation.ts`.

**Verifikasi:** `git diff --stat` hanya menampilkan berkas `.md`.

**Commit:** `docs: sinkronkan struktur direktori dengan kode`

---

## Laporan akhir (wajib ditulis agent setelah selesai)

Laporkan dalam format ini:

```
Tugas 1: selesai / dilewati (alasan) — commit <hash>
Tugas 2: ... — aset yang TIDAK dipindah karena masih dirujuk: <daftar atau "tidak ada">
Tugas 3: ... — jumlah tes 125 -> N; method dibuang sebagai duplikat: <daftar + alasan>
Tugas 4: ... — panjang AllocateRooms: 237 -> N baris
Tugas 5: ...
Hasil akhir: php artisan test = <hasil>, pint --test = <hasil>, typecheck = <hasil>,
             test:unit = <hasil>, test:browser = <hasil>
Berkas pemilik yang tidak disentuh: masih berstatus M di git status? ya/tidak
```

Jangan menyatakan lulus untuk perintah yang tidak dijalankan.

---

## Di luar cakupan (jangan dikerjakan)

- Mengubah `frontend/tests/e2e/*` atau menjalankan `test:e2e`.
- Mengganti string status di frontend (`'pending'`, dll.) — sudah dijaga union type di `types/*.ts`.
- Mengubah label `'Selesai' / 'Sedang Menginap'` di `StayHistory.vue` — nilai turunan, bukan enum.
- Memecah `RoomAllocationTest.php` atau `CateringTest.php` meskipun panjang.
- Upgrade dependensi, perubahan UI/desain, atau perubahan skema.

---
---

# Bagian 2 — Merapikan isi kode

Hasil audit tingkat kode (2026-10-15) atas seluruh `app/`, `routes/`, `config/` dan `frontend/app/`.
Kesimpulan audit: kode **sudah cukup bersih** (controller 3–15 baris per method, tidak ada SQL mentah di
controller, tidak ada baris >120 karakter). Tugas di bawah adalah **duplikasi kecil dan inkonsistensi**
yang tersisa. Tiap tugas kecil, berdiri sendiri, dan diurutkan dari risiko paling rendah.

Aturan § 0 tetap berlaku. Tambahan khusus Bagian 2:

- **Teks yang dibaca pengguna tidak boleh berubah satu karakter pun** (label, pesan sukses/galat, judul).
  Label `Resolved` dan `Assigned` yang berbahasa Inggris **sengaja** demikian (ditetapkan di
  `domain.md`) — jangan diterjemahkan.
- **Tampilan tidak boleh berubah.** Jika mengganti kelas Tailwind, hasil CSS-nya harus setara.
- Sebelum mengedit sebuah berkas, **baca dulu seluruh berkas itu**. Potongan kode di rencana ini
  menunjukkan bentuk yang dituju; nomor baris bisa bergeser.
- Bila sebuah `grep` yang "harus kosong" ternyata berisi hasil yang tidak disebut rencana, berhenti dan
  laporkan.

Verifikasi standar Bagian 2 (jalankan di akhir **setiap** tugas, kecuali tugas menyebut lain):

```bash
# tugas backend
php artisan test && vendor/bin/pint --test
# tugas frontend
npm --prefix frontend run typecheck && npm --prefix frontend run test:unit \
  && npm --prefix frontend run test:browser
```

Sebelum Tugas 6, catat keluaran `php artisan test` (jumlah `passed`). Jumlah itu tidak boleh turun
(boleh naik bila tugas menambah tes).

---

## Tugas 6 — Backend: satu tempat untuk normalisasi profil penghuni

**Masalah:** fungsi `normalize()` yang identik (huruf kecil + rapikan spasi) ditulis dua kali, di
`app/Actions/AllocateRooms.php` dan `app/Actions/UpdateResidentProfile.php`. Kalau aturan normalisasi
berubah, mudah lupa salah satunya — dan alokasi akan salah mengelompokkan angkatan.

**Langkah:**

1. Di `app/Models/User.php`, tambahkan method statis publik **tepat setelah** `scopeResidents`:

   ```php
   /** Angkatan dan agama dibandingkan tanpa beda huruf besar/kecil dan spasi berlebih. */
   public static function normalizeProfileText(?string $value): string
   {
       return mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $value ?? '')));
   }
   ```

2. `app/Actions/AllocateRooms.php`: ganti `$this->normalize(` → `User::normalizeProfileText(` (2 tempat),
   lalu hapus method privat `normalize`. (`User` sudah di-import.)
3. `app/Actions/UpdateResidentProfile.php`: ganti `$this->normalize(` → `User::normalizeProfileText(`
   (2 tempat), lalu hapus method privat `normalize`. (`User` sudah di-import.)

**Verifikasi tambahan:**

```bash
grep -rn "function normalize" app        # harus kosong
grep -rn "normalizeProfileText" app      # 5 baris: 1 definisi + 4 pemakaian
```

**Commit:** `refactor(resident): satukan normalisasi angkatan dan agama di model User`

---

## Tugas 7 — Backend: scope `visibleTo` yang disalin tiga kali

**Masalah:** method `scopeVisibleTo` yang identik ada di `Complaint`, `LaundryOrder`, dan `LeavePermit`.

**Langkah:**

1. Buat `app/Models/Concerns/VisibleToOwner.php`:

   ```php
   <?php

   namespace App\Models\Concerns;

   use App\Enums\Role;
   use App\Models\User;
   use Illuminate\Database\Eloquent\Builder;

   /**
    * Penghuni hanya melihat baris miliknya; admin melihat semuanya.
    * Model pemakai wajib punya relasi user().
    */
   trait VisibleToOwner
   {
       public function scopeVisibleTo(Builder $query, User $user): void
       {
           if ($user->role === Role::Resident) {
               $query->whereBelongsTo($user);
           }
       }
   }
   ```

2. Di ketiga model (`app/Models/Complaint.php`, `LaundryOrder.php`, `LeavePermit.php`):
   - ubah `use HasFactory;` menjadi `use HasFactory, VisibleToOwner;`
   - tambahkan `use App\Models\Concerns\VisibleToOwner;` di blok import (urut abjad)
   - hapus method `scopeVisibleTo` (di `LeavePermit` beserta docblock satu barisnya)
   - hapus import `App\Enums\Role` **hanya jika** kata `Role::` tidak muncul lagi di berkas itu.

**Verifikasi tambahan:**

```bash
grep -rn "function scopeVisibleTo" app   # tepat 1 hasil: app/Models/Concerns/VisibleToOwner.php
php artisan test tests/Feature/RoleAccessTest.php tests/Feature/LeavePermitTest.php \
  tests/Feature/ComplaintTest.php tests/Feature/LaundryOrderTest.php
```

**Commit:** `refactor(models): pindahkan scope visibleTo ke trait VisibleToOwner`

---

## Tugas 8 — Backend: kerapian kecil

Tiga perubahan kecil, satu commit.

**8a. Urutan isi `app/Models/CateringMenu.php`.** Semua model lain berurutan: properti (`$fillable`,
`$casts`, `$attributes`) → relasi → scope → method lain. Di `CateringMenu`, method `ratingClosesAt` dan
`canBeRated` berada **di atas** `$fillable`. Pindahkan kedua method itu ke **akhir** kelas (setelah
`scopeWithRatingsFor`), tanpa mengubah isinya kecuali butir 8b.

**8b. Nilai cadangan zona waktu ganda.** Di `ratingClosesAt`, ubah
`(string) config('asrama.display_timezone', 'Asia/Jakarta')` menjadi
`(string) config('asrama.display_timezone')` — nilainya sudah ada di `config/asrama.php`.

**8c. Trait tak terpakai di `app/Http/Controllers/Controller.php`.** Cek dulu:

```bash
grep -rnE '\$this->(validate|authorize)\(' app
```

Jika **kosong**, hapus baris `use AuthorizesRequests, ValidatesRequests;` beserta kedua import-nya
(`Illuminate\Foundation\Auth\Access\AuthorizesRequests`, `Illuminate\Foundation\Validation\ValidatesRequests`).
Jika ada hasil, lewati 8c dan catat.

**Jangan** ubah `LaundryOrderController::update` meski terlihat bisa diringkas dengan `??` —
`array_key_exists` sengaja membedakan "cost dikirim bernilai null" dari "cost tidak dikirim".

**Commit:** `refactor(backend): rapikan urutan CateringMenu dan hapus trait tak terpakai`

---

## Tugas 9 — Frontend: satu fungsi untuk "halaman beranda per peran"

**Masalah:** ekspresi `role === 'admin' ? '/admin' : '/resident'` ditulis di tiga tempat:
`frontend/app/middleware/auth.global.ts`, `frontend/app/pages/login.vue`,
`frontend/app/components/auth/AuthLoggedInCard.vue`.

**Langkah:**

1. Di `frontend/app/utils/navigation.ts`, tambahkan tepat setelah fungsi `menusFor`:

   ```ts
   export function homePathFor(role?: Role): string {
     return role === 'admin' ? '/admin' : '/resident'
   }
   ```

2. Ganti ketiga pemakaian (fungsi di `utils/` di-auto-import Nuxt; tidak perlu `import`):
   - `auth.global.ts`: `const home = homePathFor(user.value?.role)`
   - `login.vue`: hapus variabel `home`, tulis `await navigateTo(homePathFor(user.value?.role))`
   - `AuthLoggedInCard.vue`: `:to="homePathFor(user.role)"`

3. Tambahkan tes di `frontend/tests/unit/navigation.test.ts` (ikuti gaya tes yang sudah ada di berkas
   itu, termasuk cara import-nya): `homePathFor('admin')` → `'/admin'`,
   `homePathFor('resident')` → `'/resident'`, `homePathFor(undefined)` → `'/resident'`.

**Verifikasi tambahan:** `grep -rn "'/admin' : '/resident'" frontend/app` → tepat 1 hasil (`navigation.ts`).

**Commit:** `refactor(frontend): satukan penentuan beranda per peran di homePathFor`

---

## Tugas 10 — Frontend: cara menentukan mode admin yang seragam

**Masalah:** `LeavePermitSection` dan `ComplaintSection` membaca `isAdmin` dari `useSession()`, sedangkan
`LaundrySection` dan `CateringSection` menerima prop `admin` dari halaman **dan** punya cadangan
`props.admin ?? isAdmin.value`. Cadangan itu tidak pernah terpakai: Vue mengubah prop boolean yang tidak
dikirim menjadi `false`, bukan `undefined`. Dua cara untuk hal yang sama membingungkan pembaca.
Middleware sudah menjamin halaman `/admin/*` hanya dibuka admin, jadi `isAdmin` dari sesi setara.

**Langkah (ikuti pola `LeavePermitSection.vue`):**

1. `frontend/app/components/laundry/LaundrySection.vue` dan `frontend/app/components/catering/CateringSection.vue`:
   - hapus blok `const props = defineProps<{ admin?: boolean }>()`
   - hapus baris `const adminMode = computed(...)`
   - ganti `adminMode.value` → `isAdmin.value` (di `<script>`) dan `adminMode` → `isAdmin` (di template).
2. `frontend/app/pages/admin/laundry.vue`: `<LaundrySection admin />` → `<LaundrySection />`.
   `frontend/app/pages/admin/catering.vue`: `<CateringSection admin />` → `<CateringSection />`.

**Verifikasi tambahan:**

```bash
grep -rn "adminMode\|admin?: boolean" frontend/app     # harus kosong
```

Lalu verifikasi standar frontend (seluruh `test:browser`, karena `roles.spec.ts` ikut menguji ini).

**Commit:** `refactor(frontend): mode admin laundry dan catering dibaca dari sesi`

---

## Tugas 11 — Frontend: kelas tombol kecil `.btn-sm`

**Masalah:** override `py-1! px-2.5! text-xs` (kadang ditambah `font-semibold`) disalin 7 kali di
`CateringTable.vue`, `ComplaintTable.vue`, dan `LeavePermitTable.vue`.

**Langkah:**

1. Di `frontend/app/assets/css/main.css`, di dalam `@layer components`, tambahkan **sebagai aturan
   terakhir** layer itu (setelah `.overline`, sebelum `}` penutup layer):

   ```css
     /* Ukuran ringkas untuk tombol aksi di baris tabel; dipakai bersama .btn / .btn-secondary / .btn-danger. */
     .btn-sm {
       @apply px-2.5! py-1! text-xs!;
     }
   ```

2. Di ketiga berkas, ganti:
   - `py-1! px-2.5! text-xs font-semibold` → `btn-sm`
   - `py-1! px-2.5! text-xs` → `btn-sm`

   Contoh: `class="btn py-1! px-2.5! text-xs font-semibold"` → `class="btn btn-sm"`.
   (`font-semibold` aman dibuang: `.btn`, `.btn-secondary`, `.btn-danger` sudah `font-semibold`.)
   **Jangan** ubah `btn-text py-1! px-2! text-xs` — ukurannya berbeda.

**Verifikasi tambahan:**

```bash
grep -rn "py-1! px-2.5!" frontend/app        # harus kosong
grep -rn "btn-sm" frontend/app | wc -l       # 8 (1 definisi + 7 pemakaian)
```

Lalu verifikasi standar frontend.

**Commit:** `refactor(frontend): tambah kelas btn-sm untuk tombol aksi tabel`

---

## Tugas 12 — Frontend: pakai `AlertMessage` di beranda admin

**Masalah:** `frontend/app/pages/admin/index.vue` menulis kotak galat sendiri (`<div v-if="error"
role="alert" ...>` dengan tombol "Coba lagi"), padahal `components/ui/AlertMessage.vue` sudah mendukung
`retryable` + `@retry` dan dipakai dengan cara itu di `pages/resident/room.vue`.

**Langkah:** ganti seluruh blok `<div v-if="error" role="alert" ...> ... </div>` (termasuk tombol di
dalamnya) dengan:

```vue
    <AlertMessage
      v-if="error"
      error="Data tidak dapat dimuat. Pastikan Laravel berjalan."
      retryable
      @retry="refresh()"
    />
```

Teks galat harus **persis** sama dengan sebelumnya.

**Verifikasi tambahan:** `grep -rn 'role="alert"' frontend/app` → hanya `components/ui/AlertMessage.vue`.
Jika `test:browser` gagal karena tes mencari kelas CSS lama (mis. `bg-red-50`), laporkan — jangan ubah tes.

**Commit:** `refactor(frontend): beranda admin memakai AlertMessage`

---

## Tugas 13 — Frontend: identifier berbahasa Inggris

**Masalah:** aturan proyek: identifier Inggris, teks pengguna Indonesia. Yang masih Indonesia:

| Lama | Baru | Lokasi (semua di `frontend/app/`) |
|---|---|---|
| nama ikon `beranda` | `home` | `components/layout/AppIcon.vue` (kunci `paths` + cadangan `paths.beranda`), `utils/navigation.ts` (2×) |
| nama ikon `kamar` | `room` | `AppIcon.vue`, `utils/navigation.ts` (2×), `pages/admin/index.vue`, `pages/resident/index.vue`, `components/room/MyRoomCard.vue` (2×), `components/room/StayHistory.vue` |
| id datalist `agama-options` | `religion-options` | `components/room/ResidentProfiles.vue`, `components/room/ResidentProfileForm.vue` |

**Hanya** identifier yang diganti. Label `'Beranda'`, `'Kamar'`, `'Buka kamar'`, `'Total kamar'`, dst.
adalah teks pengguna (huruf besar / kalimat) — **jangan diubah**.

**Langkah:**
- `AppIcon.vue`: kunci `beranda:` → `home:`, `kamar:` → `room:`, dan `paths.beranda` → `paths.home`.
- Di berkas lain: string `'beranda'` → `'home'`, `'kamar'` → `'room'`, `name="kamar"` → `name="room"`,
  `agama-options` → `religion-options`.

**Verifikasi tambahan:**

```bash
grep -rnE "['\"](beranda|kamar)['\"]|beranda:|kamar:|paths\.beranda|agama-options" frontend/app frontend/tests
# harus kosong
```

Jika ada hasil di `frontend/tests`, ganti juga di sana (itu identifier, bukan teks). Lalu verifikasi standar.

**Commit:** `refactor(frontend): ganti identifier ikon dan datalist ke bahasa Inggris`

---

## Tugas 14 — Frontend: kerapian kecil

Satu commit, empat perubahan kecil. Masing-masing tidak mengubah perilaku.

**14a. `frontend/app/composables/useTable.ts` — pembatalan debounce ditulis 4 kali.** Blok

```ts
if (debounceTimer !== undefined) {
  clearTimeout(debounceTimer)
}
```

muncul di `applySearch`, `sortBy`, `watch(search, ...)`, dan `onBeforeUnmount`. Tambahkan tepat di bawah
baris `let debounceTimer ...`:

```ts
function cancelDebounce() {
  if (debounceTimer !== undefined) {
    clearTimeout(debounceTimer)
  }
}
```

lalu ganti keempat blok itu dengan `cancelDebounce()`. Untuk `onBeforeUnmount`, tulis
`onBeforeUnmount(cancelDebounce)`.

**14b. `frontend/app/components/catering/CateringSection.vue`.**
- Di `saveMenu`, hindari non-null assertion `editingMenu.value!.id` dengan pola dari
  `LeavePermitSection.save`: simpan `const menu = editingMenu.value` di baris pertama fungsi, ubah
  `if (editingMenu.value)` → `if (menu)`, dan pakai `` `catering-menus/${menu.id}` ``. Cabang `else` tidak berubah.
- Di `startEdit`, hapus pembungkus `if (typeof window !== 'undefined') { ... }` dan sisakan baris
  `window.scrollTo({ top: 0, behavior: 'smooth' })` — aplikasi ini `ssr: false`, `window` selalu ada.

**14c. `frontend/app/types/session.ts` — alias `SessionUser` sama persis dengan `User`.** Hapus baris
`export type SessionUser = User` (dan baris kosong berlebih setelahnya). Di
`frontend/app/components/auth/AuthLoggedInCard.vue`, ganti `SessionUser` → `User` di import dan di prop.
Cek: `grep -rn SessionUser frontend/app frontend/tests` → kosong.

**14d. `frontend/app/components/room/RoomAllocation.vue` — dua computed kembar.** `incompleteNames` dan
`waitingNames` isinya sama kecuali properti yang dibaca. Hapus keduanya, tambahkan:

```ts
function namesOf(people: { name: string }[]): string {
  return people.map(person => person.name).join(', ')
}
```

dan di template ganti `{{ incompleteNames }}` → `{{ namesOf(result.incomplete) }}` serta
`{{ waitingNames }}` → `{{ namesOf(result.waiting) }}` (keduanya sudah berada di dalam `v-if="result"`).

**Commit:** `refactor(frontend): rapikan useTable, CateringSection, tipe sesi, dan RoomAllocation`

---

## Tugas 15 — Sinkronkan dokumentasi Bagian 2

Perbarui hanya bagian yang berubah:

1. `CLAUDE.md` § "Data & Domain": tambahkan satu poin
   `` - `App\Models\Concerns\VisibleToOwner` menyediakan scope `visibleTo($user)`: penghuni hanya melihat miliknya, admin melihat semuanya (dipakai `LeavePermit`, `Complaint`, `LaundryOrder`). `` (lewati bila Tugas 7 dilewati).
2. `.claude/skills/gojags-asrama-architecture/SKILL.md` blok "Struktur target", baris `Models/`: tambahkan
   baris di bawahnya `  Models/Concerns/     VisibleToOwner` (lewati bila Tugas 7 dilewati).

**Verifikasi:** `git diff --stat` hanya menampilkan berkas `.md`.

**Commit:** `docs: catat trait VisibleToOwner`

---

## Di luar cakupan Bagian 2 (sengaja tidak dikerjakan)

Ditemukan saat audit, tetapi **jangan** dikerjakan agent tanpa keputusan pemilik:

- Kelas Tailwind panjang di `pages/login.vue`, `MyRoomCard.vue` (tombol check-out), dan `AppSidebar.vue`
  — menyangkut desain; perlu dicek visual oleh manusia.
- Pola "simpan → `formKey++` → `goToFirstPage()` → `refresh()`" berulang di 4 komponen `*Section`;
  mengabstraksikannya justru menambah lapisan tanpa banyak manfaat.
- Prop penghitung `reloadTrigger` di `ResidentProfiles.vue`; alternatifnya `defineExpose` + template ref,
  tetapi pola sekarang sudah teruji dan mudah diikuti.
- Pesan "Anda belum memiliki alokasi kamar." di `CheckIn` dan `CheckOut` — dua aksi berbeda, wajar.
- `MyRoomController::show` menyusun array sendiri, bukan Resource — hanya dipakai satu endpoint.
- `CateringTable.vue` memakai `<h2 class="text-xl font-semibold">`, bukan `.section-title` — mengubahnya
  mengubah tampilan.

## Laporan akhir Bagian 2

```
Jumlah tes awal: php artisan test = <N passed>
Tugas 6:  selesai / dilewati (alasan) — commit <hash>
Tugas 7:  ...
Tugas 8:  ... — 8c dikerjakan? ya/tidak (alasan)
Tugas 9:  ...
Tugas 10: ...
Tugas 11: ...
Tugas 12: ...
Tugas 13: ... — berkas tes yang ikut diubah: <daftar atau "tidak ada">
Tugas 14: ...
Tugas 15: ...
Hasil akhir: php artisan test = <hasil>, pint --test = <hasil>, typecheck = <hasil>,
             test:unit = <hasil>, test:browser = <hasil>
Berkas pemilik (frontend/tests/e2e/*, playwright.e2e.config.ts) masih status M dan tidak disentuh? ya/tidak
```

Jangan menyatakan lulus untuk perintah yang tidak dijalankan.

---
---

# Bagian 3 — Keamanan akses

Hasil audit pemisahan peran & akses (2026-10-15). Pemisahan admin/penghuni, kepemilikan data, CSRF, dan
sesi **sudah aman** dan teruji (`tests/Feature/RoleAccessTest.php`). Bagian ini memperbaiki dua hal:

1. **Rate limit login hanya per email** (terbukti lewat uji):
   - percobaan ke banyak email berbeda dari satu sumber tidak pernah dibatasi (*password spraying*);
   - siapa pun bisa mengunci akun orang lain: 5 percobaan salah → pemilik akun dengan sandi **benar**
     ikut mendapat 429 selama satu menit.
   Keputusan stakeholder no. 18 (`docs/notes/DEDUKSI_JAWABAN_STAKEHOLDER.md`) meminta kunci
   **email + IP**. Masalahnya, semua permintaan lewat proxy Nuxt, sehingga Laravel melihat IP
   `127.0.0.1` untuk semua orang.
2. **Pengaturan produksi**: header anti-clickjacking dan daftar periksa deploy.

Aturan § 0 dan tambahan Bagian 2 tetap berlaku (teks pengguna dan tampilan tidak berubah).

**Di luar cakupan Bagian 3 — menunggu keputusan pemilik:** membatasi izin keluar, laundry, dan rating
hanya untuk penghuni yang sedang menginap (keputusan no. 10 & 17). Saat ini penghuni tanpa kamar atau
yang sudah check-out masih bisa melakukannya. **Jangan dikerjakan** sampai `domain.md` diperbarui.

---

## Tugas 16 — Rate limit login per email + IP, dengan IP asli klien

Ada tiga lapisan yang harus berubah bersama, dalam **satu commit**:

```
Peramban ──> Nuxt proxy ──(X-Forwarded-For = IP klien)──> Laravel (percaya 127.0.0.1) ──> limiter email|ip
```

### 16a. Proxy Nuxt meneruskan IP klien — dan menimpa kiriman klien

Klien bisa mengirim header `X-Forwarded-For` palsu untuk mengakali limiter. Karena itu proxy **selalu
menimpa** header tersebut dengan IP yang dilihat Nuxt sendiri.

1. `frontend/nuxt.config.ts`, ubah `runtimeConfig` menjadi:

   ```ts
   runtimeConfig: {
     laravelBaseUrl: 'http://127.0.0.1:8000',
     // true hanya bila Nuxt berada di belakang reverse proxy (mis. nginx) yang MENIMPA X-Forwarded-For
     // dengan IP klien ($remote_addr). Bawaan false: IP diambil dari koneksi langsung.
     trustForwardedFor: false,
   },
   ```

2. `frontend/server/routes/api/[...path].ts`, ganti seluruh isi menjadi:

   ```ts
   export default defineEventHandler(async (event) => {
     const path = getRouterParam(event, 'path') || ''
     // Tujuan tetap; jangan pernah menerima URL tujuan dari peramban.
     if (!/^[a-z0-9/-]+$/.test(path)) {
       throw createError({ statusCode: 404 })
     }
     const config = useRuntimeConfig(event)
     const base = config.laravelBaseUrl.replace(/\/$/, '')
     const query = getRequestURL(event).search
     // Laravel membatasi login per email + IP; IP ini menimpa X-Forwarded-For kiriman klien.
     const clientIp = getRequestIP(event, { xForwardedFor: Boolean(config.trustForwardedFor) }) || 'unknown'
     setResponseHeader(event, 'Cache-Control', 'no-store')
     return proxyRequest(event, `${base}/api/${path}${query}`, {
       headers: { 'accept': 'application/json', 'x-forwarded-for': clientIp },
     })
   })
   ```

   Satu-satunya perubahan perilaku: header `x-forwarded-for` ditambahkan. Komentar Inggris lama diganti
   Bahasa Indonesia agar sama dengan berkas lain.

### 16b. Laravel memercayai proxy Nuxt, hanya untuk header IP

`app/Http/Middleware/TrustProxies.php`, ganti isi kelas menjadi:

```php
class TrustProxies extends Middleware
{
    /**
     * Hanya proxy Nuxt di mesin yang sama. Laravel tidak boleh dapat diakses langsung dari luar.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies = ['127.0.0.1', '::1'];

    /**
     * Hanya alamat IP yang dipercaya; host/port/proto dari header tidak dipakai.
     */
    protected $headers = Request::HEADER_X_FORWARDED_FOR;
}
```

### 16c. Limiter memakai email + IP, ditambah batas longgar per IP

1. `config/asrama.php`, tambahkan entri (perhatikan komentar — wajib ditulis):

   ```php
       // Batas percobaan login per IP per menit. Longgar karena satu Wi-Fi asrama (satu IP) dipakai
       // hingga 150 penghuni yang login hampir bersamaan (keputusan stakeholder no. 18).
       'login_attempts_per_ip' => 200,
   ```

2. `app/Providers/RouteServiceProvider.php`, ganti definisi limiter `login` menjadi:

   ```php
   RateLimiter::for('login', function (Request $request) {
       $email = Str::lower((string) $request->input('email'));

       return [
           Limit::perMinute(5)->by('login:'.$email.'|'.$request->ip()),
           Limit::perMinute((int) config('asrama.login_attempts_per_ip'))->by('login-ip:'.$request->ip()),
       ];
   });
   ```

### 16d. Tes

Tes lama `SessionTest::test_sixth_login_attempt_within_a_minute_returns_429` **harus tetap lulus tanpa
diubah** (semua permintaan tes berasal dari IP yang sama).

Tambahkan dua tes baru di `tests/Feature/SessionTest.php` (setelah tes 429 yang ada). Permintaan tes
datang dari `127.0.0.1` (proxy tepercaya), jadi header `X-Forwarded-For` menentukan IP klien:

```php
public function test_failed_logins_from_another_ip_do_not_lock_out_the_account_owner(): void
{
    User::factory()->resident()->create([
        'email' => 'victim@example.id',
        'password' => 'secret123',
    ]);

    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $this->withHeader('X-Forwarded-For', '10.0.0.1')
            ->postJson('/api/login', ['email' => 'victim@example.id', 'password' => 'wrong'])
            ->assertUnprocessable();
    }

    $this->withHeader('X-Forwarded-For', '10.0.0.1')
        ->postJson('/api/login', ['email' => 'victim@example.id', 'password' => 'wrong'])
        ->assertTooManyRequests();

    $this->withHeader('X-Forwarded-For', '10.0.0.2')
        ->postJson('/api/login', ['email' => 'victim@example.id', 'password' => 'secret123'])
        ->assertOk();
}

public function test_many_emails_from_one_ip_hit_the_per_ip_limit(): void
{
    config(['asrama.login_attempts_per_ip' => 3]);

    for ($attempt = 1; $attempt <= 3; $attempt++) {
        $this->withHeader('X-Forwarded-For', '10.0.0.3')
            ->postJson('/api/login', ['email' => "spray{$attempt}@example.id", 'password' => 'wrong'])
            ->assertUnprocessable();
    }

    $this->withHeader('X-Forwarded-For', '10.0.0.3')
        ->postJson('/api/login', ['email' => 'spray4@example.id', 'password' => 'wrong'])
        ->assertTooManyRequests();
}
```

Catatan: `withHeader` menempel pada instance tes; tiap blok di atas memanggilnya ulang, sehingga aman.
Jika tes kedua gagal karena header dari tes lain terbawa, tambahkan `$this->flushHeaders();` sebelum
pemanggilan dengan IP berbeda — **jangan** mengubah kode aplikasi demi tes.

### Verifikasi Tugas 16

```bash
php artisan test tests/Feature/SessionTest.php
php artisan test && vendor/bin/pint --test
npm --prefix frontend run typecheck && npm --prefix frontend run test:unit \
  && npm --prefix frontend run test:browser
```

Verifikasi manual proxy (opsional tetapi disarankan; butuh MySQL hidup). Jalankan dua server di latar
belakang, lalu:

```bash
# 6 percobaan salah lewat proxy dengan header palsu yang BERBEDA-BEDA.
# Jika proxy menimpa header dengan benar, percobaan ke-6 tetap 429.
for i in 1 2 3 4 5 6; do
  TOKEN=$(curl -s -c /tmp/j -b /tmp/j http://127.0.0.1:3000/api/session | sed -E 's/.*"csrfToken":"([^"]+)".*/\1/')
  curl -s -o /dev/null -w "%{http_code}\n" -c /tmp/j -b /tmp/j \
    -H "X-CSRF-TOKEN: $TOKEN" -H "X-Forwarded-For: 1.2.3.$i" -H "Content-Type: application/json" \
    -d '{"email":"probe@example.id","password":"x"}' http://127.0.0.1:3000/api/login
done
# Harapan: 422 422 422 422 422 429
```

Jika tidak dijalankan, tulis "verifikasi manual proxy: tidak dijalankan" di laporan.

**Commit:** `fix(auth): batasi login per email dan IP asli klien melalui proxy`

---

## Tugas 17 — Header keamanan dan daftar periksa produksi

### 17a. Header anti-clickjacking di Nuxt

`frontend/nuxt.config.ts`, tambahkan properti tingkat atas (setelah `runtimeConfig`):

```ts
  // Halaman admin tidak boleh disisipkan dalam iframe situs lain (clickjacking).
  routeRules: {
    '/**': {
      headers: {
        'X-Frame-Options': 'DENY',
        'X-Content-Type-Options': 'nosniff',
        'Referrer-Policy': 'same-origin',
      },
    },
  },
```

**Jangan** menambahkan `Content-Security-Policy` — perlu diuji manual terhadap skrip Nuxt dan
`/js/theme.js`; catat sebagai saran di laporan.

Tambahkan tes `frontend/tests/browser/security-headers.spec.ts` (ikuti import dan gaya
`frontend/tests/browser/login.spec.ts`, termasuk fixture/mock yang dipakai berkas itu untuk membuka
`/login`):

```ts
test('halaman mengirim header anti-clickjacking', async ({ page }) => {
  // siapkan mock API seperti di login.spec.ts sebelum goto
  const response = await page.goto('/login')
  expect(response?.headers()['x-frame-options']).toBe('DENY')
  expect(response?.headers()['x-content-type-options']).toBe('nosniff')
})
```

Jika header tidak muncul di server dev Playwright, laporkan (jangan menghapus tes) — berarti
`routeRules` perlu diperiksa manusia.

### 17b. Daftar periksa produksi

Buat `docs/CHECKLIST_PRODUKSI.md` berisi tepat:

```markdown
# Daftar Periksa Produksi

Sebelum aplikasi dipasang di server:

- [ ] `.env` Laravel: `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` baru (`php artisan key:generate`).
- [ ] `.env` Laravel: `SESSION_SECURE_COOKIE=true` (situs diakses lewat HTTPS).
- [ ] Laravel hanya mendengarkan `127.0.0.1` dan tidak dapat diakses langsung dari luar; semua akses
      lewat Nuxt. `TrustProxies` hanya memercayai `127.0.0.1`.
- [ ] Bila Nuxt berada di belakang reverse proxy (nginx), proxy itu **menimpa** header
      (`proxy_set_header X-Forwarded-For $remote_addr;`) dan `NUXT_TRUST_FORWARDED_FOR=true`.
      Tanpa ini, rate limit login melihat semua pengguna sebagai satu IP.
- [ ] Jangan menjalankan `AccountSeeder` (akun demo `admin123`/`user123`). `DatabaseSeeder` sudah
      melewatinya di luar lingkungan `local`, `testing`, `e2e` — pastikan `APP_ENV=production`.
- [ ] Ganti sandi akun admin pertama setelah dibuat.
```

### 17c. `.env.example`

Tambahkan di **akhir** `.env.example`:

```
# Produksi: lihat docs/CHECKLIST_PRODUKSI.md (APP_DEBUG=false, SESSION_SECURE_COOKIE=true)
```

**Verifikasi:** verifikasi standar frontend (termasuk spec baru).

**Commit:** `chore(security): header anti-clickjacking dan daftar periksa produksi`

---

## Tugas 18 — Sinkronkan dokumentasi Bagian 3

Ganti kalimat rate limit di tiga tempat agar sesuai perilaku baru:

| Berkas | Kalimat lama (cari) | Ganti dengan |
|---|---|---|
| `CLAUDE.md` | `(maksimal 5 percobaan per menit per kombinasi email)` | `(maksimal 5 percobaan per menit per kombinasi email + IP klien, dan batas longgar per IP dari config/asrama.php; IP diteruskan proxy Nuxt lewat X-Forwarded-For)` |
| `AGENTS.md` | `(maksimal 5 percobaan per menit per kombinasi email)` | sama seperti baris di atas |
| `.claude/skills/gojags-asrama-architecture/references/domain.md` | `Login dibatasi 5 percobaan per menit (`throttle:5,1`).` | `Login dibatasi 5 percobaan per menit per email + IP, ditambah batas per IP (`asrama.login_attempts_per_ip`).` |

Di `CLAUDE.md` bagian "Alur Permintaan", pada kalimat proxy (`... memaksa Accept: application/json, dan
menyetel Cache-Control: no-store`), tambahkan `, serta menimpa X-Forwarded-For dengan IP klien` sebelum
tanda titik.

**Verifikasi:** `git diff --stat` hanya berkas `.md`.

**Commit:** `docs: perbarui aturan rate limit login`

---

## Laporan akhir Bagian 3

```
Tugas 16: selesai / dilewati (alasan) — commit <hash>
          tes lama 429 lulus tanpa diubah? ya/tidak; verifikasi manual proxy: <hasil atau "tidak dijalankan">
Tugas 17: ... — header muncul di test:browser? ya/tidak
Tugas 18: ...
Hasil akhir: php artisan test = <hasil>, pint --test = <hasil>, typecheck = <hasil>,
             test:unit = <hasil>, test:browser = <hasil>
Berkas pemilik (frontend/tests/e2e/*, playwright.e2e.config.ts) tidak disentuh? ya/tidak
```

---
---

# Bagian 4 — Audit lanjutan (hanya membaca, menghasilkan laporan)

Area yang **belum** diaudit. Berbeda dengan Bagian 1–3, tugas di sini **tidak mengubah kode aplikasi**.
Setiap tugas menghasilkan satu berkas laporan di `docs/audit/`. Perbaikan dikerjakan nanti, setelah
pemilik membaca laporan dan memutuskan.

Aturan khusus Bagian 4 (selain § 0):

- **Dilarang mengubah** berkas di `app/`, `routes/`, `config/`, `database/`, `frontend/app/`,
  `frontend/server/`, `composer.json`, `composer.lock`, `frontend/package.json`, `frontend/package-lock.json`.
  Jangan menjalankan `npm audit fix`, `composer update`, atau `npm install <paket>`.
- Tes atau skrip sementara untuk membuktikan temuan **boleh** dibuat, dengan nama berawalan `Tmp`
  (PHP) atau `tmp-` (TS/JS), dan **wajib dihapus** sebelum commit. Cek: `git status --short` tidak
  menampilkan berkas `Tmp*`/`tmp-*`.
- Setiap temuan wajib punya **bukti** yang bisa dicek ulang: path + nomor baris, keluaran perintah, atau
  hasil tes sementara. Temuan tanpa bukti ditulis di bagian "Dugaan (belum terbukti)".
- Jangan menebak tingkat keparahan secara berlebihan. Gunakan skala: **Tinggi** (data rusak/bocor atau
  fitur wajib tidak ada), **Sedang** (perilaku salah dalam kondisi tertentu), **Rendah** (kerapian/risiko kecil).
- Satu tugas = satu berkas laporan = satu commit `docs(audit): <topik>`.

Format umum setiap laporan:

```markdown
# Audit <Topik>

Tanggal: <YYYY-MM-DD> · Commit yang diaudit: <hash pendek dari `git rev-parse --short HEAD`>

## Ringkasan
<2–4 kalimat: kesimpulan utama dan jumlah temuan per tingkat>

## Temuan
| # | Tingkat | Temuan | Bukti | Saran perbaikan |
|---|---|---|---|---|

## Dugaan (belum terbukti)
<boleh kosong>

## Perintah yang dijalankan
<daftar perintah beserta ringkasan keluarannya>
```

Urutan pengerjaan sesuai prioritas: 19 → 20 → 21 → 22 → 23 → 24.

---

## Tugas 19 — Kesesuaian kode dengan keputusan stakeholder (prioritas tertinggi)

**Latar:** tabel "Keputusan Final" (§ 5) di `docs/notes/DEDUKSI_JAWABAN_STAKEHOLDER.md` menulis kolom
"Yang Dibangun / Dampak Teknis" seolah sudah dibangun, tetapi pemeriksaan awal menemukan banyak di
antaranya **tidak ada di kode** (mis. `is_active`, enum `Religion`, `CancelCheckOut`, `served_on`,
status `in_progress`/`cancelled`).

**Keluaran:** `docs/audit/19-keputusan-stakeholder.md`, dengan tabel khusus (menggantikan tabel Temuan umum):

```markdown
| No | Keputusan (ringkas) | Item teknis | Status | Bukti | Bertentangan dengan domain.md? |
|---|---|---|---|---|---|
```

**Langkah:**

1. Baca § 5 tabel keputusan no. 1–20. Pecah kolom "Yang Dibangun / Dampak Teknis" tiap nomor menjadi
   **item teknis terpisah** (satu kolom DB, satu enum, satu endpoint, satu Action, satu validasi, satu
   elemen UI = satu baris). Contoh no. 10 menjadi 4 baris: `leaves_at >= now()`, `returns_at > leaves_at`,
   larangan izin tumpang tindih, hanya penghuni aktif menginap.
2. Untuk tiap item, cari buktinya. Perintah bantu:
   ```bash
   grep -rnE "<kata kunci>" app database routes frontend/app tests
   php artisan route:list --path=api
   ```
   Kata kunci contoh: `is_active`, `Religion`, `AssignRoomSlot`, `MoveResidentRoom`, `UnassignRoomSlot`,
   `CancelCheckOut`, `in_progress`, `cancelled`, `rejection_reason`, `response`, `served_on`,
   `unique`, `after_or_equal:now`, `password`, `export`, `xlsx`.
3. Isi kolom **Status** dengan salah satu: `Ada` (lengkap, sebutkan berkas:baris), `Sebagian`
   (jelaskan yang kurang), `Tidak ada`, `Bertentangan` (kode melakukan kebalikannya — mis. no. 2/9/16:
   cek apakah `pendingResidents()` masih mengecualikan penghuni yang pernah check-out, apakah kolom
   `status` catering masih ada, apakah profil yang sudah dialokasikan masih dikunci).
4. Kolom **Bertentangan dengan domain.md?**: cek `.claude/skills/gojags-asrama-architecture/references/domain.md`.
   Jika `domain.md` menyatakan aturan sebaliknya (mis. domain.md: "Profil penghuni yang sudah punya slot
   terkunci", keputusan no. 7: boleh diedit kapan saja), tulis `Ya — domain.md § <n> baris <m>`.
5. **Ringkasan** laporan wajib memuat: jumlah item per status, dan daftar keputusan yang bertentangan
   dengan `domain.md` (pemilik harus memilih salah satu sebagai sumber kebenaran).
6. Tambahkan bagian akhir `## Dampak ke naskah skripsi`: jalankan
   ```bash
   grep -rnE "is_active|CancelCheckOut|impor|Excel|ekspor|ubah kata sandi|in_progress|served_on" ../skripsi/chapters
   ```
   dan daftarkan kalimat naskah yang menyebut fitur berstatus `Tidak ada`/`Sebagian`. Jangan ubah naskah.

**Jangan** mengubah `DEDUKSI_JAWABAN_STAKEHOLDER.md` maupun `domain.md`.

**Commit:** `docs(audit): kesesuaian kode dengan keputusan stakeholder`

---

## Tugas 20 — Kerentanan dependensi

**Latar:** pemeriksaan awal: `npm audit --omit=dev` → 14 kerentanan (2 critical, 12 high);
`composer audit` → 4 advisory `laravel/framework`, termasuk *CRLF injection in default email rule*
(high) yang **tidak punya perbaikan di Laravel 10** (versi 10 sudah tidak didukung).

**Keluaran:** `docs/audit/20-dependensi.md`, dengan tabel khusus:

```markdown
| Paket | Versi terpasang | Advisory (ID) | Tingkat | Dependensi langsung/tidak | Dipakai di produksi? | Jalur pemakaian di aplikasi | Versi perbaikan | Perlu upgrade mayor? |
|---|---|---|---|---|---|---|---|---|
```

**Langkah:**

```bash
composer audit --format=json > /tmp/composer-audit.json; composer audit --format=plain
npm --prefix frontend audit --omit=dev --json > /tmp/npm-audit.json
npm --prefix frontend audit --omit=dev
npm --prefix frontend audit                        # termasuk devDependencies, untuk perbandingan
npm --prefix frontend ls <paket>                   # untuk tiap paket rentan: siapa yang menariknya
composer show laravel/framework | head -5
```

1. Satu baris tabel per advisory. Untuk paket npm transitif, isi "Dependensi langsung/tidak" dengan
   rantai dari `npm ls` (mis. `nuxt > nitropack > <paket>`).
2. Kolom **Jalur pemakaian di aplikasi**: apakah fitur rentan benar-benar terpakai. Contoh untuk advisory
   aturan `email` Laravel: `grep -rn "'email'" app/Http/Requests` → dipakai di `LoginRequest`; aplikasi
   tidak mengirim email (`grep -rn "Mail::" app` kosong) → tulis itu sebagai bukti dampak.
3. Kolom **Perlu upgrade mayor?**: `Ya` bila versi perbaikan di luar rentang versi di `composer.json` /
   `package.json`.
4. Bagian akhir `## Opsi untuk pemilik`: (a) upgrade Laravel 10 → versi yang didukung (sebutkan versi
   minimum yang memperbaiki semua advisory), (b) upgrade paket npm, (c) menerima risiko dengan alasan.
   Untuk tiap opsi, sebutkan berkas yang kemungkinan terdampak — **jangan** menjalankannya.

**Commit:** `docs(audit): kerentanan dependensi`

---

## Tugas 21 — Integritas data dan konkurensi

**Keluaran:** `docs/audit/21-integritas-data.md` (format umum).

**Langkah:**

1. Untuk setiap migrasi di `database/migrations/2026_*`, buat tabel ringkas di laporan:
   kolom, tipe, nullable, default, indeks/unik, foreign key + `onDelete`.
2. Periksa invarian berikut — apakah **dijaga basis data** (constraint) atau hanya kode PHP. Tulis
   `DB` / `Kode saja` / `Tidak dijaga` beserta buktinya:
   - satu user paling banyak satu slot (`room_slots.user_id` unik?);
   - satu rating per (menu, user) (`catering_ratings` unik?);
   - nomor kamar unik; (room_id, position) unik;
   - paling banyak satu `stays` aktif (`checked_out_at` null) per user;
   - menghapus user/menu/kamar: apa yang terjadi pada baris anak (cascade, set null, atau gagal?);
   - `leave_permits.returns_at > leaves_at`.
3. Untuk tiap invarian "Kode saja", tulis tes sementara `tests/Feature/TmpIntegrityProbeTest.php` yang
   mencoba melanggarnya **langsung lewat model** (mis. `RoomSlot::factory()->create(['user_id' => $sudahPunyaSlot])`)
   dan catat apakah berhasil. Hapus berkas tes itu setelahnya.
4. **Deadlock:** pada awal sesi audit, `php artisan test` sekali menghasilkan 18 kegagalan
   `SQLSTATE[40001] Deadlock`. Jalankan `php artisan test` 5× berturut-turut dan catat hasilnya. Jika
   terulang, catat nama tes yang gagal dan cek apakah ada proses lain memakai basis data
   `gojags_asrama_testing` (`php artisan serve`, E2E). Jangan mengubah kode.
5. Periksa semua `DB::transaction(..., 3)`: apakah semua jalur yang mengubah `room_slots` lewat
   `RoomLocks`? (`grep -rn "room_slots\|RoomSlot::\|->slot()" app`.)

**Commit:** `docs(audit): integritas data dan konkurensi`

---

## Tugas 22 — Kualitas dan cakupan tes

**Keluaran:** `docs/audit/22-cakupan-tes.md`, dengan tabel khusus:

```markdown
| Bagian domain.md | Aturan / pesan | Tes yang mencakup (berkas::method) | Kuat/Lemah | Catatan |
|---|---|---|---|---|
```

**Langkah:**

1. Untuk setiap aturan dan **setiap teks pesan** di `.claude/skills/gojags-asrama-architecture/references/domain.md`,
   cari tes yang mencakupnya: `grep -rn "<potongan teks pesan>" tests frontend/tests`.
2. **Kuat** = tes memeriksa hasil (isi JSON, perubahan basis data, teks pesan persis). **Lemah** = tes
   hanya memeriksa kode status, atau tidak ada tes. Aturan tanpa tes ditulis `—` di kolom tes.
3. Bandingkan dengan daftar "Minimum per fitur" di `references/testing.md` § 10; tulis butir yang belum
   terpenuhi per fitur.
4. Jalankan dan catat jumlahnya: `php artisan test`, `npm --prefix frontend run test:unit`,
   `npm --prefix frontend run test:browser`. (Tidak perlu `test:e2e`.)
5. Opsional: jika `php -m | grep -i -E "xdebug|pcov"` ada, jalankan
   `php artisan test --coverage --min=0` dan lampirkan persentase per berkas di `app/`. Jika tidak ada,
   tulis "coverage tidak diukur (xdebug/pcov tidak terpasang)".

**Commit:** `docs(audit): kualitas dan cakupan tes`

---

## Tugas 23 — Performa di bawah beban

**Target dari keputusan no. 18:** 156 penghuni, 100–150 login serentak, setiap halaman melakukan polling
tiap 15 detik (`frontend/app/composables/usePolling.ts`).

**Keluaran:** `docs/audit/23-performa.md` (format umum) dengan tambahan tabel:

```markdown
| Endpoint | Jumlah query (data seed) | Waktu (ms) | Dipolling? | Perkiraan permintaan/menit saat 150 pengguna |
|---|---|---|---|---|
```

**Langkah:**

1. Hitung jumlah query per endpoint dengan tes sementara `tests/Feature/TmpQueryCountTest.php`:
   ```php
   DB::enableQueryLog();
   $this->actingAs($admin)->getJson('/api/rooms?per_page=100')->assertOk();
   $count = count(DB::getQueryLog());
   fwrite(STDERR, "\n/api/rooms: {$count} query");
   ```
   Jalankan dengan `$this->seed()` agar ada 78 kamar, lalu isi beberapa slot. Ukur semua endpoint `GET`
   di `routes/api.php` untuk peran yang relevan, dengan `per_page` 10 dan 100. Jumlah query yang naik
   seiring jumlah baris = indikasi N+1. Hapus berkas tes setelahnya.
2. Catat khusus: `RoomController::monitoring()` memuat **semua** kamar di setiap permintaan `/api/rooms`
   (yang dipolling). Ukur waktunya.
3. Hitung beban polling: komponen apa saja yang memanggil `usePolling` per halaman
   (`grep -rn "usePolling" frontend/app`), kalikan 150 pengguna × 4 permintaan/menit.
4. Periksa indeks untuk kolom yang dipakai `sort`/`search` di `TableQuery` (bandingkan daftar `->sort([...])`
   dan `->search([...])` tiap controller dengan indeks di migrasi).
5. **Jangan** menjalankan uji beban terhadap server bersama/produksi. Uji beban lokal opsional; jika
   dilakukan, sebutkan alatnya dan parameternya.

**Commit:** `docs(audit): performa di bawah beban`

---

## Tugas 24 — Pemeriksaan ringan: aksesibilitas, penanganan galat, kesiapan deploy

**Keluaran:** `docs/audit/24-ringan.md` dengan tiga bagian (masing-masing tabel Temuan umum).

**24a. Aksesibilitas & UI** (gunakan skill `ui-ux-pro-max` bila tersedia untuk agent; jika tidak, pakai
daftar ini):
- setiap `<input>`/`<select>`/`<textarea>` punya label (`grep -n "<input\|<select\|<textarea" -r frontend/app`
  lalu cek pembungkus `<label>` atau `aria-label`);
- tombol ikon tanpa teks punya `aria-label`;
- `alt` pada `<img>`;
- urutan heading (`h1` → `h2` → `h3`) per halaman;
- tampilan lebar 375 px: jalankan `npm --prefix frontend run test:browser` lalu, bila memungkinkan,
  ambil screenshot halaman utama di viewport 375×812 dengan Playwright (skrip sementara `tmp-*.mjs`,
  dihapus setelahnya; screenshot simpan di `docs/audit/screenshots/`).

**24b. Penanganan galat & logging:**
- `app/Exceptions/Handler.php`: apa yang dikembalikan untuk 500 saat `APP_DEBUG=false`
  (tes sementara dengan `config(['app.debug' => false])` dan rute yang melempar exception);
- `config/logging.php`: kanal bawaan, rotasi, lokasi;
- apakah sandi/token bisa ikut tercatat (cek `$dontFlash` di Handler, `TrimStrings` except).

**24c. Kesiapan build & deploy:**
- jalankan `npm --prefix frontend run build` dan catat hasil/ukuran `.output`;
- cocokkan dengan `docs/CHECKLIST_PRODUKSI.md` (dibuat di Tugas 17; jika belum ada, tulis "Tugas 17 belum dikerjakan");
- adakah petunjuk backup basis data dan cara menjalankan Nuxt + Laravel di server
  (`grep -rniE "backup|pm2|systemd|nginx" README.md AGENTS.md docs`).

**Commit:** `docs(audit): aksesibilitas, penanganan galat, dan kesiapan deploy`

---

## Laporan akhir Bagian 4

```
Tugas 19: laporan docs/audit/19-keputusan-stakeholder.md — commit <hash>
          item: Ada <n> · Sebagian <n> · Tidak ada <n> · Bertentangan <n>; bertentangan dengan domain.md: <n>
Tugas 20: ... — critical <n>, high <n>; perlu upgrade mayor: <paket>
Tugas 21: ... — invarian "Kode saja"/"Tidak dijaga": <daftar>; deadlock terulang? ya/tidak (<x>/5)
Tugas 22: ... — aturan tanpa tes: <n>; tes lemah: <n>
Tugas 23: ... — endpoint dengan indikasi N+1: <daftar>
Tugas 24: ... — temuan Tinggi: <n>
Berkas Tmp*/tmp-* tersisa? ya/tidak · Kode aplikasi berubah? ya/tidak (harus "tidak")
```
