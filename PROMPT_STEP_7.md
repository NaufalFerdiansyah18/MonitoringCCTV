# Prompt Implementasi — Langkah 7: Milestone Lanjutan (Rekam/Playback, Alarm, PTZ, Export Arsip, Monitoring Online/Offline)

Gunakan prompt ini untuk langkah ketujuh implementasi sistem monitoring CCTV — fitur yang pada PRD masih **di luar scope milestone awal** (bagian **8. Di Luar Scope**), yang dikerjakan setelah PRD disetujui.

---

## Instruksi

1. **Baca dulu `PRD.md`**, terutama bagian **8. Di Luar Scope**, **2. Aktor & Role**, **5. Halaman / Alur**, **7. Non-Fungsional**, **9. Roadmap**, dan **10. Poin Perlu Konfirmasi**.
2. Jangan mengubah atau menimpa: `PRD.md`, `PROMPT_STEP_1.md` s.d. `PROMPT_STEP_6.md`, dan seluruh hasil Langkah 1–6 (migrasi, model, factory, seeder, auth/RBAC, masterdata, `RtspGenerator`, `StreamManager`, middleware `EnsureSuperadmin`, controller, view, test yang sudah ada).
3. **Jangan mengubah perilaku `CctvTestController` / halaman `cctv-test`**, `StreamManager`, dan seluruh route `liveview/*` yang sudah ada. Fitur baru ditambahkan di **samping** jalur streaming liveview yang sudah berjalan.
4. Fitur di bagian 8 PRD sifatnya besar; kerjakan **bertahap dan bertest**, jangan menyatukan semuanya dalam satu commit besar tanpa test. Tiap fitur di bawah punya bagian "Pengujian" sendiri.
5. Ikuti konvensi yang sudah ada: PHPUnit (tanpa Pest) di `tests/Feature`, DB sqlite in-memory di `phpunit.xml`, gaya kode Laravel + Pint, layout sidebar `resources/views/layouts/app.blade.php`, hls.js via CDN (pola halaman liveview).

## Konteks (Sudah Ada dari Langkah 1–6)

- **Auth/RBAC**: `role.superadmin` (master data & manajemen) vs `teknis` (liveview dibatasi kategori grup). Filter server via `User::allowedUnitCategories()`. Route `liveview/*` memverifikasi kepemilikan unit/kamera per request.
- **Liveview**: `DashboardController` = tampilan utama; `StreamManager` (`start/status/stop/runningCount`) menjalankan 1 proses FFmpeg per kamera dengan `streamKey = cam-{cameraId}`, HLS di `public/hls/{streamKey}/`, file PID di `storage/app/hls/`, batas `MAX_CONCURRENT_STREAMS`.
- **`RtspGenerator`**: format Dahua on-the-fly, mode local/public, `null` bila public tanpa `ip_public`. Password DVR hanya lewat `getPlainPassword()` di sisi server.
- **Keamanan**: password DVR terenkripsi (Crypt) + `$hidden`; log FFmpeg disanitasi (`rtsp://***@`) sebelum sampai ke client (`StreamManager::redactCredentials`).
- Stack: PHP + FFmpeg (path dari `FFMPEG_PATH`), PHPUnit sqlite in-memory, tanpa dependensi tambahan.

## Keputusan yang Harus Dipatuhi

- **Posisi fitur**: Rekam, PTZ, dan Monitoring terhubung ke **kamera/DVR nyata** — dilarang menulis kode yang berasumsi kolom `rtsp_url` disimpan (URL tetap digenerate on-the-fly via `RtspGenerator`).
- **Persetujuan di dalam file ini**: skema/pendekatan yang dijelaskan di bawah dipakai sebagai **default**; bila menemukan pilihan yang lebih rapi, tanyakan dulu (lihat Batasan Lain).
- **RBAC tetap berlaku** untuk semua fitur baru:
  - master data & manajemen (termasuk halaman admin alarm/arsip, adalah pembacaan data) tetap `role.superadmin`.
  - aksi kamera (rekam mulai/berhenti, PTZ, playback) mengikuti aturan liveview: **teknis hanya untuk unit di kategori grup-nya**, diverifikasi di sisi server, bukan sekadar UI.
