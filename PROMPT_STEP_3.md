# Prompt Implementasi — Langkah 3: Master Data (Unit, DVR, Camera)

Gunakan prompt ini untuk langkah ketiga implementasi sistem monitoring CCTV, sesuai bagian **3. Master Data** pada `PRD.md`.

---

## Instruksi

1. **Baca dulu `PRD.md`**, terutama bagian **3. Master Data**, **5.3 Masterdata (khusus superadmin)**, **6. Skema Database**, dan **7. Non-Fungsional**.
2. Jangan mengubah atau menimpa: `PRD.md`, `PROMPT_STEP_1.md`, `PROMPT_STEP_2.md`, dan seluruh hasil Langkah 1 & 2 (migrasi, model, factory, seeder, middleware `EnsureSuperadmin`, auth, layout sidebar, controller User/Grup Teknis, test yang sudah ada).
3. **Jangan** mengubah `CctvTestController`/`cctv-test`, dan **belum** membuat service generate RTSP atau halaman liveview grid — itu langkah berikutnya.
4. Ikuti konvensi yang sudah ada: PHPUnit (tanpa Pest), DB sqlite in-memory di `phpunit.xml`, gaya kode Laravel + Pint, layout sidebar `resources/views/layouts/app.blade.php`.

## Konteks (Sudah Ada)

- Migrasi & model **sudah jadi** dari Langkah 1: `Unit` (`kode`, `nama`, `kategori` enum `kebun`/`pks`/`ro`, relasi `dvrs`), `Dvr` (`unit_id`, `nama`, `ip_local`, `port_local`, `ip_public?`, `port_public?`, `username`, `password` **terenkripsi** + `getPlainPassword()`, relasi `unit`, `cameras`), `Camera` (`dvr_id`, `channel` 1–16 + validasi model, `nama_lokasi`, `kategori?`, unique `(dvr_id, channel)`, relasi `dvr`).
- Factory: `UnitFactory`, `DvrFactory`, `CameraFactory`, `TechnicalGroupFactory`.
- Sudah ada group route `admin` dengan middleware `role.superadmin`, prefix `admin`, prefix name `admin.` (contoh: `admin.users.index`).
- Sidebar di `layouts/app.blade.php` memuat menu Dashboard + User + Grup Teknis.
- Superadmin bisa login via `/login` (seeder: `SUPERADMIN_EMAIL`/`SUPERADMIN_PASSWORD`).

## Keputusan yang Harus Dipatuhi

- **Hanya superadmin** yang mengakses semua halaman masterdata. User `teknis` → `403`.
- **Password DVR**: dienkripsi saat simpan, **tidak pernah** ditampilkan apa adanya di halaman/response. Form edit menampilkan placeholder "biarkan kosong jika tidak diubah"; kosong = password tetap.
- **Kategori Kamera**: teks bebas (bukan masterdata terpisah), opsional.
- **Channel kamera**: angka 1–16, unik per DVR; validasi di form + validasi model yang sudah ada.
- Kategori unit hanya `kebun` / `pks` / `ro`.

## Tugas

### 1. Route (tambahkan ke group auth + `role.superadmin` yang sudah ada di `routes/web.php`)

Rekomendasi struktur (nama akhir bisa disesuaikan, konsisten dengan yang ada):

- `admin/units` → resource `units` (`except: ['show']`) → `admin.units.*`
- `admin/dvrs` → resource `dvrs` (`except: ['show']`) → `admin.dvrs.*`
- Kamera **nested di bawah DVR** agar alurnya Unit → DVR → Camera:
  - `admin/dvrs/{dvr}/cameras` → resource (index/create/store) maupun `admin/cameras/{camera}/edit|update|destroy`
  - Bisa juga `Route::resource('dvrs.cameras', ...)` pola Laravel, atau route eksplisit `deep` — pilih yang paling rapi; pastikan nama route konsisten & diuji.
- Route `admin.dvrs.index` mendukung query filter `?unit={id}` untuk mempersempit daftar DVR per unit.

### 2. Controller (khusus superadmin)

Buat `UnitController`, `DvrController`, `CameraController` mengikuti pola `UserController`/`TechnicalGroupController` (validasi di controller, redirect ke index dengan flash success).

