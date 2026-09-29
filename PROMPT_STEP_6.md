# Prompt Implementasi — Langkah 6: Non-Fungsional (Keamanan Password, RBAC, Batas Stream, Mode Public)

Gunakan prompt ini untuk mengerjakan dan mengunci **persyaratan non-fungsional** sistem monitoring CCTV, sesuai bagian **7. Non-Fungsional** pada `PRD.md`.

---

## Instruksi

1. **Baca dulu `PRD.md`**, terutama bagian **3. Master Data**, **4. Generator RTSP**, **5. Halaman / Alur (5.2–5.4)**, **6. Skema Database**, **7. Non-Fungsional**, dan **9. Roadmap**.
2. Jangan mengubah atau menimpa: `PRD.md`, `PROMPT_STEP_1.md` s.d. `PROMPT_STEP_5.md`, dan seluruh hasil Langkah 1–5 (migrasi, model, factory, seeder, auth/RBAC, masterdata, `RtspGenerator`, `StreamManager`, middleware `EnsureSuperadmin`, controller, view, test yang sudah ada).
3. **Jangan mengubah perilaku `CctvTestController` / halaman `cctv-test`** — mekanisme FFmpeg→HLS single-stream milik halaman tes tetap dipakai.
4. Sebagian besar butir NF ini **sudah terpasang** di Langkah 1–5. Tugas utama langkah ini adalah **mengaudit, menyelaraskan, dan mengunci** — bukan membangun ulang. Jangan menduplikasi kode yang sudah benar; cukup perbaiki gap/inkonsistensi, tambahkan yang hilang, dan pastikan seluruh butir NF diverifikasi oleh test.
5. Ikuti konvensi yang sudah ada: PHPUnit (tanpa Pest) di `tests/Feature`, DB sqlite in-memory di `phpunit.xml`, gaya kode Laravel + Pint.

## Konteks (Sudah Ada dari Langkah 1–5)

Empat butir NF dari PRD §7 sudah terpasang dengan detail berikut. **Audit dulu — jangan rombak tanpa alasan.**

### NF-1 — Password DVR terenkripsi (Crypt), tidak pernah dikirim balik ke frontend
- `Dvr` memakai mutator `Attribute::make(set: ...)` dengan `Crypt::encryptString`, atribut `password` masuk `$hidden`, dan `getPlainPassword()` (`Crypt::decryptString`) dipakai **hanya di sisi server** (di `RtspGenerator`).
- Form DVR memakai `type="password"` + placeholder "Biarkan kosong jika tidak diubah"; update kosong = password tetap.
- Sudah dites: `CctvModelsTest::test_dvr_password_is_encrypted_and_never_serialized`, `MasterDataTest` (halaman/response tidak mengandung password plaintext).

### NF-2 — RBAC via middleware; data liveview difilter lewat relasi user→grup→kategori unit
- `User::isSuperadmin()` dan `User::allowedUnitCategories()` (superadmin → semua `Unit::KATEGORI`; teknis → kategori grup-nya, kosong bila tanpa grup).
- Middleware `EnsureSuperadmin` terdaftar dengan alias `role.superadmin` di `bootstrap/app.php`, dipasang di grup `admin.*` dan menolak (`403`) selain superadmin.
- Filter **di sisi server**: `DashboardController` memfilter unit via `allowedUnitCategories()`; `LiveviewController::authorizeUnit()` memutus akses teknis ke unit di luar kategorinya (`403`) — bukan sekadar sembunyi di UI.
- Sudah dites: `AuthRbacTest`, `LiveviewTest`, `CctvModelsTest`.

### NF-3 — Kapasitas stream dibatasi konfigurasi (1 proses FFmpeg per kamera)
- `config/cctv.php` punya `max_concurrent_streams` ← `MAX_CONCURRENT_STREAMS` (`.env`/`.env.example`, default 8).
- `StreamManager`: `streamKey = cam-{cameraId}` (pola `^[a-z0-9\-_]+$`), satu direktori HLS `public/hls/{streamKey}/`, satu file PID per stream, `runningCount()` menghitung proses hidup (file PID basi dibersihkan), start **ditolak** bila sudah ≥ batas, stop hanya membunuh PID stream itu (tidak pernah `taskkill /IM ffmpeg.exe` global).
- Sudah dites: `LiveviewTest` (batas tercapai ditolak, streamKey konsisten, stop membersihkan PID + folder HLS).

### NF-4 — Mode public bergantung kondisi jaringan/NAT/port-forwarding
- `RtspGenerator::generateForMode()` return **`null`** bila mode public dan `ip_public` kosong (hanya `ip_local`/`port_local` untuk mode local; `ip_public`/`port_public` untuk public).
- Saat mode Public, kamera dengan DVR tanpa `ip_public` disembunyikan/tidak tersedia; `StreamManager::start()` menolak dengan pesan jelas.
- `mode` global dari `config('cctv.mode')` (`CCTV_MODE`), `subtype` dari `config('cctv.subtype')` (`CCTV_SUBTYPE`), keduanya juga di `.env`/`.env.example`.
- Sudah dites: `RtspGeneratorTest`, `LiveviewTest`.

## Keputusan yang Harus Dipatuhi

