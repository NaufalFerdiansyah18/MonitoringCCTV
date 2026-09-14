# Prompt Implementasi — Langkah 1: Dasar Data (Migrasi, Model, Relasi, Seeder)

Gunakan prompt ini untuk langkah pertama implementasi sistem monitoring CCTV sesuai `PRD.md` pada proyek Laravel ini.

---

## Instruksi

1. **Baca dulu `PRD.md` secara lengkap**, terutama bagian **3. Master Data**, **6. Skema Database**, dan **7. Non-Fungsional**. Ikuti skema dan keputusan yang sudah ada di sana.
2. Jangan mengubah atau menimpa `PRD.md`.
3. Jangan menyentuh `CctvTestController` dan halaman `cctv-test` yang sudah ada — itu akan dipakai/diperluas di langkah berikutnya.
4. Kerjakan **hanya struktur data dasar** untuk langkah ini: migrasi, model, relasi, cast, dan seeder. **Belum** membuat controller, view, CRUD, halaman liveview, atau route baru kecuali diperlukan untuk konfigurasi dasar auth.

## Konteks Proyek

- Stack: Laravel (PHP), project sudah jalan di direktori ini.
- Halaman tes manual sudah ada (`CctvTestController`), menyalurkan RTSP → FFmpeg → HLS.
- Belum ada master data, auth, maupun RBAC.
- Tabel `users`, `cache`, `jobs` sudah ada (migrasi default Laravel).

## Tugas

### 1. Migrasi & Model

Buat migrasi beserta model dengan pola dan skema berikut (nama tabel/model mengikuti konvensi Laravel):

- **`units`** — model `Unit`
  - `kode` string, unik
  - `nama` string
  - `kategori` enum(`kebun`, `pks`, `ro`)
  - Relasi: `hasMany(Dvr::class)`

- **`dvrs`** — model `Dvr`
  - `unit_id` FK → `units`, index
  - `nama` string
  - `ip_local` string
  - `port_local` integer (default 554)
  - `ip_public` string nullable — jika kosong artinya mode Public tidak tersedia
  - `port_public` integer nullable
  - `username` string (akun DVR)
  - `password` string — **disimpan terenkripsi** (jangan plaintext), dan **never** muncul dalam serialisasi/API
  - Relasi: `belongsTo(Unit::class)`, `hasMany(Camera::class)`

- **`cameras`** — model `Camera`
  - `dvr_id` FK → `dvrs`, index
  - `channel` integer dengan constraint 1–16
  - `nama_lokasi` string
  - `kategori` string nullable (teks bebas, mis. PKS/Bioglas/Timbangan/Rebusan)
  - Constraint unik `(dvr_id, channel)`
  - Relasi: `belongsTo(Dvr::class)`

- **`technical_groups`** — model `TechnicalGroup` (master data "Grup Teknis" yang fleksibel)
  - `nama` string (bebas: tekpol, tanaman, dst.)
  - Relasi banyak-ke-banyak ke kategori unit via tabel pivot
  - Tabel pivot `technical_group_unit_categories`:
    - `technical_group_id` FK → `technical_groups`
    - `kategori` (enum sama seperti unit: `kebun`/`pks`/`ro`)
    - constraint unik `(technical_group_id, kategori)`
  - Relasi: `hasMany(User::class)`

- **Modifikasi `users`** (migrasi baru — jangan ubah migrasi lama, buat migrasi penambah kolom):
  - `role` enum(`superadmin`, `teknis`) dengan default `teknis`
  - `technical_group_id` FK → `technical_groups` nullable, index
  - Relasi di model `User`: `belongsTo(TechnicalGroup::class)`, plus helper:
    - `isSuperadmin(): bool`
    - `allowedUnitCategories(): Collection` → daftar kategori unit yang boleh diakses (superadmin = semua; teknis = kategori milik grup pilihan user)

### 2. Keamanan password DVR

- Simpan `password` DVR terenkripsi (mis. mutator `setPasswordAttribute` dengan `Crypt::encryptString`).
- Sediakan cara membaca plaintext hanya di sisi server (mis. method `getPlainPassword()`), dan pastikan `password` berada dalam `$hidden` sehingga tidak pernah bocor lewat serialisasi/JSON.
- Jangan pernah menampilkan/mengirim password DVR apa adanya ke frontend.

### 3. Seeder & Factory

- Factory untuk `Unit`, `Dvr`, `Camera`, `TechnicalGroup`, dan dukungan `User` untuk testing.
- Seeder yang membuat:
  - 1 akun **superadmin** (nama, email, password dari env/`.env` atau konfigurasi, dengan fallback yang jelas dicatat).
  - Contoh minimal `TechnicalGroup` (mis. grup "tanaman" → kategori `kebun`, grup "tekpol" → kategori `pks`) hanya sebagai data contoh/demo.
- Pastikan `php artisan db:seed` dan factory jalan tanpa error.

### 4. Pengujian (dasar)

- Test migrasi berjalan (`php artisan migrate:fresh` sukses).
- Model test (Pest/PHPUnit — lihat konvensi test yang sudah ada di folder `tests/`) yang memverifikasi:
  - Relasi Unit→Dvr→Camera berfungsi.
  - Constraint unik `(dvr_id, channel)` menolak channel duplikat.
  - Constraint channel di luar 1–16 ditolak.
  - Password DVR tersimpan terenkripsi dan method baca plaintext benar.
  - Helper `isSuperadmin`/kategori akses benar.

### 5. Verifikasi wajib di akhir

Jalankan dan pastikan sukses, lalu laporkan hasilnya:
- `php artisan migrate:fresh --seed`
- `php artisan test` (atau perintah test sesuai setup project)

## Batasan Lain

- Jangan menambahkan komentar yang tidak perlu di kode.
- Ikuti gaya kode & konvensi yang sudah ada di project ini (periksa dulu file di `app/`, `database/`, `tests/`).
- Jangan memulai server (`.env`/`artisan serve`) — cukup testing.
- Jika ada ketidakjelasan data/skema, tanyakan dulu sebelum melanjutkan.