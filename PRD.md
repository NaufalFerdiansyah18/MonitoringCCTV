# PRD — Sistem Monitoring CCTV Pabrik

**Versi:** 0.2 (draf revisi)
**Stack:** Laravel (PHP) + FFmpeg → HLS, playback browser, verifikasi RTSP via **VLC → Open Network Stream**
**Tanggal:** 2026-09-14

## 1. Latar Belakang & Tujuan

Saat ini monitoring CCTV baru berupa halaman tes manual (`CctvTestController`): input satu URL RTSP → FFmpeg transcode ke HLS → diputar di browser. Belum ada master data, hak akses, maupun tampilan per-unit/per-DVR.

Sistem yang dibangun harus:
1. Menyimpan **master data** Unit → DVR → Camera beserta konfigurasi RTSP-nya.
2. Membatasi akses user: **superadmin** bisa semuanya; **teknis** hanya liveview pada unit sesuai grup-nya (grup fleksibel, bukan cuma tekpol/tanaman).
3. Menyediakan **liveview multi-kamera** (grid 1/4/8/16) dengan RTSP yang **digenerate otomatis** (tidak disimpan di DB) berformat **Dahua**.
4. Memisahkan tampilan **Local** dan **Public** berdasarkan ketersediaan IP public DVR.

## 2. Aktor & Role

| Role | Hak Akses |
|---|---|
| **Superadmin** | Semua unit; kelola masterdata (Unit/DVR/Camera); kelola user & grup teknis; liveview semua unit. |
| **Teknis** | **Liveview saja** (tanpa menambah/mengubah data), dibatasi ke unit sesuai **Grup Teknis**-nya. |

### 2.1 Grup Teknis (fleksibel)
- Masterdata yang dikelola superadmin, berisi `nama` bebas (contoh: `tekpol`, `tanaman`, `listrik`, `mekanik`, dst.) dan relasi banyak-ke-banyak ke **kategori unit** (`kebun`/`pks`/`ro`).
- User teknis cukup **dipilihkan satu Grup Teknis** → otomatis mendapat akses ke semua unit dengan kategori yang terhubung ke grup tersebut.
- Menambah tipe teknis baru = **buat grup baru, tanpa ubah kode**.
- *(Opsional, untuk kapan-kapan)* pivot `user_units` untuk menyesuaikan unit spesifik di luar kategori grup.

Contoh: grup **"tanaman"** terkait kategori `kebun` → seluruh user di grup itu hanya melihat unit kategori `kebun`.

## 3. Master Data

### 3.1 Unit
| Kolom | Tipe | Ket |
|---|---|---|
| `kode` | string, unik | contoh `U1`, `U2`, `U3` |
| `nama` | string | Unit 1 / Unit 2 / Unit 3 |
| `kategori` | enum | `kebun` ¦ `pks` ¦ `ro` |

### 3.2 DVR (1 DVR menampung 8–16 kamera)
| Kolom | Tipe | Ket |
|---|---|---|
| `id` | PK | |
| `unit_id` | FK → Unit | |
| `nama` | string | |
| `ip_local` | string | IP jaringan LAN |
| `port_local` | int | port RTSP LAN (umum 554) |
| `ip_public` | string, opsional | IP public; **kosong → mode Public tak tersedia untuk DVR ini** |
| `port_public` | int, opsional | port public/NAT |
| `username` | string | akun DVR |
| `password` | string | akun DVR → **dienkripsi saat simpan**, tak pernah ditampilkan apa adanya |

### 3.3 Camera
| Kolom | Tipe | Ket |
|---|---|---|
| `id` | PK | |
| `dvr_id` | FK → DVR | |
| `channel` | int 1–16 | unik per DVR (`unique(dvr_id, channel)`) |
| `nama_lokasi` | string | contoh: *Crh Timbangan*, *Rebusan* |
| `kategori` | string, opsional | **teks bebas** untuk memisahkan kamera, contoh: *PKS*, *Bioglas*, *Timbangan* |

## 4. Generator RTSP (DIGENERATE — tidak disimpan di DB)

Format **Dahua**:

```
rtsp://{username}:{password}@{host}:{port}/cam/realmonitor?channel={channel}&subtype={subtype}
```

- **Mode Local** → `host` = `ip_local`, `port` = `port_local`.
- **Mode Public** → `host` = `ip_public`, `port` = `port_public`; tidak dibangkitkan bila `ip_public` kosong.
- `subtype`: `0` = stream utama, `1` = substream (untuk grid banyak kamera hemat bandwidth; dijadikan konfigurasi global di `.env`).
- URL dibangkitkan on-the-fly di service/controller, tidak ada kolom `rtsp_url` di database.
- Verifikasi dari URL hasil generate: buka di **VLC → Open Network Stream**.

