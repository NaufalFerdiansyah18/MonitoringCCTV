# Prompt Implementasi — Langkah 10: Poin Perlu Konfirmasi (PRD §10)

Gunakan prompt ini untuk langkah kesepuluh implementasi sistem monitoring CCTV: menjawab dan mengunci **bagian 10. Poin Perlu Konfirmasi** pada `PRD.md`, lalu menerapkan hasil keputusan ke kode (bila ada yang berubah).

---

## Instruksi

1. **Baca dulu `PRD.md`**, terutama bagian **2. Aktor & Role**, **3. Master Data**, **4. Generator RTSP**, **5. Halaman / Alur**, **6. Skema Database**, dan **10. Poin Perlu Konfirmasi**.
2. Jangan mengubah atau menimpa: `PRD.md`, `PROMPT_STEP_1.md` s.d. `PROMPT_STEP_9.md`, dan seluruh hasil Langkah 1–9 (migrasi, model, factory, seeder, auth/RBAC, masterdata, `RtspGenerator`, `StreamManager`, `RecordingManager`, `PtzService`, `RtspProber`, `RecordingExporter`, command `cctv:monitor`/`cctv:recorder`/`cctv:urls`, middleware `EnsureSuperadmin`, controller, view, test yang sudah ada).
3. **Langkah ini dimulai dengan konfirmasi (wajib)**: jangan mengubah kode apa pun sebelum user menjawab keempat poin di bagian "Konfirmasi Sebelum Mulai". Setelah dijawab, terapkan **hanya** perubahan yang sesuai jawaban; poin yang sudah terpenuhi oleh implementasi saat ini cukup dikunci dengan test/dokumentasi bila perlu, tidak perlu dirombak.
4. Ikuti konvensi yang sudah ada: PHPUnit (tanpa Pest) di `tests/Feature`, DB sqlite in-memory di `phpunit.xml`, gaya kode Laravel + Pint, layout sidebar `resources/views/layouts/app.blade.php`.

## Konteks (KONDISI SAAT INI — audit dulu, jangan asumsi)

### Poin 1 — Unit RO
- `Unit::KATEGORI = ['kebun', 'pks', 'ro']`.
- Superadmin: `allowedUnitCategories()` = semua kategori. Teknis: kategori dari `TechnicalGroupUnitCategory` grup-nya; bila tanpa grup → kosong.
- `TechnicalGroupSeeder` hanya membuat grup `tanaman → ['kebun']` dan `tekpol → ['pks']` — **tidak ada** grup dengan kategori `ro`.
- Validasi `TechnicalGroupController` masih mengizinkan `ro` masuk ke grup teknis (`Rule::in(Unit::KATEGORI)`), jadi saat ini RO hanya superadmin **secara de facto (via seeder), bukan aturan yang dipaksakan (enforced)**.

### Poin 2 — Substream (subtype)
- `CCTV_SUBTYPE=0` (`config/cctv.php`, `.env`) dipakai **global** oleh `RtspGenerator` di semua jalur: liveview grid (`StreamManager`), rekaman (`RecordingManager`/`cctv:recorder`), dan prober (`RtspProber`).
- Tidak ada pembeda "grid → substream ringan" vs "fullscreen/rekam → stream utama". Ubah `RtspGenerator` **dilarang** (format dikekang Langkah 4); perubahan dilakukan di pemanggil (`StreamManager::start`, dst.) dengan menambah parameter `subtype` override bila jawaban memilih grid ringan.

### Poin 3 — Filter kategori di halaman utama
- Dashboard saat ini memfilter **unit** berdasar kategori unit (`DashboardController::index`: `WhereIn('kategori', allowedUnitCategories())`), antar muka "pilih unit → DVR → kamera".
- `camera.kategori` (nullable, kolom ada) hanya ditampilkan sebagai badge/label — **belum ada filter berdasar kategori kamera**, dan bentuk `dvrs.cameras.create/edit` sudah menyediakan isian kategori per kamera.

### Poin 4 — Kategori campuran dalam satu DVR
- `kameras.kategori` menempel di **kamera** (bukan DVR), CRUD `CameraController::validated()` mengizinkan `kategori` nullable per kamera — **memang sudah memungkinkan** kamera 1–8 PKS & 9–16 Bioglas pada DVR yang sama. Tidak ada constraint yang menghalangi.