- **Keamanan**: tidak pernah bocorkan password DVR / URL RTSP ke HTML/JS/response; endpoint yang membawa payload RTSP tidak boleh menampilkannya; log disanitasi.
- **Tanpa dependensi baru**; tanpa mengubah perilaku fitur yang sudah ada; test lama harus tetap hijau.
- **Sumber kebenaran skema** tetap milik Langkah 1 (`Unit::KATEGORI`, konstanta, dst.) — migrasi baru boleh ditambah, yang lama tidak diubah.

## Pemetaan Fitur (bagian 8 PRD → tugas akhir langkah ini)

| Fitur PRD | Hasil Akhir yang Diharapkan |
|---|---|
| Rekam/playback | Rekaman per kamera pada permintaan, tersimpan sebagai HLS, bisa diputar ulang di browser. |
| Alarm/notifikasi | Model alarm + halaman list (filter kategori) + catatan waktu kejadian; dasar notifikasi in-app. |
| PTZ | Kontrol arah kamera untuk kamera yang mendukung PTZ (`can_ptz`), format CGI Dahua. |
| Export arsip | Unduh rekaman tertentu sebagai satu file MP4 (remux via FFmpeg). |
| Monitoring online/offline | Status online/offline DVR/kamera diperbarui terjadwal, tampil sebagai badge + `last_seen`. |

---

## Tugas A — Skema & Model pendukung (migrasi baru)

**Catatan**: lima sub-tugas ini boleh dikerjakan terpisah; migrasi boleh digabung bila rapi, selama urutan nomor file valid untuk FK.

### 1. Rekam — `create_recordings_table`
- `id` PK
- `camera_id` FK → `cameras` `cascadeOnDelete`, index
- `started_at` timestamp **nullable**
- `ended_at` timestamp **nullable**
- `duration_seconds` `unsignedInteger` nullable
- `size_bytes` `unsignedBigInteger` nullable
- `format` string default `'hls'`
- `status` enum(`recording`, `stopped`, `failed`) default `recording`
- `timestamps`
- Model `Recording`: `belongsTo(Camera::class)`; casts (`started_at`/`ended_at` → datetime, `duration_seconds` → integer, `size_bytes` → integer); fillable sesuai kolom; `$hidden` tidak perlu khusus.

### 2. PTZ — `add_can_ptz_to_cameras_table`
- Tambah `can_ptz` boolean default `false` ke `cameras` (migrasi baru, jangan ubah migrasi `cameras` lama).
- Model `Camera`: tambah `can_ptz` ke fillable + cast boolean.

### 3. Alarm — `create_alarms_table`
- `id` PK
- `camera_id` FK → `cameras` `cascadeOnDelete`, index
- `type` string nullable (contoh: `motion`, `video_loss`, `offline`, `manual`)
- `message` string nullable
- `started_at` timestamp nullable
- `ended_at` timestamp nullable
- `seen_at` timestamp nullable (tanda sudah dilihat/di-ack)
- `timestamps`
- Model `Alarm`: `belongsTo(Camera::class)`; cast tanggal; fillable sesuai kolom.

### 4. Monitoring — `add_monitoring_columns_to_cameras_table`
- Tambah ke `cameras`: `is_online` boolean default `false`, `last_checked_at` timestamp nullable (dan opsional `latency_ms` `unsignedInteger` nullable).
- Model `Camera`: fillable + cast boolean/tanggal.

### 5. (Opsional, hanya bila dipakai) relasi lanjutan
- `Camera::recordings()`, `Camera::alarms()`; `Dvr` tetap via `cameras` (rekaman di level kamera).

---

## Tugas B — Rekam & Playback (bagian "Rekam/playback")

