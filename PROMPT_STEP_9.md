# Prompt Implementasi — Langkah 9: Pengujian Verifikasi — VLC `Open Network Stream` terhadap URL RTSP Hasil Generate

Gunakan prompt ini untuk langkah kesembilan implementasi sistem monitoring CCTV, sesuai item **"Pengujian: VLC `Open Network Stream` terhadap URL hasil generate"** pada bagian **9. Roadmap Implementasi** di `PRD.md`.

---

## Instruksi

1. **Baca dulu `PRD.md`**, terutama bagian **4. Generator RTSP**, **5. Halaman / Alur**, **7. Non-Fungsional**, dan **9. Roadmap** (item pengujian via VLC).
2. Jangan mengubah atau menimpa: `PRD.md`, `PROMPT_STEP_1.md` s.d. `PROMPT_STEP_7.md`, dan seluruh hasil Langkah 1–7 (migrasi, model, factory, seeder, auth/RBAC, masterdata, `RtspGenerator`, `StreamManager`, middleware `EnsureSuperadmin`, controller, view, service rekam/alarm/PTZ/export/monitor, test yang sudah ada).
3. **Tujuan langkah ini bukan menulis fitur baru monitoring**, melainkan **menyediakan sarana + prosedur verifikasi** bahwa URL yang dibangkitkan `RtspGenerator` benar dan bisa diputar di **VLC → Media → Open Network Stream** (atau `Ctrl+N`), beserta pengujian PHPUnit atas sarana itu.
4. **Format URL tidak boleh berubah**: `rtsp://{user}:{pass}@{host}:{port}/cam/realmonitor?channel={channel}&subtype={subtype}` — jangan menyentuh `RtspGenerator`.
5. Ikuti konvensi yang sudah ada: PHPUnit (tanpa Pest) di `tests/Feature`, DB sqlite in-memory di `phpunit.xml`, gaya kode Laravel + Pint, layout sidebar `resources/views/layouts/app.blade.php`.

## Konteks (Sudah Ada dari Langkah 1–8)

- `App\Services\RtspGenerator::generateForMode(Dvr, channel, subtype, mode)` menghasilkan URL Dahua on-the-fly; mode `local` memakai `ip_local`+`port_local`, `public` memakai `ip_public`+`port_public` dan mengembalikan `null` bila `ip_public` kosong; `username`/`password` di-`rawurlencode`; `CCTV_MODE`/`CCTV_SUBTYPE` dari `.env`.
- Password DVR terenkripsi (`Crypt`) + `$hidden`; hanya dibaca sisi server via `Dvr::getPlainPassword()`. **NF-1 & NF-2 sudah dikunci**: URL RTSP/password tidak pernah sampai ke HTML/JS/response — verifikasi VLC justru sengaja membutuhkan URL mentah, jadi langkah ini menyediakan jalur eksplisit, terkendali, dan berizin untuk memperolehnya.
- Tidak ada kolom `rtsp_url`; tidak ada halaman/endpoint yang menampilkan URL hasil generate (dashboard/liveview menyembunyikannya demi keamanan).
- `Dvr`: `unit_id`, `nama`, `ip_local`/`port_local`, `ip_public?`/`port_public?`, `username`, password terenkripsi. `Camera`: `dvr_id`, `channel` (1–16), `nama_lokasi`, `kategori?`, `can_ptz`, `is_online`.
- RBAC: `User::allowedUnitCategories()` (superadmin = semua; teknis = kategori grup-nya); halaman admin hanya `role.superadmin`.

## Keputusan yang Harus Dipatuhi

- Langkah ini = **alat bantu pengambilan URL** + **runbook manual VLC** + **test**. Bukan fitur pemutaran baru.
- Default sumber URL untuk pengujian: **console command** `php artisan cctv:urls` yang mencetak URL generate ke stdout. Opsi `--redact` untuk menyamarikan password (demo/screenshot). Perlu persetujuan bila juga diminta **halaman admin** (Lihat Konfirmasi Sebelum Mulai).
- Sarana ini **tidak boleh menulis URL/password ke log, storage, database, atau file apa pun**; hanya ke stdout (command) atau halaman yang disetujui dengan RBAC ketat.
- RBAC tetap berlaku: halaman (bila dibuat) hanya untuk yang disetujui; teknis tidak melihat URL kamera di luar kategori grup-nya.
- Jangan mengubah `RtspGenerator`, `StreamManager`, `CctvTestController`, route `liveview/*`, dan semua test lama — harus tetap hijau.

