# Prompt Implementasi — Langkah 5: Liveview Multi-Kamera (Unit → DVR → Kamera, Grid 1/4/8/16, Local/Public, FFmpeg per-Kamera)

Gunakan prompt ini untuk langkah kelima implementasi sistem monitoring CCTV, sesuai bagian **5. Halaman / Alur** (khususnya **5.4 Liveview**) pada `PRD.md`.

---

## Instruksi

1. **Baca dulu `PRD.md`**, terutama bagian **5. Halaman / Alur**, **4. Generator RTSP**, **7. Non-Fungsional**, dan **9. Roadmap** (item liveview grid + integrasi FFmpeg/HLS multi-stream).
2. Jangan mengubah atau menimpa: `PRD.md`, `PROMPT_STEP_1.md` s.d. `PROMPT_STEP_4.md`, dan seluruh hasil Langkah 1–4 (migrasi, model, factory, seeder, auth/RBAC, masterdata Unit/DVR/Camera, `RtspGenerator`, middleware `EnsureSuperadmin`, layout sidebar, controller, view, test yang sudah ada).
3. **Jangan mengubah perilaku `CctvTestController` / halaman `cctv-test`** — halaman tes ini tetap dipakai. Logika FFmpeg boleh diekstrak ke service baru hanya jika benar-benar rapi dan **perilaku halaman tes tidak berubah serta seluruh test lama tetap hijau**.
4. **Tidak ada perubahan skema database / migrasi baru** pada langkah ini. Tidak boleh ada kolom `rtsp_url`.
5. Ikuti konvensi yang sudah ada: PHPUnit (tanpa Pest) di `tests/Feature`, DB sqlite in-memory di `phpunit.xml`, gaya kode Laravel + Pint, layout sidebar `resources/views/layouts/app.blade.php`, hls.js (sama seperti halaman `cctv-test` yang memakai CDN — pilih CDN atau npm tapi konsisten dan tidak perlu build rumit).

## Konteks (Sudah Ada)

- **Auth + RBAC**: login/logout, middleware `role.superadmin`, helper `User::allowedUnitCategories()` (superadmin = semua kategori `kebun/pks/ro`; teknis = kategori grup-nya). Halaman non-auth sudah diproteksi.
- **Masterdata**: CRUD `Unit`/`Dvr`/`Camera` oleh superadmin. `Dvr` punya `ip_local`/`port_local`, `ip_public`/`port_public` (nullable — kosong berarti public tak tersedia), `username`, `password` terenkripsi + `getPlainPassword()`.
- **`RtspGenerator`** sudah jadi: format Dahua `rtsp://{user}:{pass}@{host}:{port}/cam/realmonitor?channel={c}&subtype={s}`, mode dari `config('cctv.mode')` (`CCTV_MODE`), subtype dari `config('cctv.subtype')` (`CCTV_SUBTYPE`), `generateForMode()` untuk override mode, `rawurlencode` pada username/password, return `null` bila public dan `ip_public` kosong.
- **`CctvTestController`** = acuan mekanisme streaming single-stream: `proc_open` FFmpeg (bentuk array, aman Windows), `-rtsp_transport tcp`, `-use_wallclock_as_timestamps 1`, transcode `libx264 -preset veryfast -tune zerolatency -pix_fmt yuv420p`, `-g 30 -sc_threshold 0`, `-b:v 2500k`, `-f hls -hls_time 2 -hls_list_size 6 -hls_flags temp_file+delete_segments+independent_segments`, playlist di `public/hls/stream.m3u8`, PID di `storage/app/cctv.pid`, log `storage/logs/cctv-ffmpeg.log`, path ditemukan via `resolveFfmpegPath()` (`FFMPEG_PATH`), status berdasar PID hidup + kesegaran playlist (< 10 detik), kill via `taskkill /PID .. /T /F` (Windows) / `kill -9` (non-Windows).
- **Dashboard saat ini** (route `dashboard`) = placeholder: menampilkan kartu unit yang diizinkan user dengan label "Liveview: coming soon".