---

## Konfirmasi Sebelum Mulai (Wajib — tanya ke user, jangan asumsi)

Tanyakan keempat poin berikut; sertakan rekomendasi default. **Jangan melanjutkan ke Tugas sebelum dijawab.**

1. **Unit RO — hanya superadmin?**
   - (a) Ya, jadikan aturan (enforced): `ro` **tidak boleh** dipilih sebagai kategori grup teknis (validasi tolak `ro`), dan tetap tidak ada grup sw-seed untuknya — *rekomendasi*.
   - (b) Ya, tapi cukup kondisi faktual saat ini (seeder tidak mengasosiasikan `ro`), tanpa mengubah validasi.
   - (c) Tidak — `ro` boleh dimasukkan ke grup teknis seperti kategori lain.

2. **Substream untuk grid?**
   - (a) Grid memakai substream (`subtype=1`) supaya beban lebih ringan; fullscreen/rekam tetap stream utama (`subtype=0`) — *rekomendasi*; tambahkan `CCTV_GRID_SUBTYPE` di `config` + `.env`/`.env.example`, `StreamManager::start` menerima override subtype, `RecordingManager` dan `RtspProber` tetap memakai `config('cctv.subtype')`.
   - (b) Semua memakai stream utama (`subtype=0`) — tidak ada perubahan kode (selain memastikan `CCTV_SUBTYPE=0`).
   - (c) Semua memakai satu nilai global yang bisa diubah (perilaku sekarang) — tanpa perubahan.

3. **Filter beranda berdasarkan kategori kamera?**
   - (a) Ya — tambah filter kategori kamera pada halaman utama (beranda) beserta server-side filtering, misal query `?kategori=...`; list kategori diambil dari `Unit::KATEGORI` atau union kategori kamera yang ada — *rekomendasi*.
   - (b) Tidak — cukup kolom kategori pada form + badge di daftar (kondisi sekarang), tanpa filter.
   - (c) Alternatif lain (misal grup/panel per kategori) — jelaskan.

4. **Kategori campuran dalam satu DVR?**
   - *(Asumsi PRD: boleh — kategori menempel di kamera, dan saat ini sudah bisa)*
   - (a) Boleh, pertahankan perilaku sekarang, kunci dengan test kecil (satu DVR, dua kamera kategori berbeda) — *rekomendasi*.
   - (b) Harus seragam satu kategori per DVR — perlu perubahan (validasi/skema); jelaskan dampaknya (melanggar asumsi PRD, butuh keputusan skema).

---

## Tugas (jalankan sesuai jawaban konfirmasi; coretan poin yang jawabannya "tidak berubah" boleh dilewati)

### Tugas A — Unit RO: aturan akses (jawaban 1)
- Bila **(a) enforce**:
  - Ubah validasi grup teknis (`TechnicalGroupController::validateGroup`) atau kelas penanganan lain yang konsisten agar kategori `ro` **ditolak** untuk grup teknis dengan pesan jelas (misal "Unit kategori RO hanya untuk superadmin").
  - Pastikan konsisten di sisi model bila ada helper pemilih kategori (`kategoris` di `create`/`edit` view) — `ro` disembunyikan/dinonaktifkan di form grup teknis.
  - Test baru (mis. perluas `AuthRbacTest` atau file baru `tests/Feature/CctvConfirmationTest.php`): membuat/update grup teknis dengan `ro` → validasi menolak; update dengan `kebun`/`pks` → diterima; superadmin tetap `allowedUnitCategories()` berisi `ro`.
- Bila **(b)**: tidak ada perubahan; cek masih konsisten (opsional tambah test faktual bahwa tidak ada grup teknis berisi `ro` pada seeder).
- Bila **(c)**: tidak ada perubahan kode selain dokumentasi; pastikan `allowedUnitCategories()` perilaku default tetap.