### Service `App\Services\RecordingManager` (baru; boleh meniru pola `StreamManager`)
- `start(Camera $camera, string $mode = 'local'): array{ok: bool, recordingId?: int, streamKey?: string, error?: string}`
  - Buat baris `Recording` (`status=recording`, `started_at=now`).
  - Bangun URL via `RtspGenerator::generateForMode(...)`; `null` saat public tanpa `ip_public` → tandai `status=failed`.
  - `streamKey = "rec-{$recording->id}"` (validasi pola `^[a-z0-9\-_]+$`), folder HLS `public/hls/{streamKey}/`.
  - Jalankan FFmpeg persis pola `StreamManager` (RTSP input, `libx264`/`yuv420p`, HLS) **tetapi tanpa `delete_segments`** agar semua segment tersimpan (gunakan `-hls_list_size 0`, `-hls_flags temp_file+independent_segments`), output `index.m3u8` + `segment_%03d.ts`; PID di `storage/app/hls/{streamKey}.pid`, log `storage/logs/cctv-{streamKey}.log`.
  - Saat start, isi `streamKey` pada baris recording (tambah kolom `stream_key` nullable di migrasi A1 bila perlu).
- `stop(int $recordingId): void` — bunuh PID recording itu (jangan global), hapus PID, set `ended_at`, hitung `duration_seconds` & `size_bytes` dari folder HLS, `status=stopped`.
- `status(int $recordingId): string` — pola `StreamManager::status` (PID hidup + playlist segar).
- `isRecording(int $recordingId): bool`.
- `recordingsFor(Camera $camera)` — query rekaman kamera terbaru dulu.
- Perhatikan: ini **independen** dari `StreamManager` (jalur liveview). Tidak boleh mematikan proses liveview dan sebaliknya; cek `streamKey` tidak saling tumpang tindih (`cam-*` vs `rec-*`).

### Halaman & route
- Route (group `auth`, verifikasi kepemilikan kamera) atau `role.superadmin` untuk form admin — pilih yang sesuai:
  - `POST /recordings/start` — body `{camera_id, mode}` → `RecordingManager::start` → JSON `{ok, recordingId?, streamKey?, error?}`.
  - `POST /recordings/stop` — body `{recording_id}` → stop → JSON.
  - `GET /recordings/status?recording_id=...` → JSON status.
  - `GET /recordings` → halaman list rekaman (superadmin semua; teknis filter kategori). Filter kategori server-side via `allowedUnitCategories()`.
  - `GET /recordings/{recording}/playlist` → serve `index.m3u8` folder HLS recording itu (verifikasi akses).
- UI: tombol **Rekam** per kamera di halaman liveview (atau halaman rekaman), indikator sedang merekam, list rekaman dengan tombol **Putar** dan **Export** (Tugas D), dan player memakai `hls.js` (pola liveview) pada route playlist.
- Pastikan halaman list rekaman menampilkan waktu mulai/selesai, durasi, ukuran, dan status.

### Pengujian (file baru `tests/Feature/RecordingTest.php`)
- Record row dibuat dengan status/`streamed` saat start dipanggil (pakai stub/mock — **jangan jalankan FFmpeg nyata**; test pembentukan `streamKey`, baris DB, dan pesan error saat FFmpeg tidak ada, pola `LiveviewTest`).
- public tanpa `ip_public` → start ditolak (`ok:false`).
- teknis hanya bisa rekam/lihat rekaman kamera kategori grup-nya; di luar kategori → `403` (start, list, playlist).
- guest → redirect login; superadmin → `200`.
- `stop` update `ended_at`/`status`; `status` recording tak dikenal → `failed`; stop idempoten.
- response halaman tidak mengandung `rtsp://` / password DVR.

---

## Tugas C — Alarm & Notifikasi (bagian "alarm/notifikasi")

- Model `Alarm` + halaman admin:
  - Route `admin.alarms.index` (di bawah `role.superadmin`), list alarm (digabung dengan data kamera → unit → kategori), filter kategori unit, filter belum/sudah `seen`.
  - Aksi `POST admin.alarms.seen/{alarm}` → tandai `seen_at=now`.