## Keputusan yang Harus Dipatuhi

- **Liveview adalah tampilan utama** dan diakses **semua user login** (superadmin semua unit; teknis **hanya** unit kategori grup-nya). Superadmin tetap satu-satunya yang bisa manajemen user/grup/masterdata.
- Alur: **pilih Unit → lihat DVR dalam unit → pilih kamera → grid 1/4/8/16**.
- **Filter di sisi server** memakai `allowedUnitCategories()` — jangan hanya sembunyi di UI. User teknis yang mencoba mengakses unit di luar kategorinya → `403` (tidak boleh sekadar dimunculkan).
- **Toggle Local ↔ Public**:
  - Mode default mengikuti `config('cctv.mode')` (`CCTV_MODE`), bisa dioverride per permintaan stream lewat `RtspGenerator::generateForMode`.
  - Saat mode **Public**, kamera yang DVR-nya **tidak memiliki `ip_public` disembunyikan** dari pemilihan (tidak distream, tidak ditampilkan).
- **Per-kamera streaming**: satu proses FFmpeg per kamera, tiap stream punya **`streamKey` unik** + direktori HLS sendiri **`public/hls/{streamKey}/`**. Tidak boleh ada dua proses menulis ke folder yang sama.
- **Batas stream bersamaan configurable**: key **`MAX_CONCURRENT_STREAMS`** di `.env` (default misal `8`). Saat penuh, permintaan start baru **ditolak dengan pesan jelas** (jangan meng-overwrite stream yang berjalan).
- URL RTSP **hanya dibangkitkan on-the-fly di server** (`RtspGenerator`), **tidak pernah** sampai ke HTML/JS/DOM, tidak disimpan di DB. Password DVR tidak boleh bocor ke response mana pun.
- `streamKey` harus **URL-safe & aman untuk path** (mis. hanya `a-z0-9-_`), contoh `cam-{cameraId}` atau `dvr-{dvrId}-ch-{channel}` — konsistenkan.

## Tugas

### 1. Konfigurasi

- `config/cctv.php`: tambah `max_concurrent_streams` → `env('MAX_CONCURRENT_STREAMS', 8)`.
- Tambah **`MAX_CONCURRENT_STREAMS=8`** ke `.env` dan `.env.example` (dengan komentar singkat).
- hls.js: gunakan pendekatan yang konsisten dengan halaman `cctv-test` (CDN). Boleh via npm/Vite bila lebih rapi, asalkan halaman liveview tetap bisa di-build tanpa menyulitkan.

### 2. Service streaming per-kamera (baru)

Buat **`App\Services\StreamManager`** (nama boleh disesuaikan) yang memuat inti mekanisme dari `CctvTestController` untuk **banyak stream**. Method utama:

- `start(Camera $camera, string $mode): array{ok: bool, streamKey?: string, error?: string}`
  - Hitung proses aktif (`runningCount()` dari daftar file PID); bila ≥ `max_concurrent_streams` → tolak (`ok=false`, pesan "Batas stream tercapai").
  - Bangun RTSP via `RtspGenerator::generateForMode($dvr, $channel, config('cctv.subtype'), $mode)`; bila `null` (public tanpa `ip_public`) → tolak.
  - `streamKey = "cam-{$camera->id}"` (atau varian aman lain, konsisten). Validasi dulu `preg_match('#^[a-z0-9\-_]+$#', $streamKey)`.
  - Pastikan folder `public/hls/{$streamKey}` bersih (hapus sisa lama bila ada), jalankan FFmpeg dengan argumen setara `CctvTestController` (RTSP sebagai `-i`, output `-f hls` ke `index.m3u8` + `segment_%03d.ts` di folder itu, log per-stream di `storage/logs/cctv-{streamKey}.log`).
  - Simpan PID ke `storage/app/hls/{$streamKey}.pid`; return `streamKey`.
  - Bila FFmpeg tidak ditemukan (`FFMPEG_PATH`) → `ok=false` + pesan jelas (pola `CctvTestController`).
