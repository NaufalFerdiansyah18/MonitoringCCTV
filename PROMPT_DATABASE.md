# Prompt Implementasi — Skema Database (Bagian 6 PRD)

Gunakan prompt ini untuk membangun **lapisan database** sistem monitoring CCTV, sesuai bagian **6. Skema Database** dan **3. Master Data** pada `PRD.md`.

---

## Instruksi

1. **Baca dulu `PRD.md`**, terutama bagian **3. Master Data**, **6. Skema Database**, dan **7. Non-Fungsional**. Ikuti skema dan keputusan yang ada di sana.
2. Jangan mengubah atau menimpa `PRD.md` dan seluruh file `PROMPT_STEP_*.md` yang sudah ada.
3. Jangan menyentuh `CctvTestController` dan halaman `cctv-test` yang sudah ada.
4. Kerjakan **hanya lapisan data**: migrasi, model, relasi, cast, fakta (factory), seeder, dan test dasar. **Belum** membuat controller, view, CRUD, service RTSP, atau halaman liveview.
5. **Jika struktur database ini sudah sebagian/seluruhnya ada** di proyek (migrasi, model, factory, seeder sudah dibuat dari langkah sebelumnya), jangan duplikat — **bandingkan dan selaraskan**: perbaiki perbedaan, tambahkan yang hilang, pastikan persis sesuai skema ini, lalu laporkan statusnya.
6. Ikuti konvensi proyek yang sudah ada: Laravel + Eloquent standar, PHPUnit (tanpa Pest) di `tests/Feature`, DB testing sqlite in-memory di `phpunit.xml`, gaya kode Laravel + Pint.

## Konteks Proyek

- Stack: Laravel (PHP), project sudah jalan di direktori ini.
- Halaman tes manual sudah ada (`CctvTestController`) menyalurkan RTSP → FFmpeg → HLS — jangan disentuh.
- Tabel default Laravel `users`, `cache`, `jobs` sudah ada (migrasi `0001_01_01_*`). Tabel `users` **dikembangkan dari default Laravel** (tambah kolom, jangan ubah migrasi lama — buat migrasi baru).
- Belum ada master data, auth, maupun RBAC.

## Skema Database (Wajib, Sesuai Bagian 6 PRD)

```
users            (id, name, email, password, role enum superadmin|teknis,
                  technical_group_id FK nullable, timestamps)
technical_groups (id, nama, timestamps)
technical_group_unit_categories (technical_group_id FK, kategori,
                                 unique(technical_group_id, kategori))
units            (id, kode unique, nama, kategori)
dvrs             (id, unit_id FK, nama, ip_local, port_local,
                  ip_public nullable, port_public nullable, username, password, timestamps)
cameras          (id, dvr_id FK, channel, nama_lokasi, kategori nullable,
                  unique(dvr_id, channel), timestamps)
```

Pemetaan kolom → tipe Laravel:

### `technical_groups` (pendahulu — dibuat lebih dulu, dipakai sebagai FK)
- `id` PK
- `nama` string (nama bebas: `tanaman`, `tekpol`, `listrik`, dst.)
- `timestamps`

### `technical_group_unit_categories` (pivot relasi grup ↔ kategori unit)
- `id` PK
- `technical_group_id` FK → `technical_groups` (index; perilaku FK mengikuti keputusan di bawah)
- `kategori` enum(`kebun`, `pks`, `ro`)
- **unique(`technical_group_id`, `kategori`)**
- `timestamps`

### `units`
- `id` PK
- `kode` string, **unique**
- `nama` string
- `kategori` enum(`kebun`, `pks`, `ro`)
- `timestamps`