- Sumber alarm (minimal, default):
  - **Manual** dari halaman detail kamera (tombol "Buat Alarm" — untuk demonstrasi & backup).
  - Dari **monitoring offline** (Tugas E): saat kamera terdeteksi offline, tulis alarm `type=offline`; saat kembali online lalu `ended_at`.
  - *(Opsional, butuh konfirmasi)* integrasi motion/video loss Dahua via HTTP API.
- **Notifikasi**: cukup **in-app** (badge jumlah alarm belum dilihat di sidebar; superadmin melihat semua, teknis hanya alarm kamera kategori grup-nya). *(Opsional: email via Laravel Mail bawaan, tanpa package baru — via konfirmasi.)*
- Mixed RBAC: membuat alarm manual = aksi kamera → teknis boleh untuk kategori grup-nya; menandai seen / lihat semua = superadmin.

### Pengujian (`tests/Feature/AlarmTest.php`)
- Model & relasi `Alarm` → `Camera` berfungsi.
- Guest → redirect login; teknis lihat alarm kamera di luar kategorinya → `403`/tidak muncul.
- Teknis hanya menerima alarm kategori grup-nya; superadmin semua.
- `seen` diupdate; alarm manual dibuat dengan `type=manual`.
- Tanpa bocor password/URL.

---

## Tugas D — PTZ & Export Arsip (bagian "PTZ", "export arsip")

### 4a. PTZ (Dahua CGI)
- Service `App\Services\PtzService`:
  - Kode arah: `Up`, `Down`, `Left`, `Right`, `LeftUp`, `RightUp`, `LeftDown`, `RightDown`, `ZoomIn`, `ZoomOut`, `Stop` (dahsua: `cgi-bin/ptz_control.cgi?action=start&code=...&channel={channel}`; berhenti = `action=stop&code=Stop&channel={channel}`).
  - Host/port mengikuti mode (local/public via `RtspGenerator`-style) — pilih host dari `ip_local`/`ip_public` (public hanya bila `ip_public` terisi). Pakai `username` + `getPlainPassword()`.
  - Gunakan HTTP client Laravel (`Http::timeout(...)->withBasicAuth(...)`) atau `curl` — tanpa package baru.
- Route (di dalam jalur akses kamera seperti liveview; `can_ptz=true` wajib):
  - `POST /ptz/{camera}` — body `{action: 'start'|'stop', code: 'Up'|...}` → JSON; verifikasi kamera diizinkan user + `can_ptz`.
- UI: tampilkan kontrol PTZ pada tile kamera **hanya bila `can_ptz`** (center + zoom + tombol stop); tombol atas/bawah/kiri/kanan/diagonal.
- Perilaku saat `can_ptz=false`: endpoint ditolak (`422`/`403`), UI tidak menampilkan kontrol.

### 4b. Export Arsip
- Route `GET /recordings/{recording}/export` (verifikasi akses: superadmin semua, teknis kategori grup-nya).
- Proses: `mkdir temp`, `ffmpeg -i {hlsFolder}/index.m3u8 -c copy -bsf:a aac_adtstoasc out.mp4` (remux, bukan re-encode — cepat), kirim file via `streamDownload`, lalu hapus temp. Bila FFmpeg tak ada/pipeline gagal → response error jelas.
- Pastikan file sementara di temp, bukan di `public/`, dan dibersihkan setelah dikirim (gunakan `Response` dengan `DeleteFileAfterSend=true` bila memungkinkan).

### Pengujian (`tests/Feature/PtzExportTest.php`)
- PTZ: teknis kamera di luar kategori → `403`; kamera `can_ptz=false` → ditolak; request valid membentuk URL CGi yang benar (mock HTTP/no network, pola stub) namun tidak pernah memuat URL RTSP ke response.
- Export: guest redirect; teknis di luar kategori → `403`; FFmpeg absen → error jelas; sukses mengembalikan file (boleh dengan proses FFmpeg di-stub, mis. memeriksa bahwa jalur remux dipanggil).

---

## Tugas E — Monitoring Online/Offline Real-time (bagian "monitoring online/offline")

