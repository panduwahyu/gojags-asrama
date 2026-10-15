# Prompt untuk agen rewrite

Empat prompt siap tempel. Jalankan agen dengan direktori kerja di root repo `gojags-asrama/`.
Pakai **Prompt 1** untuk memulai. Bila agen berhenti di tengah jalan, pakai **Prompt 2**. Bila konteks
agen kecil dan ia sering "lupa", pakai **Prompt 3** satu tugas per sesi. Setelah semua tugas selesai,
jalankan **Prompt 4** di sesi baru sebagai pemeriksa.

---

## Prompt 1 — Mulai

```text
Kamu adalah software engineer yang me-rewrite repo ini (Laravel 10 + Nuxt 4) ke struktur baru.

Sebelum menulis kode apa pun, baca dua berkas ini sampai habis:
1. REWRITE_TASKS.md  — daftar tugas berurutan dan aturan umum
2. .claude/skills/gojags-asrama-architecture/SKILL.md  — arsitektur target

Lalu kerjakan tugas di REWRITE_TASKS.md satu per satu, berurutan, mulai dari T00.

Untuk SETIAP tugas, lakukan persis urutan ini:
1. Tulis satu baris: "Mulai <ID> — <judul>".
2. Buka dan baca setiap berkas yang disebut di bagian "Baca dulu" tugas itu. Jangan mengandalkan ingatan.
3. Kerjakan hanya yang tertulis di tugas itu. Jangan menambah fitur, pustaka, atau berkas lain.
4. Jalankan perintah di bagian "Selesai bila" dan baca keluarannya.
5. Bila gagal: perbaiki kodenya, lalu jalankan lagi. Jangan mengubah atau menghapus tes agar lulus.
   Bila sudah tiga kali gagal, BERHENTI dan laporkan pesan error lengkapnya.
6. Bila lulus: jalankan `git add -A` lalu `git commit -m "rewrite(<ID>): <ringkasan singkat>"`.
7. Tulis satu baris: "Selesai <ID>", lalu lanjut ke tugas berikutnya.

Aturan yang tidak boleh dilanggar:
- Jangan meniru pola kode lama. Kode lama hanya untuk memahami perilaku dan menyalin teks antarmuka.
- Nama kelas, variabel, kolom, URL, dan berkas berbahasa Inggris. Teks yang dibaca pengguna berbahasa
  Indonesia. Pesan dalam tanda kutip di references/domain.md disalin persis, huruf demi huruf.
- Dilarang di kode baru: DB::table(, expectsJson(, view(, $request->validate( di controller, string
  status mentah di luar enum, baris lebih dari 120 karakter.
- Jangan mengubah apa pun di luar folder repo ini. Jangan menyentuh folder ../skripsi/.
- Jangan pernah menulis "lulus" atau "selesai" untuk perintah yang tidak kamu jalankan. Bila sebuah
  perintah tidak bisa dijalankan di lingkunganmu, katakan itu apa adanya.
- Bila instruksi tidak jelas atau dua dokumen bertentangan, BERHENTI dan tanyakan. Jangan menebak.

Berhenti hanya bila: semua tugas sampai E3 selesai, atau kamu terkena salah satu kondisi BERHENTI di atas.
Di akhir, tulis laporan sesuai bagian "Laporan akhir" pada tugas E3.
```

---

## Prompt 2 — Lanjutkan setelah terhenti

```text
Kamu melanjutkan pekerjaan rewrite repo ini yang terhenti.

1. Baca REWRITE_TASKS.md dan .claude/skills/gojags-asrama-architecture/SKILL.md sampai habis.
2. Jalankan `git log --oneline -40` dan cari commit berawalan "rewrite(<ID>)". ID terakhir adalah tugas
   terakhir yang sudah selesai.
3. Jalankan `git status`. Bila ada perubahan yang belum di-commit, itu sisa tugas yang terputus:
   baca perubahannya dengan `git diff`, lalu selesaikan tugas itu (jangan dibuang).
4. Jalankan `composer test`. Bila ada yang merah, perbaiki dulu sebelum melanjutkan.
5. Lanjutkan dari tugas berikutnya dengan urutan kerja dan aturan yang sama seperti tertulis di
   "Aturan umum" REWRITE_TASKS.md: baca "Baca dulu", kerjakan, jalankan "Selesai bila", commit.

Jangan mengulang tugas yang sudah punya commit. Jangan menulis "lulus" untuk perintah yang tidak kamu
jalankan. Bila tiga kali gagal pada satu tugas atau instruksi tidak jelas, berhenti dan laporkan.
```

---

## Prompt 3 — Satu tugas per sesi (untuk agen berkonteks kecil)

Ganti `<ID>` dengan ID tugas, misalnya `B5`.