## Tugas

### T1 — (bila ada ketidakjelasan) Konfirmasi pendekatan sebelum coding

Tanya dahulu sesuai daftar "Konfirmasi Sebelum Mulai" bila ada pilihan yang belum diputuskan. Default yang dipakai bila tidak ada jawaban:
- Command `cctv:urls` saja (tanpa halaman); akses halaman = superadmin.

### T2 — Console command `cctv:urls` (baru)

Buat `app/Console/Commands/CctvUrlsCommand.php` dengan `signature` kira-kira:

```
cctv:urls {--mode=local} {--unit=} {--dvr=} {--camera=} {--raw} {--redact}
```

- Ambil DVR (dengan `cameras`, `unit`); opsi `--unit`/`--dvr`/`--camera` memfilter (nilai id). Tanpa filter → semua.
- Untuk tiap kamera, bangun URL via `RtspGenerator::generateForMode($dvr, $camera->channel, (int) config('cctv.subtype'), $mode)`.
  - `null` (mode public tanpa `ip_public`) → tandai baris `(public: tidak tersedia — ip_public kosong)`, jangan bangun URL.
- Output default (manusia) per baris:
  `[kode-unit] DVR {dvr.nama} → CH {channel} ({camera.nama_lokasi}) [{mode}]: {url}`
- `--raw` → cetak **hanya URL mentah** (satu per baris; situlah yang disalin ke VLC).
- `--redact` → ganti password di URL dengan `***` (mis. `rtsp://admin:***@host:port/...`). Berlaku untuk kedua mode output. `--redact` adalah default yang disarankan untuk output non-raw.
- **Tidak ada tulisan ke log/storage**; gunakan `$this->line()`/`$this->info()`.
- Daftarkan (bila ragu, gunakan `php artisan make:command`). Tidak perlu schedule.

### T3 — (Opsional, hanya bila disetujui) Halaman admin daftar URL untuk VLC

- Route `GET /admin/rtsp-urls` (name `admin.rtsp-urls.index`) di dalam grup `role.superadmin` → `200`; teknis/guest → `403`/redirect, yang dites.
- Halaman tabel per DVR → kamera, kolom: unit, DVR, CH, nama lokasi, URL **mode local**, URL **mode public** (tanda `—` bila kosong/`null`), tombol **Salin** (clipboard) + hint singkat "Tempel di VLC → Media → Open Network Stream".
- Tampilkan label tegas di atas tabel: *"URL ini mengandung kredensial — hanya untuk pengujian di VLC, jangan dibagikan."*
- Jangan pernah menyimpan/menulis URL ke log; jangan render password terpisah (terkandung di URL saja).

### T4 — Runbook verifikasi manual VLC (dokumentasikan di respons akhir/README Testing bila disetujui)

Prosedur verifikasi nyata (dijalankan manual terpisah, bukan otomatis):

1. Pastikan FFmpeg + lingkungan siap; `php artisan migrate:fresh --seed` sudah memberi data contoh, atau isi DVR/kamera nyata (mis. `ip_local`, `port_local=554/38003`, `username`, `password` via CRUD admin).
2. Jalankan `php artisan cctv:urls --mode=local` → ambil satu URL.
3. Buka **VLC → Media → Open Network Stream (Ctrl+N)** → tempel URL → **Play**.
   - Berhasil: video tampil (stream utama `subtype=0`).
   - Gagal: cek konektivitas `host:port`, kredensial, apakah kamera menyala.
4. Ulangi untuk tiap channel dan (bila `ip_public` terisi) `--mode=public`.
5. Opsional: bandingkan liveview grid (dashboard) — stream yang sama terlihat di browser.
6. Catat hasil per kamera (berhasil/gagal + catatan) — ini bukti verifikasi langkah ini.

