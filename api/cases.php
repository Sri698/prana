<?php
/**
 * PRANA HOMEO CARE — CLINICAL CASES API ENDPOINT
 * Handles incoming patient case submissions, doctor retrieval, updates, followups & CRUD.
 * Compatible with MySQL and SQLite (prana_fallback.sqlite).
 */

require_once __DIR__ . '/db.php';

$pdo = getDbConnection();
$method = $_SERVER['REQUEST_METHOD'];

// Ensure prana_cases and prana_case_followups tables exist
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `prana_cases` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `patient_name` VARCHAR(191) NOT NULL,
            `phone` VARCHAR(50) DEFAULT '',
            `diagnosis` VARCHAR(255) DEFAULT '',
            `language` VARCHAR(20) DEFAULT 'en',
            `answers_json` LONGTEXT NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_case_patient` (`patient_name`),
            INDEX `idx_case_phone` (`phone`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `prana_case_followups` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `case_id` INT NOT NULL,
            `visit_date` DATE NOT NULL,
            `notes` TEXT DEFAULT NULL,
            `prescription` TEXT DEFAULT NULL,
            `advice` TEXT DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_followup_case` (`case_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
} catch (Exception $e) {
    // SQLite fallback schema
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS prana_cases (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                patient_name TEXT NOT NULL,
                phone TEXT DEFAULT '',
                diagnosis TEXT DEFAULT '',
                language TEXT DEFAULT 'en',
                answers_json TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS prana_case_followups (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                case_id INTEGER NOT NULL,
                visit_date TEXT NOT NULL,
                notes TEXT DEFAULT '',
                prescription TEXT DEFAULT '',
                advice TEXT DEFAULT '',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");
    } catch (Exception $e2) {
        // Ignore if table exists
    }
}

// 1. GET: LIST CASES OR FETCH SINGLE CASE WITH FOLLOWUPS
if ($method === 'GET') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $search = trim($_GET['search'] ?? $_GET['q'] ?? '');

    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM prana_cases WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $case = $stmt->fetch();
        if (!$case) {
            sendResponse(['status' => 'error', 'message' => 'Case record not found.'], 404);
        }
        $case['answers'] = json_decode($case['answers_json'], true) ?: [];

        // Fetch followups
        try {
            $fStmt = $pdo->prepare("SELECT * FROM prana_case_followups WHERE case_id = ? ORDER BY visit_date DESC, id DESC");
            $fStmt->execute([$id]);
            $case['followups'] = $fStmt->fetchAll() ?: [];
        } catch (Exception $fe) {
            $case['followups'] = [];
        }

        sendResponse(['status' => 'success', 'data' => $case]);
    } else {
        $sql = "SELECT id, patient_name, phone, diagnosis, language, created_at, updated_at FROM prana_cases";
        $params = [];
        if (!empty($search)) {
            $sql .= " WHERE patient_name LIKE :s OR phone LIKE :s OR diagnosis LIKE :s";
            $params[':s'] = '%' . $search . '%';
        }
        $sql .= " ORDER BY id DESC LIMIT 200";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $cases = $stmt->fetchAll() ?: [];
        sendResponse(['status' => 'success', 'data' => $cases]);
    }
}

// 2. POST: SUBMIT NEW PATIENT CASE OR ADD DOCTOR FOLLOWUP
if ($method === 'POST') {
    $data = getJsonInput();
    $action = $_GET['action'] ?? ($data['action'] ?? '');

    // 2a. Add doctor follow-up / prescription
    if ($action === 'add_followup') {
        $caseId = intval($data['case_id'] ?? 0);
        $visitDate = trim($data['visit_date'] ?? date('Y-m-d'));
        $notes = trim($data['notes'] ?? '');
        $prescription = trim($data['prescription'] ?? '');
        $advice = trim($data['advice'] ?? '');

        if ($caseId <= 0 || (empty($notes) && empty($prescription) && empty($advice))) {
            sendResponse(['status' => 'error', 'message' => 'Case ID and at least one clinical note/prescription required.'], 400);
        }

        try {
            $fStmt = $pdo->prepare("
                INSERT INTO prana_case_followups (case_id, visit_date, notes, prescription, advice)
                VALUES (?, ?, ?, ?, ?)
            ");
            $fStmt->execute([$caseId, $visitDate, $notes, $prescription, $advice]);
            $followupId = $pdo->lastInsertId();

            sendResponse([
                'status' => 'success',
                'ok' => true,
                'id' => (int)$followupId,
                'message' => 'Follow-up and prescription recorded successfully.'
            ], 201);
        } catch (Exception $e) {
            sendResponse(['status' => 'error', 'message' => 'Failed to save follow-up: ' . $e->getMessage()], 500);
        }
    }

    // 2b. Submit new patient case record
    if (!is_array($data) || empty($data['answers'])) {
        sendResponse(['status' => 'error', 'message' => 'Invalid case data payload.'], 400);
    }

    $answers = $data['answers'];
    $patientName = trim($answers['patientName'] ?? $answers['patient_name'] ?? $answers['Name /'] ?? $answers['s0f0'] ?? '');
    if (empty($patientName)) {
        sendResponse(['status' => 'error', 'message' => 'Patient name is required.'], 400);
    }

    $phone = trim($answers['phone'] ?? $answers['Ph.No ./'] ?? $answers['phone_number'] ?? $answers['s0f8'] ?? '');
    $diagnosis = trim($answers['diagnosis'] ?? $answers['Diagnosis /'] ?? '');
    $language = trim($data['language'] ?? 'both');
    $answersJson = json_encode($answers, JSON_UNESCAPED_UNICODE);

    try {
        $stmt = $pdo->prepare("
            INSERT INTO prana_cases (patient_name, phone, diagnosis, language, answers_json)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$patientName, $phone, $diagnosis, $language, $answersJson]);
        $caseId = $pdo->lastInsertId();

        // Also ensure patient is synced to prana_patients table for the Doctor Portal
        try {
            $age = trim($answers['age'] ?? $answers['Age /'] ?? '');
            $gender = trim($answers['gender'] ?? 'Other');
            $address = trim($answers['address'] ?? '');
            $complaints = trim($answers['chiefComplaint'] ?? '');
            $regNo = 'PR-' . str_pad($caseId, 4, '0', STR_PAD_LEFT);
            $uid = 'p_case_' . $caseId;

            $checkPatient = $pdo->prepare("SELECT id FROM prana_patients WHERE phone = ? AND name = ? LIMIT 1");
            $checkPatient->execute([$phone, $patientName]);
            if (!$checkPatient->fetch()) {
                $insPatient = $pdo->prepare("
                    INSERT INTO prana_patients (patient_uid, reg_no, clinic_branch, name, age, gender, phone, address, complaints, reg_date)
                    VALUES (?, ?, 'KATTUPAKKAM', ?, ?, ?, ?, ?, ?, CURRENT_DATE)
                ");
                $insPatient->execute([$uid, $regNo, $patientName, $age, substr($gender, 0, 1), $phone, $address, $complaints]);
            }
        } catch (Exception $pe) {
            // Patient sync is optional, non-blocking
        }

        sendResponse([
            'status' => 'success',
            'ok' => true,
            'id' => (int)$caseId,
            'message' => 'Clinical case record submitted successfully.'
        ], 201);

    } catch (Exception $e) {
        sendResponse(['status' => 'error', 'message' => 'Failed to save case record: ' . $e->getMessage()], 500);
    }
}

// 3. PUT: EDIT CASE (Doctor CRUD)
if ($method === 'PUT') {
    $data = getJsonInput();
    $id = isset($data['id']) ? intval($data['id']) : 0;
    if ($id <= 0 || empty($data['answers'])) {
        sendResponse(['status' => 'error', 'message' => 'Invalid case update payload.'], 400);
    }

    $answers = $data['answers'];
    $patientName = trim($answers['patientName'] ?? $data['patient_name'] ?? '');
    $phone = trim($answers['phone'] ?? $data['phone'] ?? '');
    $diagnosis = trim($answers['diagnosis'] ?? $data['diagnosis'] ?? '');
    $answersJson = json_encode($answers, JSON_UNESCAPED_UNICODE);

    try {
        $stmt = $pdo->prepare("
            UPDATE prana_cases 
            SET patient_name = ?, phone = ?, diagnosis = ?, answers_json = ? 
            WHERE id = ?
        ");
        $stmt->execute([$patientName, $phone, $diagnosis, $answersJson, $id]);
        sendResponse(['status' => 'success', 'ok' => true, 'message' => 'Case updated successfully.']);
    } catch (Exception $e) {
        sendResponse(['status' => 'error', 'message' => 'Failed to update case: ' . $e->getMessage()], 500);
    }
}

// 4. DELETE: REMOVE CASE (Doctor CRUD)
if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) {
        $data = getJsonInput();
        $id = intval($data['id'] ?? 0);
    }

    if ($id <= 0) {
        sendResponse(['status' => 'error', 'message' => 'Case ID required for deletion.'], 400);
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM prana_cases WHERE id = ?");
        $stmt->execute([$id]);

        // Also delete followups
        try {
            $fStmt = $pdo->prepare("DELETE FROM prana_case_followups WHERE case_id = ?");
            $fStmt->execute([$id]);
        } catch (Exception $fe) {}

        sendResponse(['status' => 'success', 'ok' => true, 'message' => 'Case record deleted successfully.']);
    } catch (Exception $e) {
        sendResponse(['status' => 'error', 'message' => 'Failed to delete case: ' . $e->getMessage()], 500);
    }
}

sendResponse(['status' => 'error', 'message' => 'Method not allowed.'], 405);
