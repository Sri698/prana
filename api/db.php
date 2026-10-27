<?php
/**
 * PRANA HOMEO CARE — DATABASE CONNECTION HELPER (PDO)
 */

require_once __DIR__ . '/config.php';

// Enable CORS and JSON Response headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=UTF-8');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

function getDbConnection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

        // Ensure all clinical tables exist automatically on cPanel
        static $tablesCreated = false;
        if (!$tablesCreated) {
            $pdo->exec("
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
                    INDEX `idx_branch` (`clinic_branch`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
                    INDEX `idx_branch` (`clinic_branch`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `prana_holidays` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `holiday_date` DATE NOT NULL UNIQUE,
                    `title` VARCHAR(191) NOT NULL,
                    `scope` VARCHAR(50) DEFAULT 'ALL_DAY',
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `prana_slot_config` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `slot_time` VARCHAR(50) NOT NULL UNIQUE,
                    `online_enabled` TINYINT(1) DEFAULT 1,
                    `offline_enabled` TINYINT(1) DEFAULT 1,
                    `is_active` TINYINT(1) DEFAULT 1,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
            ");
            $tablesCreated = true;
        }

        return $pdo;
    } catch (PDOException $e) {
        // Fallback to SQLite if MySQL is not configured
        $sqlitePath = __DIR__ . '/prana_fallback.sqlite';
        $pdo = new PDO("sqlite:" . $sqlitePath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        static $sqliteCreated = false;
        if (!$sqliteCreated) {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS prana_patients (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    patient_uid TEXT NOT NULL UNIQUE,
                    reg_no TEXT NOT NULL,
                    clinic_branch TEXT NOT NULL DEFAULT 'KATTUPAKKAM',
                    name TEXT NOT NULL,
                    age TEXT DEFAULT '',
                    weight TEXT DEFAULT '',
                    gender TEXT DEFAULT 'M',
                    phone TEXT DEFAULT '',
                    whatsapp TEXT DEFAULT '',
                    address TEXT DEFAULT NULL,
                    complaints TEXT DEFAULT NULL,
                    history TEXT DEFAULT NULL,
                    reg_date TEXT DEFAULT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );
                CREATE TABLE IF NOT EXISTS prana_visits (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    visit_uid TEXT NOT NULL UNIQUE,
                    patient_id TEXT NOT NULL,
                    clinic_branch TEXT NOT NULL DEFAULT 'KATTUPAKKAM',
                    visit_date TEXT NOT NULL,
                    complaints TEXT DEFAULT NULL,
                    mind_rubrics TEXT DEFAULT NULL,
                    generals TEXT DEFAULT NULL,
                    diagnosis TEXT DEFAULT NULL,
                    observations TEXT DEFAULT NULL,
                    prescription_data TEXT DEFAULT NULL,
                    images_data TEXT DEFAULT NULL,
                    payment_amount DECIMAL(10,2) DEFAULT 0.00,
                    payment_mode TEXT DEFAULT 'Cash',
                    remarks TEXT DEFAULT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );
                CREATE TABLE IF NOT EXISTS prana_pharmacy (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    medicine_uid TEXT NOT NULL UNIQUE,
                    clinic_branch TEXT NOT NULL DEFAULT 'KATTUPAKKAM',
                    name TEXT NOT NULL,
                    potency TEXT NOT NULL DEFAULT '30C',
                    form TEXT NOT NULL DEFAULT 'Dilution',
                    batch_no TEXT DEFAULT '',
                    quantity INTEGER DEFAULT 0,
                    expiry_date TEXT DEFAULT '',
                    reorder_level INTEGER DEFAULT 10,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );
                CREATE TABLE IF NOT EXISTS prana_holidays (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    holiday_date TEXT NOT NULL UNIQUE,
                    title TEXT NOT NULL,
                    scope TEXT DEFAULT 'ALL_DAY',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );
                CREATE TABLE IF NOT EXISTS prana_slot_config (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    slot_time TEXT NOT NULL UNIQUE,
                    online_enabled INTEGER DEFAULT 1,
                    offline_enabled INTEGER DEFAULT 1,
                    is_active INTEGER DEFAULT 1,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );
                CREATE TABLE IF NOT EXISTS prana_appointments (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    age TEXT DEFAULT '',
                    phone TEXT NOT NULL,
                    email TEXT DEFAULT '',
                    appointment_date TEXT NOT NULL,
                    appointment_time TEXT DEFAULT '10:00 AM',
                    mode TEXT DEFAULT 'In-Clinic',
                    concern TEXT DEFAULT '',
                    message TEXT DEFAULT NULL,
                    status TEXT DEFAULT 'confirmed',
                    meet_link TEXT DEFAULT '',
                    calendar_link TEXT DEFAULT NULL,
                    reschedule_token TEXT DEFAULT '',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );
                CREATE TABLE IF NOT EXISTS prana_prescriptions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    rx_uid TEXT NOT NULL UNIQUE,
                    patient_id TEXT DEFAULT '',
                    clinic_branch TEXT NOT NULL DEFAULT 'KATTUPAKKAM',
                    patient_name TEXT NOT NULL,
                    age TEXT DEFAULT '',
                    gender TEXT DEFAULT 'M',
                    rx_date TEXT NOT NULL,
                    medicines_data TEXT NOT NULL,
                    instructions TEXT DEFAULT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );
            ");
            $sqliteCreated = true;
        }
        return $pdo;
    }
}

function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

function getJsonInput() {
    $raw = file_get_contents('php://input');
    if (empty($raw)) {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}