### `dvrs` (1 unit punya banyak DVR; 1 DVR menampung 8–16 kamera)
- `id` PK
- `unit_id` FK → `units` (index; perilaku FK mengikuti keputusan di bawah)
- `nama` string
- `ip_local` string (IP LAN)
- `port_local` integer unsigned, default `554`
- `ip_public` string **nullable** — kosong berarti mode Public **tidak tersedia** untuk DVR ini
- `port_public` integer unsigned **nullable**
- `username` string (akun DVR)
- `password` string — **disimpan terenkripsi**, tidak pernah tampil mentah
- `timestamps`

### `cameras`
- `id` PK
- `dvr_id` FK → `dvrs` (index; perilaku FK mengikuti keputusan di bawah)
- `channel` integer, rentang **1–16**, **unique bersamaan `dvr_id`** → unique(`dvr_id`, `channel`)
- `nama_lokasi` string (contoh: `Crh Timbangan`, `Rebusan`)
- `kategori` string **nullable** (teks bebas: `PKS`, `Bioglas`, `Timbangan`, dst.)
- `timestamps`

### Modifikasi `users` (migrasi baru — jangan ubah migrasi `users` lama)
- `role` enum(`superadmin`, `teknis`) default `teknis`
- `technical_group_id` FK → `technical_groups` **nullable** (index; perilaku FK mengikuti keputusan di bawah)

*(Opsional, jangan diimplementasi pada langkah ini)* pivot `user_units (user_id FK, unit_id FK)` untuk penyesuaian unit spesifik di luar kategori grup.

## Keputusan yang Harus Dipatuhi

### Enum kategori (satu sumber kebenaran)
- Kategori unit bernilai `kebun` / `pks` / `ro`. Pakai **satu konstanta sama** (mis. `Unit::KATEGORI`) untuk unit, pivot, dan validasi.

### Perilaku FK (konsistenkan, jangan asal `cascade`)
- `technical_group_unit_categories.technical_group_id` → `cascadeOnDelete` (kategori ikut terhapus saat grup dihapus).
- `users.technical_group_id` → `nullOnDelete` (saat grup dihapus, user **tetap ada** tapi tanpa grup → tidak punya akses unit; jangan meniadakan user-nya).
- `dvrs.unit_id` → `cascadeOnDelete` (DVR ikut terhapus bila unit dihapus).
- `cameras.dvr_id` → `cascadeOnDelete` (kamera ikut terhapus bila DVR dihapus).

### Keamanan password DVR
- Simpan `password` DVR terenkripsi (mis. mutator `Attribute::make(set: ...)` dengan `Crypt::encryptString`).
- `password` harus masuk `$hidden` agar tidak pernah bocor lewat serialisasi/JSON.
- Baca plaintext **hanya di sisi server** via method (mis. `getPlainPassword()` memakai `Crypt::decryptString`).
- Jangan pernah menampilkan/mengirim password DVR apa adanya ke frontend.

### Constraint channel kamera
- Channel wajib dalam 1–16. Terapkan paling tidak di lapisan model (mis. hook `saving` yang melempar `InvalidArgumentException` bila di luar rentang) dan di validasi form nanti.

### Helper akses user (pondasi RBAC langkah berikutnya)
- `User::isSuperadmin(): bool` → `role === 'superadmin'`.
- `User::allowedUnitCategories(): Collection` → superadmin: semua `Unit::KATEGORI`; teknis: kategori milik `technicalGroup`-nya (kosong bila tidak punya grup).

## Tugas

### 1. Migrasi (urutan menyesuaikan nomor file agar FK valid)

- `create_technical_groups_table` → `nama`, `timestamps`.
- `create_technical_group_unit_categories_table` → FK + `kategori` enum + unique gabungan + `timestamps`.
- `add_role_and_technical_group_id_to_users_table` → tambah `role` (default `teknis`) dan `technical_group_id` nullable FK ke `technical_groups`.
- `create_units_table` → `kode` unique, `nama`, `kategori` enum, `timestamps`.
- `create_dvrs_table` → kolom sesuai skema (+ `port_local` default `554`, `ip_public`/`port_public` nullable, `password` string biasa — enkripsi ditangani model).
- `create_cameras_table` → `channel` unsigned, `nama_lokasi`, `kategori` nullable, unique(`dvr_id`, `channel`).