### T5 — Pengujian PHPUnit (file baru `tests/Feature/CctvUrlsCommandTest.php`, dan `RtspUrlsPageTest.php` bila halaman dibuat)

- **Command** (`RefreshDatabase`, dataset factory):
  - menjalankan `cctv:urls` tanpa argumen → exit 0, output memuat setiap kamera dengan URL persis format Dahua dan channel/subtype benar (mode dari config).
  - `--mode=public` dengan DVR `ip_public` terisi → URL memakai `ip_public`/`port_public`; DVR `ip_public` kosong → ditandai tidak tersedia, tidak ada URL dibangkitkan untuk kamera itu.
  - `--redact` → output tidak mengandung password plaintext (muncul `***`); tanpa `--redact` langsung boleh mengandung kredensial (itu tujuannya) — uji perbedaan keduanya.
  - `--raw` → hanya URL, satu per baris.
  - Filter `--dvr`/`--camera` → hanya entri yang diminta.
  - Setelah perintah dijalankan: **tidak ada** URL/password muncul di `storage/logs/*` maupun file lain (baca log laravel/lógica; pastikan tidak ada logger dipanggil).
  - **Tanpa FFmpeg/DVR nyata** — command hanya mencetak URL, tidak boleh mencoba connect. Stub tidak diperlukan.
- **Halaman admin** (bila disetujui):
  - guest → redirect `login`; teknis → `403`; superadmin → `200`.
  - Halaman memuat URL yang benar; tidak memuat password sebagai atribut terpisah; hanya data DVR/camera yang ada.
- Semua test lama tetap hijau.

## Konfirmasi Sebelum Mulai (wajib — tanyakan bila tidak jelas)

1. **Sarana pengambilan URL**: cukup **command `cctv:urls`** (default), atau sekalian **halaman admin `/admin/rtsp-urls`** dengan tombol salin? (Halaman sengaja bertabrakan dengan NF-1 — jadi harus diatur izinnya; default: bukan default.)
2. **Siapa yang boleh melihat URL hasil generate**: superadmin saja (default), atau teknis boleh untuk kategori grup-nya? (Sifatnya mengekspos kredensial — condong seminimal mungkin.)
3. **Data uji**: pakai DVR/kamera nyata yang tersedia untuk runbook, atau cukup data dummy factory/seeder? (Tidak perlu tanya bila jelas — default: dummy untuk otomasi, nyata hanya untuk bagian manual.)
4. **Dokumentasi runbook**: tulis di bagian "Pengujian" di `README.md` (bila ada bagian itu), atau cukup di respons akhir prompt ini? (Tetap: jangan membuat file dokumen baru tanpa persetujuan.)

## Verifikasi Wajib di Akhir (seluruh langkah)

Jalankan dan pastikan sukses, lalu laporkan:

- `php artisan test` (seluruh suite lama + baru hijau)
- `php artisan migrate:fresh --seed`
- `vendor/bin/pint`
- `php artisan cctv:urls --mode=local --redact` (command terpasang & output aman)
- `php artisan route:list` (bila ada route baru `admin.rtsp-urls`)
- *(Opsional, manual terpisah)* verifikasi VLC `Open Network Stream` terhadap URL nyata sesuai runbook; laporkan per kamera.

## Batasan Lain

- **Tanpa dependensi tambahan**; tanpa migrasi/perubahan skema.
- Jangan mengubah format URL `RtspGenerator` dan jangan menambah kolom `rtsp_url`.
- Jangan menulis URL/password ke log, storage, database, atau file lain (selain output command / halaman yang disetujui).
- Jangan mengubah perilaku `CctvTestController`, `StreamManager`, route `liveview/*`, dan seluruh test lama — harus tetap hijau.
- Jangan menambahkan komentar yang tidak perlu.
- Jangan memulai server untuk verifikasi otomatis — cukup testing; verifikasi VLC dilakukan manual terpisah.
- Jika ada ketidakjelasan pilihan pada "Konfirmasi Sebelum Mulai", **tanyakan dulu sebelum melanjutkan** — jangan asumsi.