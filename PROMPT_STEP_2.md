# Prompt Implementasi — Langkah 2: Auth & RBAC (Aktor & Role)

Gunakan prompt ini untuk langkah kedua implementasi sistem monitoring CCTV, sesuai bagian **2. Aktor & Role** pada `PRD.md`.

---

## Instruksi

1. **Baca dulu `PRD.md`**, terutama bagian **2. Aktor & Role** dan **5.1 Auth**, **5.2 Login & Manajemen Akses (khusus superadmin)**, serta **7. Non-Fungsional**.
2. Jangan mengubah atau menimpa: `PRD.md`, `PROMPT_STEP_1.md`, dan seluruh hasil Langkah 1 (migrasi, model `Unit`/`Dvr`/`Camera`/`TechnicalGroup`/`TechnicalGroupUnitCategory`, perluasan `User`, factory, seeder, `tests/Feature/CctvModelsTest.php`).
3. **Jangan** mengubah perilaku `CctvTestController` & halaman `cctv-test` yang sudah ada.
4. **Belum** membuat CRUD masterdata Unit/DVR/Camera, service generate RTSP, maupun halaman liveview — itu langkah berikutnya. Fokus langkah ini: **autentikasi, otorisasi peran, dan manajemen user + Grup Teknis oleh superadmin**.
5. Ikuti konvensi yang sudah ada: PHPUnit (tanpa Pest) di `tests/Feature`, DB testing sqlite in-memory (sudah dikonfigurasi di `phpunit.xml`), gaya kode Laravel + Pint.

## Konteks (Sudah Ada dari Langkah 1)

- Tabel & model: `users` (kolom `role` enum `superadmin`/`teknis`, `technical_group_id`), `technical_groups`, pivot `technical_group_unit_categories`, `units`, `dvrs`, `cameras`.
- Model `User` sudah punya helper: `isSuperadmin(): bool` dan `allowedUnitCategories(): Collection` (superadmin → semua kategori `kebun`/`pks`/`ro`; teknis → kategori grup pilihan user).
- Seeder tersedia: `SuperadminSeeder` (email/password dari `SUPERADMIN_*` di `.env`) dan `TechnicalGroupSeeder` (grup contoh "tanaman" → `kebun`, "tekpol" → `pks`).
- **Belum ada scaffolding auth** (tidak ada Breeze/jetstream). Buat auth manual yang ringan.

## Keputusan Akses yang Harus Dipatuhi

| Role | Hak Akses |
|---|---|
| `superadmin` | Semua unit; kelola masterdata; kelola user & Grup Teknis; akses semua halaman. |
| `teknis` | **Liveview saja**; TIDAK bisa akses manajemen user/grup/masterdata; hanya unit sesuai kategori Grup Teknis-nya. |

- Grup Teknis = nama bebas + banyak-ke-banyak kategori unit (`kebun`/`pks`/`ro`).
- Menambah jenis teknis baru = cukup membuat grup baru, tanpa mengubah kode.
- *(Opsional, jangan diimplementasi dulu)* pivot `user_units` untuk unit spesifik di luar kategori grup.

## Tugas

### 1. Autentikasi Login/Logout (ringan, tanpa package tambahan)

- Halaman **Login**: email + password (blade sederhana, layout bersih tanpa framework CSS berat; ikuti style halaman yang sudah ada).
- Route:
  - `GET /login` (guest) → tampilkan form
  - `POST /login` → `Auth::attempt`, redirect sesuai peran (superadmin → beranda/dashboard; teknis → beranda liveview placeholder); tampilkan pesan error bila gagal
  - `POST /logout` + `GET /login` setelah logout
  - Halaman-halaman selain login dilindungi middleware `auth`; saat belum login redirect ke `/login`
- Validasi email + password wajib; jangan ungkap apakah email terdaftar (pesan generic "Email atau password salah").
- Gunakan `Hash::make` / `Auth::attempt` standar Laravel.

### 2. Middleware RBAC

