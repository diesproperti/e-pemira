-- ============================================================================
-- DATABASE SCHEMA: Sistem Pemilihan Ketua Organisasi Kampus Berbasis Web
-- Engine: MySQL 8.0+ / MariaDB (InnoDB, UTF8MB4)
-- Prinsip: One Student One Vote & Secret Ballot (Kerahasiaan Suara Penuh)
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `pemira_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `pemira_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- 1. Tabel users (Akun Autentikasi Admin & Mahasiswa)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE COMMENT 'NIM untuk mahasiswa, username untuk admin',
    `name` VARCHAR(150) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'student') NOT NULL DEFAULT 'student',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `must_change_password` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = disarankan ganti password saat login pertama',
    `password_changed_at` DATETIME NULL DEFAULT NULL,
    `last_login_at` DATETIME NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. Tabel students (Master Dataset Mahasiswa)
-- Format CSV: NIM, Nama, Jenjang, Program Studi, Periode masuk, Status Mahasiswa, Status Semester
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `students`;
CREATE TABLE `students` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL UNIQUE,
    `nim` VARCHAR(30) NOT NULL UNIQUE,
    `nama` VARCHAR(150) NOT NULL,
    `jenjang` VARCHAR(20) NOT NULL DEFAULT 'S1' COMMENT 'Contoh: S1, D4, D3',
    `program_studi` VARCHAR(120) NOT NULL,
    `periode_masuk` VARCHAR(50) NOT NULL COMMENT 'Contoh: 2023/2024 Ganjil atau 2023',
    `status_mahasiswa` VARCHAR(30) NOT NULL DEFAULT 'Aktif' COMMENT 'Aktif, Cuti, Non-Aktif, Lulus',
    `status_semester` VARCHAR(30) NOT NULL DEFAULT 'Aktif' COMMENT 'Aktif, Cuti, Non-Aktif',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_students_prodi` (`program_studi`),
    INDEX `idx_students_status` (`status_mahasiswa`, `status_semester`),
    CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. Tabel elections (Periode Pemilihan)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `elections`;
CREATE TABLE `elections` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `organization_name` VARCHAR(150) NOT NULL DEFAULT 'BEM Universitas',
    `period_label` VARCHAR(50) NOT NULL DEFAULT '2026/2027',
    `description` TEXT NULL,
    `start_time` DATETIME NOT NULL,
    `end_time` DATETIME NOT NULL,
    `status` ENUM('draft', 'active', 'closed') NOT NULL DEFAULT 'draft',
    `is_result_published` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = Mahasiswa dapat melihat hasil akhir',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_elections_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. Tabel candidates (Data Calon Ketua Organisasi)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `candidates`;
CREATE TABLE `candidates` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `election_id` INT UNSIGNED NOT NULL,
    `candidate_number` INT UNSIGNED NOT NULL COMMENT 'Nomor Urut Calon',
    `name` VARCHAR(150) NOT NULL COMMENT 'Nama Lengkap Calon Ketua',
    `nim` VARCHAR(30) NOT NULL COMMENT 'NIM Calon Ketua',
    `jenjang` VARCHAR(20) NOT NULL DEFAULT 'S1',
    `program_studi` VARCHAR(120) NOT NULL,
    `tagline` VARCHAR(255) NULL,
    `photo_path` VARCHAR(255) NULL,
    `vision` TEXT NOT NULL COMMENT 'Visi Calon',
    `mission` TEXT NOT NULL COMMENT 'Misi Calon',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_election_candidate_number` (`election_id`, `candidate_number`),
    CONSTRAINT `fk_candidates_election` FOREIGN KEY (`election_id`) REFERENCES `elections` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5. Tabel voter_attendances (Daftar Hadir Pemilih - Penjamin 1 Mahasiswa 1 Suara)
-- Mencatat SIAPA yang sudah memilih dan KAPAN, TANPA menyimpan candidate_id (Secret Ballot)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `voter_attendances`;
CREATE TABLE `voter_attendances` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `election_id` INT UNSIGNED NOT NULL,
    `student_id` INT UNSIGNED NOT NULL,
    `receipt_code` VARCHAR(64) NOT NULL UNIQUE COMMENT 'Kode bukti tanda terima suara digital',
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `voted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_voter_once_per_election` (`election_id`, `student_id`),
    CONSTRAINT `fk_attendance_election` FOREIGN KEY (`election_id`) REFERENCES `elections` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_attendance_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 6. Tabel votes (Kotak Suara Anonim - Secret Ballot)
-- Hanya menyimpan suara untuk kandidat tanpa identitas student_id
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `votes`;
CREATE TABLE `votes` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `election_id` INT UNSIGNED NOT NULL,
    `candidate_id` INT UNSIGNED NOT NULL,
    `cast_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_votes_election_candidate` (`election_id`, `candidate_id`),
    CONSTRAINT `fk_votes_election` FOREIGN KEY (`election_id`) REFERENCES `elections` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_votes_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 7. Tabel import_logs (Riwayat Import Dataset CSV Mahasiswa)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `import_logs`;
CREATE TABLE `import_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `filename` VARCHAR(255) NOT NULL,
    `total_rows` INT UNSIGNED NOT NULL DEFAULT 0,
    `inserted_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `updated_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `failed_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `error_details` LONGTEXT NULL COMMENT 'JSON array detail baris yang gagal validasi',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_import_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 8. Tabel audit_logs (Jejak Audit Aktivitas Administrasi & Keamanan)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NULL,
    `actor_name` VARCHAR(150) NOT NULL,
    `actor_role` VARCHAR(30) NOT NULL,
    `action` VARCHAR(80) NOT NULL,
    `entity_type` VARCHAR(50) NULL,
    `entity_id` INT UNSIGNED NULL,
    `description` TEXT NOT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_audit_action` (`action`),
    INDEX `idx_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