- Console command `php artisan cctv:monitor` (file `app/Console/Commands/`; daftarkan di `routes/console.php` dengan `Schedule::command('cctv:monitor')->everyMinute()` — **schedule perlu dijalankan**; untuk pengujian, command bisa dipanggil manual).
- Alur tiap kamera:
  - Tes konektivitas TCP ke `ip_local:port_local` (`fsockopen` dengan timeout singkat ≤ 2s). Bila DVR punya `ip_public`, cek juga `ip_public:port_public`.
  - Update `cameras.is_online`, `last_checked_at`, `latency_ms`; tulis `Alarm(type=offline)` saat berubah offline → online (bila belum ada alarm offline terbuka untuk kamera itu), dan `ended_at` saat kembali online.
- *(Opsional)* cek via RTSP `DESCRIBE` (menggunakan FFmpeg `-i` dengan timeout) — lebih akurat tapi mahal; default pakai konektivitas TCP port.
- UI: badge **Online/Offline** + `last_seen` di halaman liveview (daftar DVR/kamera) dan daftar admin; polling status tetap memakai jalur `liveview/status` yang ada.

### Pengujian (`tests/Feature/MonitorTest.php`)
- Command berjalan tanpa error dengan dataset kecil (mock `fsockopen`/cek koneksi agar tidak bergantung jaringan — injeksi fungsi atau strip via refactor kecil).
- Kamera yang "tak terjangkau" → `is_online=false` + `last_checked_at` terisi + alarm offline dibuat; kembali "terjangkau" → `is_online=true` + alarm online ditutup.
- Alarm offline tidak dobel dibuat untuk keadaan yang sama.

---

## Konfirmasi Sebelum Mulai (wajib — tanyakan bila tidak jelas)

1. **Rekam**: kontinu/on-demand sesuai permintaan pengguna diluncurkan dari halaman, atau perlu jadwal tetap? Default: **manual per kamera**.
2. **PTZ authorization**: teknis boleh PTZ pada unit kategori grup-nya (dianggap bagian liveview) — setujui? *(default begitu, superadmin bebas)*.
3. **Alarm sumber**: selain manual & offline, perlu integrasi `motion`/`video_loss` via HTTP API Dahua (butuh info DVR nyata)? Default: **manual + offline** dulu.
4. **Export format**: remux `-c copy` ke MP4 (cepat tapi butuh browser/player yang bisa mp4 ts) — setuju? atau re-encode penuh (`libx264`)?
5. **Monitoring**: cek TCP port saja (ringan) vs RTSP `DESCRIBE` (akurat, berat) — default **TCP port**.
6. **Retensi data**: perlu pembersihan otomatis rekaman/alarm tua (mis. `DELETE` via schedule)? Default: belum (data disimpan, admin hapus manual).

---

## Verifikasi Wajib di Akhir (seluruh langkah)

Jalankan dan pastikan sukses, lalu laporkan:

- `php artisan migrate:fresh --seed`
- `php artisan test` (seluruh suite lama + baru hijau)
- `vendor/bin/pint`
- `php artisan route:list` (route baru `recordings/*`, `ptz/*`, `admin.alarms.*` ada)
- *(Opsional)* `php artisan cctv:monitor` berjalan tanpa error dengan data dummy.

## Batasan Lain

- **Tanpa dependensi tambahan** (tidak menambah package/library baru).
- Jangan mengubah perilaku `CctvTestController`, `StreamManager`, route `liveview/*`, dan seluruh test lama — harus tetap hijau.
- Jangan menambahkan kolom `rtsp_url`; URL digenerate on-the-fly.
- Jangan menambahkan komentar yang tidak perlu di kode.
- Jangan memulai server untuk verifikasi otomatis — cukup testing; uji nyata (FFmpeg/DVR) dilakukan manual terpisah.
- Jika ada ketidakjelasan skema/pendekatan/pilihan pada "Konfirmasi Sebelum Mulai", **tanyakan dulu sebelum melanjutkan** — jangan asumsi.