- `status(string $streamKey): string` → `connected` / `starting` / `failed` (PID hidup + kesegaran playlist < 10 detik, pola sama; sertakan pesan error dari log bila `failed`).
- `stop(string $streamKey): void` → bunuh **hanya proses stream itu** (jangan `taskkill /IM ffmpeg.exe` global seperti `CctvTestController` — tahu PID dari file pid), hapus file PID dan folder HLS-nya.
- `runningCount(): int` → hitung file PID yang prosesnya masih hidup; bersihkan file pid basi.
- *(Boleh)* `cleanupStale()` untuk membersihkan folder HLS tua di `public/hls/` yang prosesnya sudah mati.

Catatan: boleh mengekstrak helper FFmpeg bersama dengan `CctvTestController` bila rapi, tetapi **perilaku `cctv-test` harus identik** dan seluruh test lama tetap hijau. Prioritas: jangan sentuh `CctvTestController` bila ragu.

### 3. Halaman Liveview (jadikan dashboard sebagai tampilan utama)

**Rekomendasi**: ubah `DashboardController`/`resources/views/dashboard/index.blade.php` menjadi halaman liveview — route `dashboard` tetap dipakai (nama route, proteksi auth, dan URL tidak berubah agar sidebar & test lama tetap jalan). Tambahkan route AJAX liveview baru di bawahnya.

- `index()` (`GET /dashboard`, auth):
  - Ambil unit diizinkan via `allowedUnitCategories()` (sama seperti sekarang).
  - Bila ada `?unit={id}` → tampilkan DVR + kamera milik unit itu (eager load `dvrs.cameras`), hanya bila unit diizinkan user (teknis di luar kategori → `403`/abort).
  - Kirim ke view: pilihan unit, daftar DVR per unit dengan kamera (`nama_lokasi`, `channel`, `kategori`, dan flag `public_available` = DVR punya `ip_public`).
  - Sediakan juga `JSON` endpoint kamera per unit bila halaman memakai pemilihan kamera via AJAX (flexibel, konsistenkan).

### 4. Routes (AJAX streaming — tambah di group `auth`, tidak di `role.superadmin`)

- `GET /dashboard` — halaman liveview (sudah ada, perilaku diperluas).
- `POST /liveview/start` — body `{ camera_id, mode }` (mode `local`/`public`) → `StreamManager::start`, return JSON `{ok, streamKey?, error?}`.
- `POST /liveview/stop` — body `{ streamKey }` → `StreamManager::stop`, return JSON.
- `GET /liveview/status?streamKey=...` — status JSON.
- **Semua aksi start/stop/status harus memverifikasi kamera/unit yang sedang diproses milik user** (teknis di unit kategori lain → `403`). Cek akses via `allowedUnitCategories()` di tiap endpoint, bukan cuma di halaman.

### 5. Views + JS

- Halaman dashboard/liveview memakai layout `layouts.app`:
  - **Pemilih Unit**: dropdown/select unit diizinkan; memilih → ke `/dashboard?unit={id}` (atau AJAX).
  - Setelah unit dipilih: daftar **DVR** dengan kamera-kameranya (checkbox/select multi), pilihan **grid 1/4/8/16**, dan **toggle Local/Public**.
  - Saat **Public**: kamera yang `public_available == false` disembunyikan/disabled dengan keterangan "Tidak tersedia di mode Public".
  - **Grid kamera** dengan CSS responsif (1=1 kolom, 4=2×2, 8=2×4 atau 4×2, 16=4×4); tiap tile berisi `<video>` + nama lokasi + badge channel/kategori + indikator status.
  - Tombol **Mulai** mengirim tiap kamera terpilih ke `/liveview/start` (mode sesuai toggle), urut bertahap sesuai jumlah grid; tampilkan pesan error bila batas stream tercapai.
  - **JS**: hls.js (pola `cctv-test`): `new Hls({ liveSyncDurationCount: 3, maxLiveSyncPlaybackRate: 1.5 })`, source `/hls/{streamKey}/index.m3u8`; polling `/liveview/status` per stream; **stop semua stream aktif saat `pagehide`/`beforeunload`** (fetch/`sendBeacon` ke `/liveview/stop`) supaya proses FFmpeg tidak menggantung setelah tab ditutup.
  - Mode toggle memengaruhi **start berikutnya**; stream yang sudah berjalan di mode lama boleh dihentikan dulu saat mode diganti (jelaskan di UI).