```text
Kerjakan SATU tugas saja di repo ini: tugas <ID> pada berkas REWRITE_TASKS.md.

Langkah:
1. Baca bagian "Aturan umum" di REWRITE_TASKS.md.
2. Baca bagian tugas <ID> di REWRITE_TASKS.md.
3. Baca setiap berkas yang disebut di "Baca dulu" tugas itu (berkasnya ada di
   .claude/skills/gojags-asrama-architecture/). Baca hanya bagian (§) yang disebut.
4. Lihat satu fitur yang sudah selesai di repo sebagai contoh bentuk (mis. app/Http/Controllers/
   LeavePermitController.php bila sudah ada) dan ikuti gayanya.
5. Kerjakan hanya yang tertulis di tugas <ID>.
6. Jalankan perintah "Selesai bila". Bila gagal, perbaiki kode (bukan tesnya) dan ulangi; setelah tiga
   kali gagal, berhenti dan tampilkan error lengkapnya.
7. Bila lulus: `git add -A` lalu `git commit -m "rewrite(<ID>): <ringkasan>"`.

Larangan: DB::table(, expectsJson(, view(, validate( di controller, string status di luar enum, baris
lebih dari 120 karakter, mengubah berkas di luar lingkup tugas, menyentuh ../skripsi/.

Akhiri dengan laporan singkat: berkas yang dibuat/diubah/dihapus, keluaran perintah verifikasi, dan hal
yang belum bisa kamu verifikasi. Jangan mengerjakan tugas lain.
```

---

## Prompt 4 — Audit akhir (sesi baru, setelah E3)

```text
Kamu adalah peninjau kode. Tugasmu MEMERIKSA hasil rewrite repo ini, bukan menulis fitur baru.

Baca dulu seluruh isi folder .claude/skills/gojags-asrama-architecture/ (SKILL.md dan semua berkas di
references/). Itu adalah standar yang dipakai untuk menilai.

Periksa berurutan dan catat setiap temuan dengan format `berkas:baris — masalah — perbaikan`:

A. Kelengkapan
   - Setiap tugas di REWRITE_TASKS.md punya commit "rewrite(<ID>)" (`git log --oneline`).
   - Setiap berkas pada pohon "Struktur target" di SKILL.md ada di repo.
   - Tidak ada satu pun butir "Dihapus tanpa pengganti" di references/migration-map.md yang tersisa.

B. Perilaku
   - Untuk tiap butir bernomor di references/domain.md, tunjukkan tes yang mengujinya (nama berkas +
     nama method). Butir tanpa tes adalah temuan.
   - Setiap pesan dalam tanda kutip di domain.md muncul persis sama di kode (`grep -rn "<pesan>" app`).
   - Rute di `php artisan route:list --path=api` sama dengan daftar di references/backend.md § 1.
   - Field tiap Resource di app/Http/Resources sama dengan tipe di frontend/app/types.

C. Keterbacaan (aturan di SKILL.md § "Aturan keterbacaan")
   - Jalankan semua perintah grep/awk pada tugas E3 di REWRITE_TASKS.md; semuanya harus kosong.
   - Controller: tidak ada method lebih dari ±15 baris, tidak ada query mentah, tidak ada format tanggal.
   - Komponen Vue: tidak ada berkas lebih dari ±150 baris; satu elemen per baris; elemen dengan lebih
     dari dua atribut memecah atributnya per baris; tidak ada ternary bersarang di template.
   - Tidak ada blok kode yang sama muncul di dua berkas.
   - Tidak ada `any` atau `as` yang tidak perlu di TypeScript.

D. Verifikasi
   - Jalankan: `composer test`, `vendor/bin/pint --test`, lalu di `frontend/`: `npm run typecheck`,
     `npm run test:unit`, `npm run test:browser`, `npm run build`.
   - Bila PHP dan basis data `*_e2e` tersedia: `npm run test:e2e`.
   - Cocokkan tes dengan references/testing.md: tiap fitur memenuhi § 10, spec wajib di § 6 ada semua,
     tidak ada pelanggaran aturan § 8, dan tidak ada aturan yang diuji berulang di beberapa lapis.
   - Tempelkan ringkasan keluaran tiap perintah apa adanya. Untuk perintah yang tidak bisa dijalankan,
     tulis "TIDAK DIJALANKAN" beserta sebabnya.

Setelah daftar temuan lengkap, perbaiki temuan satu per satu. Tiap perbaikan: ubah kode, jalankan ulang
tes terkait, lalu commit dengan pesan "rewrite(audit): <ringkasan>". Jangan mengubah tes agar lulus, dan
jangan mengubah berkas di .claude/skills/ maupun ../skripsi/.

Ulangi pemeriksaan A–D sampai tidak ada temuan baru. Akhiri dengan laporan: jumlah temuan, yang sudah
diperbaiki, yang sengaja dibiarkan beserta alasannya, dan perintah yang tidak bisa dijalankan.
```
