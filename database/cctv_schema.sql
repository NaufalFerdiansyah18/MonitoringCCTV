-- Skema database sistem monitoring CCTV (sesuai PRD bagian 6)
-- Dibuat untuk MySQL/MariaDB di Laragon.
-- Jalankan:  mysql -u root monitoring_cctv < database/cctv_schema.sql

USE monitoring_cctv;

CREATE TABLE users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  email_verified_at TIMESTAMP NULL,
  password VARCHAR(255) NOT NULL,
  remember_token VARCHAR(100) NULL,
  role ENUM('superadmin','teknis') NOT NULL DEFAULT 'teknis',
  technical_group_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE INDEX users_email_unique (email),
  INDEX users_technical_group_id_foreign (technical_group_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE technical_groups (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE technical_group_unit_categories (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  technical_group_id BIGINT UNSIGNED NOT NULL,
  kategori ENUM('kebun','pks','ro') NOT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE INDEX tguc_technical_group_id_kategori_unique (technical_group_id, kategori),
  CONSTRAINT tguc_technical_group_id_foreign
    FOREIGN KEY (technical_group_id) REFERENCES technical_groups(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE units (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  kode VARCHAR(255) NOT NULL,
  nama VARCHAR(255) NOT NULL,
  kategori ENUM('kebun','pks','ro') NOT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE INDEX units_kode_unique (kode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE dvrs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  unit_id BIGINT UNSIGNED NOT NULL,
  nama VARCHAR(255) NOT NULL,
  ip_local VARCHAR(255) NOT NULL,
  port_local INT UNSIGNED NOT NULL DEFAULT 554,
  ip_public VARCHAR(255) NULL,
  port_public INT UNSIGNED NULL,
  username VARCHAR(255) NOT NULL,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  INDEX dvrs_unit_id_foreign (unit_id),
  CONSTRAINT dvrs_unit_id_foreign
    FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cameras (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  dvr_id BIGINT UNSIGNED NOT NULL,
  channel TINYINT UNSIGNED NOT NULL,
  nama_lokasi VARCHAR(255) NOT NULL,
  kategori VARCHAR(255) NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE INDEX cameras_dvr_id_channel_unique (dvr_id, channel),
  INDEX cameras_dvr_id_foreign (dvr_id),
  CONSTRAINT cameras_dvr_id_foreign
    FOREIGN KEY (dvr_id) REFERENCES dvrs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE users
  ADD CONSTRAINT users_technical_group_id_foreign
    FOREIGN KEY (technical_group_id) REFERENCES technical_groups(id) ON DELETE SET NULL;