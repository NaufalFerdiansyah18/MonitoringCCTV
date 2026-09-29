-- ========================================
-- SQL Manual untuk Sistem Monitoring CCTV
-- ========================================
-- Dibuat sesuai PRD.md bagian 6 (Skema Database)
-- Gunakan file ini jika ingin membuat tabel secara manual via HeidiSQL/phpMyAdmin
-- atau sebagai referensi struktur database
--
-- CATATAN: Laravel migration tetap menjadi sumber kebenaran utama.
-- File ini hanya untuk keperluan setup manual atau dokumentasi.
-- ========================================

-- Gunakan database yang sesuai
-- USE nama_database_anda;

-- ========================================
-- 1. TECHNICAL GROUPS
-- ========================================
CREATE TABLE IF NOT EXISTS `technical_groups` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama` VARCHAR(255) NOT NULL COMMENT 'Nama grup teknis (contoh: tanaman, tekpol, listrik)',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 2. TECHNICAL GROUP UNIT CATEGORIES (Pivot)
-- ========================================
CREATE TABLE IF NOT EXISTS `technical_group_unit_categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `technical_group_id` BIGINT UNSIGNED NOT NULL,
  `kategori` ENUM('kebun', 'pks', 'ro') NOT NULL COMMENT 'Kategori unit yang bisa diakses grup ini',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_group_category` (`technical_group_id`, `kategori`),
  KEY `idx_technical_group_id` (`technical_group_id`),
  CONSTRAINT `fk_tguc_technical_group` 
    FOREIGN KEY (`technical_group_id`) 
    REFERENCES `technical_groups` (`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 3. USERS (Modifikasi dari tabel Laravel default)
-- ========================================
-- CATATAN: Tabel users sudah ada dari Laravel default
-- Query ini hanya menambahkan kolom baru, JANGAN jalankan CREATE TABLE
-- Jalankan ALTER TABLE ini jika tabel users sudah ada:

ALTER TABLE `users` 
  ADD COLUMN `role` ENUM('superadmin', 'teknis') NOT NULL DEFAULT 'teknis' COMMENT 'Role user',
  ADD COLUMN `technical_group_id` BIGINT UNSIGNED NULL COMMENT 'FK ke grup teknis, null untuk superadmin',
  ADD KEY `idx_technical_group_id` (`technical_group_id`),
  ADD CONSTRAINT `fk_users_technical_group` 
    FOREIGN KEY (`technical_group_id`) 
    REFERENCES `technical_groups` (`id`) 
    ON DELETE SET NULL;

-- Jika tabel users belum ada sama sekali, gunakan ini:
-- CREATE TABLE IF NOT EXISTS `users` (
--   `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
--   `name` VARCHAR(255) NOT NULL,
--   `email` VARCHAR(255) NOT NULL,
--   `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
--   `password` VARCHAR(255) NOT NULL,
--   `role` ENUM('superadmin', 'teknis') NOT NULL DEFAULT 'teknis',
--   `technical_group_id` BIGINT UNSIGNED NULL,
--   `remember_token` VARCHAR(100) NULL,
--   `created_at` TIMESTAMP NULL DEFAULT NULL,
--   `updated_at` TIMESTAMP NULL DEFAULT NULL,
--   PRIMARY KEY (`id`),
--   UNIQUE KEY `users_email_unique` (`email`),
--   KEY `idx_technical_group_id` (`technical_group_id`),
--   CONSTRAINT `fk_users_technical_group` 
--     FOREIGN KEY (`technical_group_id`) 
--     REFERENCES `technical_groups` (`id`) 
--     ON DELETE SET NULL
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 4. UNITS
-- ========================================
CREATE TABLE IF NOT EXISTS `units` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kode` VARCHAR(50) NOT NULL COMMENT 'Kode unit unik (contoh: U1, U2, U3)',
  `nama` VARCHAR(255) NOT NULL COMMENT 'Nama unit',
  `kategori` ENUM('kebun', 'pks', 'ro') NOT NULL COMMENT 'Kategori unit',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `units_kode_unique` (`kode`),
  KEY `idx_kategori` (`kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 5. DVRS
-- ========================================
CREATE TABLE IF NOT EXISTS `dvrs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `unit_id` BIGINT UNSIGNED NOT NULL,
  `nama` VARCHAR(255) NOT NULL COMMENT 'Nama DVR',
  `ip_local` VARCHAR(45) NOT NULL COMMENT 'IP lokal DVR',
  `port_local` INT UNSIGNED NOT NULL DEFAULT 554 COMMENT 'Port RTSP lokal (default 554)',
  `ip_public` VARCHAR(45) NULL COMMENT 'IP publik DVR (nullable, kosong = tidak ada akses public)',
  `port_public` INT UNSIGNED NULL COMMENT 'Port RTSP publik (nullable)',
  `username` VARCHAR(255) NOT NULL COMMENT 'Username akun DVR',
  `password` TEXT NOT NULL COMMENT 'Password DVR (disimpan terenkripsi di aplikasi)',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_unit_id` (`unit_id`),
  CONSTRAINT `fk_dvrs_unit` 
    FOREIGN KEY (`unit_id`) 
    REFERENCES `units` (`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 6. CAMERAS
-- ========================================
CREATE TABLE IF NOT EXISTS `cameras` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `dvr_id` BIGINT UNSIGNED NOT NULL,
  `channel` TINYINT UNSIGNED NOT NULL COMMENT 'Channel kamera di DVR (1-16)',
  `nama_lokasi` VARCHAR(255) NOT NULL COMMENT 'Nama lokasi kamera',
  `kategori` VARCHAR(100) NULL COMMENT 'Kategori kamera (teks bebas: PKS, Bioglas, Timbangan, dst)',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_dvr_channel` (`dvr_id`, `channel`),
  KEY `idx_dvr_id` (`dvr_id`),
  CONSTRAINT `fk_cameras_dvr` 
    FOREIGN KEY (`dvr_id`) 
    REFERENCES `dvrs` (`id`) 
    ON DELETE CASCADE,
  CONSTRAINT `chk_channel_range` CHECK (`channel` >= 1 AND `channel` <= 16)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 7. (OPSIONAL) USER_UNITS - Pivot untuk penyesuaian unit spesifik
-- ========================================
-- Belum diimplementasikan pada fase ini
-- Uncomment jika diperlukan di masa mendatang:

-- CREATE TABLE IF NOT EXISTS `user_units` (
--   `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
--   `user_id` BIGINT UNSIGNED NOT NULL,
--   `unit_id` BIGINT UNSIGNED NOT NULL,
--   `created_at` TIMESTAMP NULL DEFAULT NULL,
--   `updated_at` TIMESTAMP NULL DEFAULT NULL,
--   PRIMARY KEY (`id`),
--   UNIQUE KEY `unique_user_unit` (`user_id`, `unit_id`),
--   KEY `idx_user_id` (`user_id`),
--   KEY `idx_unit_id` (`unit_id`),
--   CONSTRAINT `fk_user_units_user` 
--     FOREIGN KEY (`user_id`) 
--     REFERENCES `users` (`id`) 
--     ON DELETE CASCADE,
--   CONSTRAINT `fk_user_units_unit` 
--     FOREIGN KEY (`unit_id`) 
--     REFERENCES `units` (`id`) 
--     ON DELETE CASCADE
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- SELESAI
-- ========================================
-- Struktur database siap digunakan
-- Selanjutnya isi data master via INSERT atau Laravel Seeder
