# Prompt Implementasi — Langkah 4: Generator RTSP (Dahhua, on-the-fly — tidak disimpan di DB)

Gunakan prompt ini untuk langkah keempat implementasi sistem monitoring CCTV, sesuai bagian **4. Generator RTSP** pada `PRD.md`.

---

## Instruksi

1. **Baca dulu `PRD.md`**, terutama bagian **4. Generator RTSP**, **6. Skema Database**, dan **7. Non-Fungsional**.
2. Jangan mengubah atau menimpa: `PRD.md`, `PROMPT_STEP_1.md`, `PROMPT_STEP_2.md`, `PROMPT_STEP_3.md`, dan seluruh hasil Langkah 1–3 (migrasi, model, factory, seeder, auth/RBAC, masterdata Unit/DVR/Camera, controller, view, test yang sudah ada).
3. **Jangan** mengubah `CctvTestController` / `cctv-test` dan **belum** membuat halaman liveview grid — itu langkah berikutnya.
4. **Tidak ada perubahan skema database.** Tidak boleh ada kolom `rtsp_url` di `dvrs`/`cameras`, tidak boleh ada migrasi baru pada langkah ini.
5. Ikuti konvensi yang sudah ada: PHPUnit (tanpa Pest), DB sqlite in-memory di `phpunit.xml`, gaya kode Laravel + Pint.

## Konteks (Sudah Ada)

- Model `Dvr`: `unit_id`, `nama`, `ip_local`, `port_local`, `ip_public?`, `port_public?`, `username`, `password` (tersimpan **terenkripsi**, dibaca via `getPlainPassword()`), relasi `cameras`.
- Model `Camera`: `dvr_id`, `channel` (1–16), `nama_lokasi`, `kategori?`.
- Factory: `DvrFactory`, `CameraFactory`, `UnitFactory`.
- Konfigurasi lewat `.env` sudah dipakai (contoh: `FFMPEG_PATH`).

## Keputusan yang Harus Dipatuhi

- **Format URL (Dahua), persis:**

```
rtsp://{username}:{password}@{host}:{port}/cam/realmonitor?channel={channel}&subtype={subtype}
```

- **Mode Local**: `host` = `ip_local`, `port` = `port_local`.
- **Mode Public**: `host` = `ip_public`, `port` = `port_public`; **tidak dibangkitkan** (return `null`/error) bila `ip_public` kosong.
- **Mode dipilih global** via `.env` (default `local`): key `CCTV_MODE=local|public`.
- **`subtype` global** via `.env` (default `0`, stream utama; pakai `1` untuk substream hemat bandwidth pada grid): key `CCTV_SUBTYPE=0|1`. Method generator tetap menerima parameter `subtype` agar bisa dioverride per pemanggilan.
- `username`/`password` di-**percent-encode** (`rawurlencode`) saat disusun ke dalam URL (aman untuk karakter spesial).
- URL **digenerate on-the-fly**, tidak pernah disimpan di database, tidak pernah ditampilkan apa adanya di halaman/response (password di dalamnya sensitif).
- Generator diletakkan di **service** (`App\Services\RtspGenerator`), bukan dibenamkan di view/controller.

## Tugas

### 1. Konfigurasi

- Buat `config/cctv.php` berisi:
  - `mode` → `env('CCTV_MODE', 'local')`
  - `subtype` → `env('CCTV_SUBTYPE', 0)`
- Tambahkan **kedua key ke `.env` dan `.env.example`** dengan nilai default (`CCTV_MODE=local`, `CCTV_SUBTYPE=0`) dan komentar/deskripsi singkat.

### 2. Service

- Buat `App\Services\RtspGenerator` (kelas biasa, di-resolve Laravel tanpa registrasi khusus).
- Method utama, contoh:
  `RtspGenerator::generate(Dvr $dvr, int $channel, int $subtype) : string` dan/atau `::generateForMode(Dvr $dvr, int $channel, int $subtype, string $mode): ?string`.
- Perilaku:
  - Mode `local`: gunakan `ip_local` + `port_local`.
  - Mode `public`: gunakan `ip_public` + `port_public`; bila `ip_public` kosong → return `null` (atau lemparkan error yang jelas — pilih satu, konsisten).
  - Password diambil dari `$dvr->getPlainPassword()`.
  - Output format persis seperti "Keputusan" di atas, `username`/`password` lewat `rawurlencode`.
- Jadikan mode default dari `config('cctv.mode')`; `subtype` default dari `config('cctv.subtype')` bila tidak dioper.

### 3. Pengujian (PHPUnit — file baru `tests/Feature/RtspGeneratorTest.php`)

Gunakan `RefreshDatabase` + `DvrFactory`. Minimal:

- Mode local → URL persis `rtsp://{username}:{password}@{ip_local}:{port_local}/cam/realmonitor?channel={channel}&subtype={subtype}`.
- Mode public & `ip_public` terisi → URL persis memakai `ip_public:port_public`.
- Mode public & `ip_public` kosong → return `null` (generator menolak).
- `channel` dan `subtype` tersisip benar di query string; override `subtype` per pemanggilan bekerja.
- Username/password berisi karakter spesial (mis. `u@ser`, `p:ass&word`) → ter-encode dengan benar di URL.
- Tidak ada kolom/atribut `rtsp_url` pada `Dvr` dan `Camera` (pastikan `toArray()`/JSON model tidak memuat field tersebut).

### 4. Larangan

- **Tanpa perubahan UI**: jangan tambah menu, tombol, route, atau halaman pemutar pada langkah ini.
- **Tanpa migrasi**: jangan ubah migrasi yang ada, jangan tambah kolom `rtsp_url`.
- Acuan `getPlainPassword()` hanya dipakai di dalam service, tidak boleh lolos ke view/response.

## Verifikasi Wajib di Akhir

Jalankan dan pastikan sukses, lalu laporkan:

- `php artisan test` (seluruh suite hijau — termasuk test Langkah 1–3 yang lama)
- `php artisan migrate:fresh --seed` (berhasil, skema tidak berubah)
- `vendor/bin/pint` untuk kerapian kode
- **Manual (verifikasi URL hasil generate dengan VLC)**:
  1. Dapatkan URL via tinker, contoh:
     `php artisan tinker --execute="echo (new App\Services\RtspGenerator)->generate(App\Models\Dvr::find(1), 1);"`
  2. Buka **VLC → Media → Open Network Stream**, tempel URL, klik **Play** → video kamera tampil.
  3. Ulangi untuk mode Public bila `ip_public` terisi.

## Batasan Lain

- Jangan menambah dependensi baru.
- Jangan menambahkan komentar yang tidak perlu.
- Jangan memulai server — cukup testing.
- Jika ada ketidakjelasan (misal pilihan return `null` vs exception, penempatan service), tanyakan dulu sebelum melanjutkan.