### Tugas B — Substream grid (jawaban 2)
- Bila **(a) grid ringan**:
  - Tambah `config/cctv.php`: `grid_subtype` → `env('CCTV_GRID_SUBTYPE', 1)`; tambah `CCTV_GRID_SUBTYPE=1` di `.env` dan `.env.example` (komentar singkat).
  - `StreamManager::start(Camera, mode, ?int $subtype = null)` — saat `null` gunakan `config('cctv.subtype')` (main), agar **rekaman/probe/backward-compat tidak berubah**; endpoint `liveview.start` mengoper subtype grid dari config untuk tile grid.
  - JS halaman liveview / dashboard tetap memakai `liveview.start` yang sama (tanpa perubahan protokol bila cukup via server session config) — jelaskan mekanisme yang dipakai; pastikan `RtspGenerator` tetap dan format URL sama.
  - Test: `StreamManager::start` dengan override subtype menghasilkan URL `subtype=1` di HLS/DB (via stub runner), tanpa override memakai `config('cctv.subtype')`; `RecordingManager`/`RtspProber` tidak berubah (URL `subtype=0`).
- Bila **(b)**: pastikan `CCTV_SUBTYPE=0` di `.env`/`.env.example` dan test format URL `subtype=0` (sudah ada di `RtspGeneratorTest`).
- Bila **(c)**: tanpa perubahan; pastikan dokumentasi `.env` menjelaskan nilai global.

### Tugas C — Filter kategori kamera di beranda (jawaban 3)
- Bila **(a) dengan filter**:
  - `DashboardController::index`: terima `?kategori=`; daftar kategori pilihan dari `Unit::KATEGORI` (atau union kategori kamera pada unit terpilih — pilih yang paling rapi); filter kamera pada unit terpilih berdasar `camera.kategori` **di sisi server**; tetap hormati RBAC (`allowedUnitCategories()` unit + kategori kamera).
  - View dashboard: tambah select filter kategori (kosong = semua) + badge hasil; safe untuk teknis (hanya kategori dalam jangkauannya).
  - Test: superadmin filter `?kategori=pks` hanya menampilkan kamera berkategori `pks`; kategori lain tidak muncul; value tidak valid → tetap daftar kosong (atau abaikan, konsisten); teknis tetap tak melihat unit di luar kategori grup-nya.
- Bila **(b)**: tanpa perubahan.

### Tugas D — Kategori campuran (jawaban 4)
- Bila **(a) boleh**:
  - Tambah test kecil (misal di file test tantangan kategori): satu `Dvr` dengan dua `Camera` `kategori` berbeda (`pks`, `ro`, atau nilai lain) — tidak ada error, relasi/CRUD masih berfungsi, dashboard menampilkan badge berbeda.
- Bila **(b) seragam**: jelaskan dampak + rancang keputusan (validasi `Camera` vs `Dvr`; konsekuensi ke dashboard/rekam/RBAC) dan **tanyakan dulu ke user** sebelum ubah skema.

---

## Verifikasi Wajib di Akhir (seluruh langkah)

Jalankan dan pastikan sukses, lalu laporkan:

- `php artisan test` (seluruh suite lama + baru hijau)
- `php artisan migrate:fresh --seed` (hanya bila ada migrasi/seed baru di Tugas B/C; bila tidak ada perubahan skema, jalankan `php artisan migrate:status` saja dan laporkan "tidak ada perubahan skema")
- `vendor/bin/pint`
- `php artisan route:list` (bila ada route/perubahan query beranda)
- *(Opsional)* verifikasi manual di browser: beranda tanpa filter, dengan filter kategori; grid liveview dengan substream bila disetujui.

## Batasan Lain

- **Tanpa dependensi tambahan**; tanpa migrasi kecuali benar-benar diperlukan oleh keputusan poin 4 (yang harus dikonfirmasi ulang).
- Jangan mengubah `RtspGenerator` (format URL), `CctvTestController`, `StreamManager` di luar parameter subtype yang disetujui, route `liveview/*`, dan seluruh test lama — harus tetap hijau.
- Jangan menambahkan kolom `rtsp_url`; URL tetap digenerate on-the-fly.
- Jangan menambahkan komentar yang tidak perlu.
- Jangan memulai server untuk verifikasi otomatis — cukup testing; verifikasi manual dilakukan terpisah.
- Jika ada ketidakjelasan pada "Konfirmasi Sebelum Mulai", **tanyakan dulu** — jangan asumsi. Begitu pula bila keputusan user berkonflik dengan batasan PRD (misal poin 4b), ajukan pertanyaan lanjutan sebelum coding.