Buat middleware (atau dua middleware) terdaftar di `bootstrap/app.php` dengan alias:
- **`role.superadmin`** — hanya `role=superadmin`; selain itu `403`.
- **`role.teknis`** (opsional) — hanya `role=teknis`.
- Seluruh route dalam grup terproteksi `auth`:
  - Kelompok **manajemen akses & user/grup** → middleware `role.superadmin`.
  - Halaman beranda/dashboard yang akan jadi liveview (placeholder sementara) → semua user login boleh, tetapi **filter data via `allowedUnitCategories()`**.

Terapkan **filter di sisi server** (jangan hanya sembunyi di UI): semua query unit/DVR/camera yang akan ditampilkan untuk user teknis harus dibatasi sesuai `allowedUnitCategories()`.

### 3. Manajemen User (khusus superadmin)

Route & halaman CRUD **User**:
- List user (nama, email, role, grup).
- Create/Edit: `name`, `email` (unik), `password` (wajib saat create, opsional saat edit — kosong berarti tidak diubah), `role` (`superadmin`/`teknis`), `technical_group_id` (dropdown Grup Teknis; wajib bila role `teknis`, kosong/null bila `superadmin`).
- Delete user (jangan izinkan menghapus diri sendiri yang sedang login — superadmin aktif).
- Validasi lengkap; tampilkan pesan galat/error dan flash success.

### 4. Manajemen Grup Teknis (khusus superadmin)

Route & halaman CRUD **Grup Teknis**:
- List grup (nama + ringkasan kategori yang terhubung).
- Create/Edit: `nama` (unik), pilihan banyak (checkbox) kategori unit `kebun`/`pks`/`ro` → simpan ke pivot `technical_group_unit_categories` (hapus-barulah-isi ulang atau `sync`).
- Delete grup: beri konfirmasi; pastikan perilaku FK `nullOnDelete` di `users.technical_group_id` berjalan (user terkait tetap ada tapi grupnya kosong → tidak punya akses unit).

### 5. Beranda / Dashboard (placeholder sementara)

- Halaman beranda login untuk semua role. Belum ada liveview: buat placeholder yang menampilkan daftar **unit yang boleh diakses** user saat itu (memakai `allowedUnitCategories()`), masing-masing masih non-interaktif (tombol/menarik ke liveview ditandai "coming soon").
- Superadmin melihat semua unit; teknis hanya unit kategori grupnya.

### 6. Pengujian (PHPUnit, `tests/Feature`)

Gunakan `RefreshDatabase`. Test minimal:
- **Login**: sukses dengan superadmin tersedia; gagal dengan password salah → kembali ke form dengan error; logout berhasil.
- **Protokol route**: halaman user/grup master → guest redirect ke `/login`; user `teknis` → `403`; user `superadmin` → `200`.
- **CRUD user**: superadmin bisa membuat user teknis; validasi nama/email/password/grup benar; user teknis tidak bisa akses endpoint create/update/delete user (403).
- **CRUD grup**: superadmin bisa membuat grup + pasang 2 kategori; update kategori tersinkron di pivot; delete grup meng-null-kan `technical_group_id` user terkait; user teknis tidak bisa (403).
- **Akses unit**: user teknis dengan grup kategori `kebun` melihat unit `kebun` saja; tidak melihat unit `pks`/`ro`.

### 7. Verifikasi wajib di akhir

Jalankan dan pastikan sukses, lalu laporkan:
- `php artisan migrate:fresh --seed`
- `php artisan test`
- `php artisan route:list` (pastikan route login/logout/user/grup ada)
- `vendor/bin/pint` untuk kerapian kode

## Batasan Lain

- Jangan menambahkan dependensi baru (no Breeze/jetstream/ui) tanpa persetujuan.
- Jangan menambahkan komentar yang tidak perlu.
- Jangan memulai server — cukup testing.
- Jika ada ketidakjelasan, tanyakan dulu sebelum melanjutkan.