- Jangan pernah menampilkan URL RTSP atau password di halaman/HTML/JS.

### 6. Pengujian (PHPUnit — file baru `tests/Feature/LiveviewTest.php`)

Gunakan `RefreshDatabase`, `actingAs` superadmin & teknis. Minimal:

- **Akses & filter**: `/dashboard` untuk guest → redirect `/login`; superadmin → `200` dan melihat semua unit; teknis grup `kebun` → `200` hanya berisi unit `kebun`; akses langsung `?unit=` kategori `pks` oleh teknis → `403`.
- **Konten halaman**: response halaman **tidak mengandung `rtsp://`** dan tidak mengandung password DVR plaintext.
- **Start stream**:
  - kamera valid, mode `local` → `{ok: true, streamKey: ...}` (streamKey mengikuti pola).
  - mode `public` dengan DVR tanpa `ip_public` → ditolak (`ok: false`).
  - kamera/unit di luar kategori user teknis → `403` / ditolak.
  - melebihi `MAX_CONCURRENT_STREAMS` → ditolak dengan pesan batas (uji lewat pemanggilan `StreamManager` dengan proses palsu/mocked `proc_open` atau `runningCount()` yang di-stub — **jangan benar-benar menjalankan FFmpeg banyak di test**; pastikan logika batas & pembentukan `streamKey` teruji tanpa membutuhkan binary FFmpeg).
- **Status/stop**: `status` untuk `streamKey` tak dikenal → `failed`; `stop` tidak error untuk streamKey tak dikenal; PID file & folder HLS dibersihkan (bila memungkinkan diuji dengan fake).
- Seluruh test lama tetap hijau.

### 7. Verifikasi wajib di akhir

Jalankan dan pastikan sukses, lalu laporkan:

- `php artisan test`
- `php artisan migrate:fresh --seed` (skema **tidak berubah**)
- `vendor/bin/pint`
- `php artisan route:list` (pastikan route `dashboard` + `liveview/start`/`stop`/`status` ada)
- **Manual opsional** (butuh server + FFmpeg + DVR nyata): login superadmin dan teknis; pilih unit → grid → start beberapa kamera; uji toggle Local/Public; uji sampai batas `MAX_CONCURRENT_STREAMS` → pesan muncul; tutup tab → stream berhenti (tidak ada proses FFmpeg tersisa). Verifikasi URL RTSP mandiri tetap via VLC seperti Langkah 4.

## Batasan Lain

- **Tanpa migrasi dan tanpa perubahan skema database** pada langkah ini.
- Jangan menambah dependensi berat; hls.js via CDN (konsisten dengan `cctv-test`) atau npm — pilih satu dan konsisten.
- Jangan menyimpan RTSP url di DB/config/response.
- Jangan merusak `CctvTestController`/`cctv-test`; seluruh test lama tetap hijau.
- Jangan menambahkan komentar yang tidak perlu.
- Jangan memulai server untuk verifikasi otomatis — cukup testing; verifikasi manual (opsional) dilakukan terpisah.
- Jika ada ketidakjelasan (misal pemilihan nama `streamKey`, perilaku batas stream, apakah dashboard harus di-rename), tanyakan dulu sebelum melanjutkan.