Catatan: migrasi memakai skema/tipe yang sudah sesuai konvensi **migrasi yang ada di `database/migrations`** — samakan gaya (Blueprint, `foreignId(...)->constrained()`, `enum`, dll.).

### 2. Model & relasi

- `Unit`: `hasMany(Dvr::class)`; konstanta `KATEGORI`; casts `kategori` → `string`; fillable `kode/nama/kategori`.
- `Dvr`: `belongsTo(Unit::class)`, `hasMany(Camera::class)`; casts `port_local`/`port_public` → `integer`; `password` terenkripsi (mutator) + `$hidden` + `getPlainPassword()`; fillable sesuai kolom.
- `Camera`: `belongsTo(Dvr::class)`; casts `channel` → `integer`; konstanta `CHANNEL_MIN=1`, `CHANNEL_MAX=16` + validasi `saving`; fillable `dvr_id/channel/nama_lokasi/kategori`.
- `TechnicalGroup`: `hasMany(User::class, 'technical_group_id')`, `hasMany(TechnicalGroupUnitCategory::class)`, helper `allowedUnitCategories(): Collection` (kategori pivot).
- `TechnicalGroupUnitCategory`: `belongsTo(TechnicalGroup::class)`.
- `User`: `belongsTo(TechnicalGroup::class)`; casts `password` → `hashed` (hash tetap standar Laravel untuk login); `role` → `string`; konstanta `ROLE_SUPERADMIN`/`ROLE_TEKNIS`; `fillable` tambah `role`, `technical_group_id`; helper `isSuperadmin()` dan `allowedUnitCategories()`.

### 3. Factory & Seeder

- Factory: `TechnicalGroupFactory`, `TechnicalGroupUnitCategoryFactory`, `UnitFactory`, `DvrFactory`, `CameraFactory`, dan dukungan `UserFactory` (termasuk `role`, `technical_group_id`).
- Seeder:
  - **Superadmin** — nama, email, password dari `SUPERADMIN_*` di `.env` (dengan fallback yang jelas tercatat di log/jalankan tanpa error).
  - **`TechnicalGroup` contoh** untuk demo (mis. grup `tanaman` → kategori `kebun`, grup `tekpol` → kategori `pks`) beserta pivot-nya.
- Pastikan `php artisan db:seed` dan seluruh factory berjalan tanpa error.

### 4. Pengujian dasar (`tests/Feature`)

- Migrasi penuh `migrate:fresh` sukses.
- Relasi rantai `Unit → Dvr → Camera` berfungsi.
- Unique `(dvr_id, channel)` menolak channel duplikat pada DVR yang sama; channel beda pada DVR sama boleh.
- Channel di luar 1–16 ditolak di layer model.
- `password` DVR tersimpan **terenkripsi** (nilai DB ≠ plaintext), tidak muncul di serialisasi/JSON, dan `getPlainPassword()` mengembalikan nilai asli.
- Helper `isSuperadmin()` / `allowedUnitCategories()` benar (superadmin → semua; teknis → kategori grupnya).

### 5. Verifikasi wajib di akhir

Jalankan dan pastikan sukses, lalu laporkan:
- `php artisan migrate:fresh --seed`
- `php artisan test`
- `vendor/bin/pint`

## Batasan Lain

- **Tanpa tambahan dependensi** (tidak menambah package / library baru).
- Jangan membuat controller, view, service RTSP, `rtsp_url` di DB, maupun halaman liveview pada langkah ini.
- Jangan menambahkan komentar yang tidak perlu di kode.
- Jangan memulai server — cukup testing.
- Jika ada ketidakjelasan skema atau konvensi, tanyakan dulu sebelum melanjutkan.