- **Unit**: index (list + jumlah DVR), create, store, edit, update, destroy.
  - Validasi: `kode` required, string, max:255, unique (ignore saat update); `nama` required; `kategori` required, in `kebun,pks,ro`.
  - Delete unit: perilaku FK DVR sudah `cascadeOnDelete` → hapus DVR (dan kamera) ikut terhapus. Beri konfirmasi di UI.

- **DVR**: index (bisa difilter per unit; tampilkan ip/port tapi **bukan password**), create, store, edit, update, destroy.
  - Validasi create: `unit_id` required exists; `nama` required; `ip_local` required (valid IP); `port_local` required integer 1–65535 (default 554); `ip_public` nullable (valid IP bila terisi); `port_public` nullable integer 1–65535; `username` required; `password` **required saat create**.
  - Validasi update: sama, tapi `password` nullable — kosong berarti tidak diubah.
  - Simpan via model `Dvr` (mutator enkripsi sudah ada). Jangan pernah pass password plaintext ke view; di form pakai placeholder saja.
  - Tampilkan daftar kamera milik DVR ini (mis. di halaman index DVR sebagai kolom ringkas, atau tombol "Kamera"). Sediakan tombol ke halaman camera DVR tersebut.

- **Camera**: index (list kamera milik DVR yang sedang dikunjungi), create, store, edit, update, destroy.
  - Validasi: `dvr_id` required exists (harus DVR milik `/admin/dvrs/{dvr}/cameras` yang sedang dibuka); `channel` required integer 1–16, unique per DVR (`Rule::unique('cameras', 'channel')->where('dvr_id', ...)`); `nama_lokasi` required; `kategori` nullable string (bebas).
  - Halaman index Camera menampilkan context DVR (unit + nama DVR) dan daftar channel yang sudah terpakai agar mudah.

### 3. Views (gunakan layout sidebar yang ada)

- Tambah menu sidebar (di bawah label **Manajemen**, untuk superadmin): **Unit**, **DVR**, **Camera**. Halaman Camera ditautkan dari konteks DVR (bukan menu utama wajib, tapi boleh).
- Halaman mengikuti gaya form/table/badge yang sudah ada: `page-head` + tombol "Tambah", form di dalam `.card`, `form-row` untuk field berpasangan, `.error-text` per field, konfirmasi sebelum delete.
- **DVR form**: password memakai `type="password"` dengan `autocomplete="new-password"` + placeholder "Biarkan kosong jika tidak diubah" pada edit; pada create required.
- Tampilkan badge kategori unit (`kebun`/`pks`/`ro`) dan badge kategori kamera bila terisi.
- Halaman index DVR: filter unit via dropdown (`?unit=`) yang mengirim GET ke `admin.dvrs.index`.

### 4. Pengujian (PHPUnit, `tests/Feature` — file baru `MasterDataTest.php`)

Gunakan `RefreshDatabase`, `actingAs` superadmin dan teknis. Minimal:

- **Proteksi**: route unit/dvr/camera → guest redirect `/login`; user `teknis` → `403`; superadmin → `200`.
- **Unit CRUD**: buat/ubah/hapus unit; validasi `kode` unik & `kategori` invalid ditolak; delete unit menghapus DVR & kameranya (cascade).
- **DVR CRUD**: buat DVR (password tersimpan terenkripsi di DB, `getPlainPassword()` benar); edit tanpa isi password → password lama tak berubah; edit dengan password baru → berubah; **response halaman tidak mengandung password plaintext**.
- **Camera CRUD**: buat kamera channel 1–16; duplicate channel di DVR sama ditolak (validasi form), channel sama di DVR beda boleh; channel di luar 1–16 ditolak; kategori opsional & bebas; camera hanya bisa dibuat di DVR yang dimaksud.
- Pastikan seluruh test lama tetap hijau.

### 5. Verifikasi wajib di akhir

Jalankan dan pastikan sukses, lalu laporkan:
- `php artisan migrate:fresh --seed`
- `php artisan test`
- `php artisan route:list` (pastikan route unit/dvr/camera ada)
- `vendor/bin/pint` untuk kerapian kode

## Batasan Lain

- Jangan menambah dependensi baru.
- Jangan menambahkan komentar yang tidak perlu.
- Jangan memulai server — cukup testing.
- Jika ada ketidakjelasan (misal detail URL/route pattern), tanyakan dulu sebelum melanjutkan.