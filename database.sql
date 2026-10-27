-- ==========================================================
-- PRANA HOMEO CARE — MYSQL DATABASE SCHEMA
-- Clinical Case Taking, Repertory, EHR & Pharmacy System
-- ==========================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. CLINICAL USERS / DOCTORS TABLE
CREATE TABLE IF NOT EXISTS `prana_users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL,
  `email` VARCHAR(191) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(191) NOT NULL DEFAULT 'Dr. G. Srinivasan M.D(Hom)',
  `role` VARCHAR(50) NOT NULL DEFAULT 'doctor',
  `clinic_branch` VARCHAR(100) NOT NULL DEFAULT 'KATTUPAKKAM',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. PATIENT MASTER DIRECTORY TABLE
CREATE TABLE IF NOT EXISTS `prana_patients` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `patient_uid` VARCHAR(64) NOT NULL UNIQUE,
  `reg_no` VARCHAR(50) NOT NULL,
  `clinic_branch` VARCHAR(100) NOT NULL DEFAULT 'KATTUPAKKAM',
  `name` VARCHAR(191) NOT NULL,
  `age` VARCHAR(20) DEFAULT '',
  `weight` VARCHAR(20) DEFAULT '',
  `gender` VARCHAR(10) DEFAULT 'M',
  `phone` VARCHAR(50) DEFAULT '',
  `whatsapp` VARCHAR(50) DEFAULT '',
  `address` TEXT DEFAULT NULL,
  `complaints` TEXT DEFAULT NULL,
  `history` TEXT DEFAULT NULL,
  `reg_date` DATE DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_reg_no` (`reg_no`),
  INDEX `idx_phone` (`phone`),
  INDEX `idx_name` (`name`),
  INDEX `idx_branch` (`clinic_branch`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. CLINICAL VISITS & CASE TAKING TABLE
CREATE TABLE IF NOT EXISTS `prana_visits` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `visit_uid` VARCHAR(64) NOT NULL UNIQUE,
  `patient_id` VARCHAR(64) NOT NULL,
  `clinic_branch` VARCHAR(100) NOT NULL DEFAULT 'KATTUPAKKAM',
  `visit_date` DATE NOT NULL,
  `complaints` TEXT DEFAULT NULL,
  `mind_rubrics` TEXT DEFAULT NULL,
  `generals` TEXT DEFAULT NULL,
  `diagnosis` TEXT DEFAULT NULL,
  `observations` TEXT DEFAULT NULL,
  `prescription_data` LONGTEXT DEFAULT NULL,
  `images_data` LONGTEXT DEFAULT NULL,
  `payment_amount` DECIMAL(10,2) DEFAULT 0.00,
  `payment_mode` VARCHAR(50) DEFAULT 'Cash',
  `remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_patient` (`patient_id`),
  INDEX `idx_date` (`visit_date`),
  INDEX `idx_branch` (`clinic_branch`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. PHARMACY INVENTORY TABLE
CREATE TABLE IF NOT EXISTS `prana_pharmacy` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `medicine_uid` VARCHAR(64) NOT NULL UNIQUE,
  `clinic_branch` VARCHAR(100) NOT NULL DEFAULT 'KATTUPAKKAM',
  `name` VARCHAR(191) NOT NULL,
  `potency` VARCHAR(50) NOT NULL DEFAULT '30C',
  `form` VARCHAR(50) NOT NULL DEFAULT 'Dilution',
  `batch_no` VARCHAR(100) DEFAULT '',
  `quantity` INT DEFAULT 0,
  `expiry_date` VARCHAR(50) DEFAULT '',
  `reorder_level` INT DEFAULT 10,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_med_name` (`name`),
  INDEX `idx_potency` (`potency`),
  INDEX `idx_branch` (`clinic_branch`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. CLINICAL PHOTO & DOCUMENT ATTACHMENTS
CREATE TABLE IF NOT EXISTS `prana_attachments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `patient_id` VARCHAR(64) NOT NULL,
  `visit_id` VARCHAR(64) DEFAULT '',
  `file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_size` INT DEFAULT 0,
  `mime_type` VARCHAR(100) DEFAULT '',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_patient_attach` (`patient_id`),
  INDEX `idx_visit_attach` (`visit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- DEFAULT DOCTOR ACCOUNT:
-- Email: clinic@email.com / Password: password123 (or demo123)
-- Hash generated using standard password_hash('password123', PASSWORD_BCRYPT)
INSERT IGNORE INTO `prana_users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role`, `clinic_branch`)
VALUES (1, 'doctor', 'clinic@email.com', '$2y$10$w0/5T1bUcmH4G8k4V70YyO3Yp4Ksqk6h7wK/b0Xn3VbKsy0w6wXre', 'Dr. G. Srinivasan M.D(Hom)', 'doctor', 'KATTUPAKKAM');

SET FOREIGN_KEY_CHECKS = 1;


-- 6. APPOINTMENT BOOKINGS & ONLINE SLOTS TABLE
CREATE TABLE IF NOT EXISTS `prana_appointments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(191) NOT NULL,
  `age` VARCHAR(20) DEFAULT '',
  `phone` VARCHAR(50) NOT NULL,
  `email` VARCHAR(191) DEFAULT '',
  `appointment_date` DATE NOT NULL,
  `appointment_time` VARCHAR(50) DEFAULT '10:00 AM',
  `mode` VARCHAR(100) DEFAULT 'In-Clinic',
  `concern` VARCHAR(191) DEFAULT '',
  `message` TEXT DEFAULT NULL,
  `status` VARCHAR(50) DEFAULT 'confirmed',
  `meet_link` VARCHAR(255) DEFAULT '',
  `calendar_link` TEXT DEFAULT NULL,
  `reschedule_token` VARCHAR(64) DEFAULT '',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_appt_date` (`appointment_date`),
  INDEX `idx_appt_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. CLINIC HOLIDAYS TABLE
CREATE TABLE IF NOT EXISTS `prana_holidays` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `holiday_date` DATE NOT NULL UNIQUE,
  `title` VARCHAR(191) NOT NULL,
  `scope` VARCHAR(50) DEFAULT 'ALL_DAY',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. DOCTOR TIMESLOT CONFIGURATION TABLE
CREATE TABLE IF NOT EXISTS `prana_slot_config` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `slot_time` VARCHAR(50) NOT NULL UNIQUE,
  `online_enabled` TINYINT(1) DEFAULT 1,
  `offline_enabled` TINYINT(1) DEFAULT 1,
  `is_active` TINYINT(1) DEFAULT 1,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. E-PRESCRIPTIONS TABLE
CREATE TABLE IF NOT EXISTS `prana_prescriptions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `rx_uid` VARCHAR(64) NOT NULL UNIQUE,
  `patient_id` VARCHAR(64) DEFAULT '',
  `clinic_branch` VARCHAR(100) NOT NULL DEFAULT 'KATTUPAKKAM',
  `patient_name` VARCHAR(191) NOT NULL,
  `age` VARCHAR(20) DEFAULT '',
  `gender` VARCHAR(10) DEFAULT 'M',
  `rx_date` DATE NOT NULL,
  `medicines_data` LONGTEXT NOT NULL,
  `instructions` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_rx_patient` (`patient_id`),
  INDEX `idx_rx_date` (`rx_date`),
  INDEX `idx_rx_branch` (`clinic_branch`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