## 5. Halaman / Alur

### 5.1 Auth
- Login (email + password), logout.
- Seeder superadmin awal.
- Middleware per-peran: masterdata & user/grup hanya superadmin; halaman liveview menyesuaikan kategori user.
- Menolak akses unit di luar kategori user (filter di sisi server).

### 5.2 Login & Manajemen Akses (khusus superadmin)
- CRUD **User** (nama, email, password, role; untuk role teknis dipilih Grup Teknis).
- CRUD **Grup Teknis** (nama + pilihan kategori unit).

### 5.3 Masterdata (khusus superadmin)
- CRUD **Unit** → CRUD **DVR** (pilih unit, form IP local/public, akun) → CRUD **Camera** (pilih DVR, channel 1–16, nama lokasi, kategori bebas).
- Halaman detail DVR menampilkan daftar kamera miliknya.

### 5.4 Liveview (tampilan utama)
- Pemilih **Unit** — hanya unit kategori yang diizinkan grup user (superadmin melihat semua).
- Setelah pilih unit → lihat **DVR** dalam unit → pilih kamera → grid **1/4/8/16**.
- **Toggle Local ↔ Public**; kamera dengan DVR tanpa `ip_public` disembunyikan saat mode Public.
- Streaming memakai mekanisme yang sudah ada di `CctvTestController` (FFmpeg RTSP→HLS, transcode H.265→H.264), diperluas **per-kamera** dengan direktori HLS terpisah (`public/hls/{streamKey}/`).
- Batas stream bersamaan **configurable** (contoh `MAX_CONCURRENT_STREAMS` di `.env`).

## 6. Skema Database

```
users            (id, name, email, password, role enum superadmin|teknis,
                  technical_group_id FK nullable, timestamps)
technical_groups (id, nama, timestamps)
technical_group_unit_categories (technical_group_id FK, kategori,
                                 unique(technical_group_id, kategori))
-- (opsional) user_units (user_id FK, unit_id FK) penyesuaian unit spesifik
units            (id, kode unique, nama, kategori)
dvrs             (id, unit_id FK, nama, ip_local, port_local,
                  ip_public?, port_public?, username, password, timestamps)
cameras          (id, dvr_id FK, channel, nama_lokasi, kategori?,
                  unique(dvr_id, channel), timestamps)
```

Tabel `users` dikembangkan dari default Laravel (tambah kolom `role` dan `technical_group_id`).

## 7. Non-Fungsional

- Password DVR disimpan terenkripsi (Crypt), tidak pernah dikirim balik ke frontend.
- RBAC via middleware; data liveview difilter lewat relasi user→grup→kategori unit.
- Kapasitas stream dibatasi konfigurasi (1 proses FFmpeg per kamera).
- Mode public bergantung kondisi jaringan/NAT/port-forwarding CCTV.

## 8. Di Luar Scope (milestone awal)

Rekam/playback, alarm/notifikasi, PTZ, export arsip, monitoring online/offline real-time → lanjutan setelah PRD disetujui.

## 9. Roadmap Implementasi (setelah PRD disetujui)

1. Migrasi + Model: `Unit`, `Dvr`, `Camera`, `TechnicalGroup` (pivot), skin `User`.
2. Auth + RBAC middleware + seeder superadmin.
3. CRUD User & Grup Teknis.
4. CRUD masterdata Unit/DVR/Camera.
5. Service generate RTSP Dahua (local & public).
6. Liveview grid + integrasi FFmpeg/HLS multi-stream.
7. Pengujian: VLC `Open Network Stream` terhadap URL hasil generate.

## 10. Poin Perlu Konfirmasi

1. **Unit RO** — benar hanya diakses superadmin (di luar grup teknis)?
2. **Substream** (`subtype=1`) untuk grid — dibuka untuk beban lebih ringan, atau semua pakai stream utama (`subtype=0`)?
3. Perlu **filter beranda berdasar kategori kamera** (misal tampil terpisah PKS vs Bioglas), atau cukup kolom di form?
4. Kamera di satu DVR boleh punya **kategori campuran** (misal kamera 1–8 PKS, 9–16 Bioglas pada DVR yang sama)? Diasumsikan **boleh**, karena kategori menempel di kamera.