- **NF-1**: Password DVR **selalu** tersimpan terenkripsi (Crypt), atribut `password` **tidak boleh pernah** muncul di serialisasi/JSON/HTML/response mana pun (termasuk lampiran log ke client). `getPlainPassword()` hanya dipakai di service di sisi server.
- **NF-2**: Tidak ada jalur akses ke master data/user/grup selain lewat `role.superadmin`. Tidak ada data unit di luar `allowedUnitCategories()` yang mencapai user teknis — di halaman, endpoint, maupun response JSON.
- **NF-3**: `MAX_CONCURRENT_STREAMS` wajib ada di `.env` dan `.env.example`; batas wajib dijalankan di sisi server (`StreamManager`), tidak pernah sekadar disembunyikan di UI. Satu kamera = satu proses FFmpeg = satu `streamKey` unik.
- **NF-4**: Mode public tidak pernah "memaksa" DVR tanpa `ip_public`; perilaku ditolak/disembunyikan konsisten antara generator, `StreamManager`, dan UI.
- Tanpa perubahan skema: **tidak ada migrasi baru** pada langkah ini.

## Tugas

### 1. Audit & selaraskan per butir NF

Periksa satu per satu terhadap butir di bagian **Konteks** dan **Keputusan** di atas:

- **(NF-1)** Pastikan: mutator enkripsi aktif, `password` di `$hidden`, `getPlainPassword()` benar, `RtspGenerator` mengambil password via `getPlainPassword()` (bukan atribut serialisasi), **tidak ada** view/controller/JSON yang mengirim plaintext. Pastikan akses `Dvr->getPlainPassword()` tidak bocor saat `camera->dvr` ikut diserialisasi.
- **(NF-2)** Pastikan: semua route `admin.*` di dalam group middleware `role.superadmin`; `LiveviewController` (start/stop/status) memverifikasi kepemilikan unit/kamera; `DashboardController` tidak meloloskan unit di luar kategori teknis. Periksa tidak ada endpoint lain yang mengekspos daftar unit/DVR/kamera tanpa filter.
- **(NF-3)** Pastikan: `config/cctv.php` membaca `MAX_CONCURRENT_STREAMS`; `.env` **dan** `.env.example` memuat key itu; `StreamManager` menolak saat `runningCount() >= max_concurrent_streams`; satu `streamKey` tidak pernah dijalankan dua proses (start ulang menghentikan proses lama, bukan menumpuk); stop tidak membunuh proses stream lain.
- **(NF-4)** Pastikan: generator return `null` saat public tanpa `ip_public`; `StreamManager::start` menolak mode itu dengan pesan; dashboard/UI menyembunyikan/disabled kamera public-unavailable saat mode Public; pesan konsisten (tidak membocorkan RTSP URL/password).

Perbarui implementasi bila ditemukan inkonsistensi (mis. nilai default `.env` vs `.env.example` berbeda, filter kurang, kolom terlolos, dsb.). **Jangan** mengubah yang sudah benar hanya untuk gaya.

### 2. Kunci dengan test (tambahkan bila ada celah)

Tinjau cakupan test yang sudah ada untuk keempat butir NF. Tambahkan test di `tests/Feature` bila ada butir yang belum terverifikasi. Minimal pastikan ada bukti untuk:

- **NF-1**: password di DB ≠ plaintext; serialisasi model/JSON tidak memuat `password` maupun plaintext; `getPlainPassword()` mengembalikan nilai asli; response halaman/liveview/masterdata tidak mengandung plaintext DVR password dan tidak mengandung `rtsp://`.
- **NF-2**: guest → redirect login; teknis → `403` di route `admin.*`; teknis hanya menerima unit kategori grup-nya (halaman **dan** endpoint start/stop/status); akses langsung unit di luar kategori → `403`.
- **NF-3**: batas `MAX_CONCURRENT_STREAMS` tercapai → start ditolak dengan pesan jelas (pakai stub/mock — jangan jalankan FFmpeg nyata); streamKey sesuai pola `cam-{id}`; stop menghapus PID + folder HLS; `runningCount()` tidak menghitung file PID basi.
- **NF-4**: public tanpa `ip_public` → generator `null` dan start ditolak; public dengan `ip_public` → URL memakai `ip_public`/`port_public`; mode local memakai `ip_local`/`port_local`.

Boleh menambah file test baru (mis. `tests/Feature/NonFunctionalTest.php`) atau memperluas file yang sudah ada — pilih yang paling rapi dan tidak duplikatif.

### 3. Verifikasi wajib di akhir

Jalankan dan pastikan sukses, lalu laporkan:

- `php artisan test`
- `php artisan migrate:fresh --seed` (skema **tidak berubah**)
- `vendor/bin/pint`
- `php artisan route:list` (pastikan route `dashboard` + `admin.*` terlindungi dan endpoint `liveview/start|stop|status` berada di group `auth`)

**Manual opsional** (butuh server + FFmpeg + DVR nyata, dijalankan terpisah): login superadmin & teknis; cek menu/akses; uji sampai batas `MAX_CONCURRENT_STREAMS` muncul pesan; uji public-unavailable disembunyikan; verifikasi URL RTSP via VLC seperti Langkah 4.

## Batasan Lain

- **Tanpa migrasi dan tanpa perubahan skema database.**
- Jangan menambah dependensi baru.
- Jangan menambahkan komentar yang tidak perlu.
- Jangan memulai server untuk verifikasi otomatis — cukup testing.
- Jika ada ketidakjelasan (misal ditemukan gap besar pada salah satu butir NF, atau perlu keputusan penamaan file test baru), tanyakan dulu sebelum melanjutkan.