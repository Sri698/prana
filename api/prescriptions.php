<?php
/**
 * PRANA HOMEO CARE — E-PRESCRIPTIONS REST API
 * Handles creation, listing, retrieval, and deletion of clinical prescriptions.
 */

require_once __DIR__ . '/db.php';

$pdo = getDbConnection();
$method = $_SERVER['REQUEST_METHOD'];

// 1. GET: FETCH PRESCRIPTIONS
if ($method === 'GET') {
    $branch    = $_GET['clinic'] ?? $_GET['branch'] ?? 'KATTUPAKKAM';
    $patientId = trim($_GET['patient_id'] ?? '');
    $search    = trim($_GET['search'] ?? '');

    $sql = "SELECT * FROM prana_prescriptions WHERE clinic_branch = :branch";
    $params = [':branch' => $branch];

    if (!empty($patientId)) {
        $sql .= " AND patient_id = :patient_id";
        $params[':patient_id'] = $patientId;
    }

    if (!empty($search)) {
        $sql .= " AND (patient_name LIKE :search OR medicines_data LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }

    $sql .= " ORDER BY rx_date DESC, id DESC LIMIT 200";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $output = array_map(function($r) {
        $meds = json_decode($r['medicines_data'], true);
        if (!is_array($meds)) $meds = [];
        return [
            'id'           => $r['rx_uid'],
            'patientId'    => $r['patient_id'],
            'patientName'  => $r['patient_name'],
            'age'          => $r['age'],
            'gender'       => $r['gender'],
            'date'         => $r['rx_date'],
            'medicines'    => $meds,
            'instructions' => $r['instructions'] ?? '',
            'branch'       => $r['clinic_branch'],
            'createdAt'    => $r['created_at']
        ];
    }, $rows);

    sendResponse(['status' => 'success', 'data' => $output]);
}

// 2. POST: CREATE OR UPDATE PRESCRIPTION
if ($method === 'POST') {
    $data = getJsonInput();
    $branch      = $data['branch'] ?? $data['clinic'] ?? 'KATTUPAKKAM';
    $patientName = trim($data['patientName'] ?? $data['patient_name'] ?? '');
    $patientId   = trim($data['patientId'] ?? $data['patient_id'] ?? '');
    $age         = trim($data['age'] ?? '');
    $gender      = trim($data['gender'] ?? 'M');
    $rxDate      = $data['date'] ?? $data['rx_date'] ?? date('Y-m-d');
    $uid         = strval($data['id'] ?? ('rx_' . bin2hex(random_bytes(8))));
    $instructions = trim($data['instructions'] ?? $data['notes'] ?? '');

    if (empty($patientName)) {
        sendResponse(['status' => 'error', 'message' => 'Patient Name is required'], 400);
    }

    $medsData = $data['medicines'] ?? [];
    if (!is_string($medsData)) {
        $medsData = json_encode($medsData, JSON_UNESCAPED_UNICODE);
    }

    try {
        $checkStmt = $pdo->prepare("SELECT id FROM prana_prescriptions WHERE rx_uid = ? LIMIT 1");
        $checkStmt->execute([$uid]);
        $exists = $checkStmt->fetch();

        if ($exists) {
            $sql = "UPDATE prana_prescriptions SET
                        patient_id = :patient_id,
                        clinic_branch = :branch,
                        patient_name = :patient_name,
                        age = :age,
                        gender = :gender,
                        rx_date = :rx_date,
                        medicines_data = :meds_data,
                        instructions = :instructions
                    WHERE rx_uid = :uid";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':uid'          => $uid,
                ':patient_id'   => $patientId,
                ':branch'         => $branch,
                ':patient_name' => $patientName,
                ':age'          => $age,
                ':gender'       => $gender,
                ':rx_date'      => $rxDate,
                ':meds_data'    => $medsData,
                ':instructions' => $instructions
            ]);
        } else {
            $sql = "INSERT INTO prana_prescriptions (
                        rx_uid, patient_id, clinic_branch, patient_name, age, gender, rx_date, medicines_data, instructions
                    ) VALUES (
                        :uid, :patient_id, :branch, :patient_name, :age, :gender, :rx_date, :meds_data, :instructions
                    )";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':uid'          => $uid,
                ':patient_id'   => $patientId,
                ':branch'         => $branch,
                ':patient_name' => $patientName,
                ':age'          => $age,
                ':gender'       => $gender,
                ':rx_date'      => $rxDate,
                ':meds_data'    => $medsData,
                ':instructions' => $instructions
            ]);
        }

        sendResponse([
            'status'  => 'success',
            'message' => 'Prescription saved successfully',
            'id'      => $uid
        ]);
    } catch (Throwable $e) {
        sendResponse([
            'status'  => 'error',
            'message' => 'Failed to save prescription: ' . $e->getMessage()
        ], 500);
    }
}

// 3. DELETE: REMOVE PRESCRIPTION
if ($method === 'DELETE') {
    $uid = $_GET['id'] ?? '';
    if (empty($uid)) {
        sendResponse(['status' => 'error', 'message' => 'Prescription ID is required'], 400);
    }

    $stmt = $pdo->prepare("DELETE FROM prana_prescriptions WHERE rx_uid = ?");
    $stmt->execute([$uid]);

    sendResponse(['status' => 'success', 'message' => 'Prescription removed successfully']);
}
