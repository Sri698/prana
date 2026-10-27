<?php
/**
 * PRANA HOMEO CARE — PATIENTS API ENDPOINT
 */

require_once __DIR__ . '/db.php';

$pdo = getDbConnection();
$method = $_SERVER['REQUEST_METHOD'];

// 1. GET: LIST / SEARCH PATIENTS
if ($method === 'GET') {
    $branch = $_GET['clinic'] ?? 'KATTUPAKKAM';
    $search = trim($_GET['search'] ?? '');
    $sort   = $_GET['sort'] ?? 'high-low';

    $sql = "SELECT * FROM prana_patients WHERE clinic_branch = :branch";
    $params = [':branch' => $branch];

    if (!empty($search)) {
        $sql .= " AND (name LIKE :search OR reg_no LIKE :search OR phone LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }

    if ($sort === 'low-high') {
        $sql .= " ORDER BY CAST(reg_no AS UNSIGNED) ASC, id ASC";
    } else {
        $sql .= " ORDER BY CAST(reg_no AS UNSIGNED) DESC, id DESC";
    }

    $sql .= " LIMIT 500";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $patients = $stmt->fetchAll();

    // Map to exact frontend format
    $output = array_map(function($p) {
        return [
            'id'       => $p['patient_uid'],
            'regNo'    => $p['reg_no'],
            'name'     => $p['name'],
            'age'      => $p['age'],
            'weight'   => $p['weight'],
            'gender'   => $p['gender'],
            'phone'    => $p['phone'],
            'whatsapp' => $p['whatsapp'],
            'address'  => $p['address'],
            'complaints' => $p['complaints'],
            'history'  => $p['history'],
            'regDate'  => $p['reg_date'] ?? substr($p['created_at'], 0, 10),
            'branch'   => $p['clinic_branch']
        ];
    }, $patients);

    sendResponse(['status' => 'success', 'data' => $output]);
}

// 2. POST: CREATE OR UPDATE PATIENT
if ($method === 'POST') {
    $data = getJsonInput();
    $branch = $data['branch'] ?? $data['clinic'] ?? 'KATTUPAKKAM';
    $name   = trim($data['name'] ?? '');
    $regNo  = trim($data['regNo'] ?? '');
    $uid    = $data['id'] ?? ('p_' . bin2hex(random_bytes(8)));

    if (empty($name) || empty($regNo)) {
        sendResponse(['status' => 'error', 'message' => 'Patient Name and Registration ID are required'], 400);
    }

    // Check duplicate Reg No within the same branch (excluding current patient if updating)
    $dupStmt = $pdo->prepare("SELECT id FROM prana_patients WHERE reg_no = ? AND clinic_branch = ? AND patient_uid != ? LIMIT 1");
    $dupStmt->execute([$regNo, $branch, $uid]);
    if ($dupStmt->fetch()) {
        sendResponse([
            'status' => 'error',
            'code'   => 'DUPLICATE_REG_NO',
            'message' => "Registration ID '{$regNo}' already exists in this clinic branch."
        ], 409);
    }

    try {
        $complaintsVal = is_array($data['complaints'] ?? null) ? json_encode($data['complaints']) : ($data['complaints'] ?? '');
        $historyVal = is_array($data['history'] ?? null) ? json_encode($data['history']) : ($data['history'] ?? '');

        // Universal Upsert: Check if record exists first
        $checkStmt = $pdo->prepare("SELECT id FROM prana_patients WHERE patient_uid = ? LIMIT 1");
        $checkStmt->execute([$uid]);
        $exists = $checkStmt->fetch();

        if ($exists) {
            $sql = "UPDATE prana_patients SET
                        reg_no = :reg_no,
                        clinic_branch = :branch,
                        name = :name,
                        age = :age,
                        weight = :weight,
                        gender = :gender,
                        phone = :phone,
                        whatsapp = :whatsapp,
                        address = :address,
                        complaints = :complaints,
                        history = :history
                    WHERE patient_uid = :uid";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':uid'        => $uid,
                ':reg_no'     => $regNo,
                ':branch'     => $branch,
                ':name'       => $name,
                ':age'        => $data['age'] ?? '',
                ':weight'     => $data['weight'] ?? '',
                ':gender'     => $data['gender'] ?? 'M',
                ':phone'      => $data['phone'] ?? '',
                ':whatsapp'   => $data['whatsapp'] ?? ($data['phone'] ?? ''),
                ':address'    => $data['address'] ?? '',
                ':complaints' => $complaintsVal,
                ':history'    => $historyVal
            ]);
        } else {
            $sql = "INSERT INTO prana_patients (
                        patient_uid, reg_no, clinic_branch, name, age, weight, gender, phone, whatsapp, address, complaints, history, reg_date
                    ) VALUES (
                        :uid, :reg_no, :branch, :name, :age, :weight, :gender, :phone, :whatsapp, :address, :complaints, :history, :reg_date
                    )";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':uid'        => $uid,
                ':reg_no'     => $regNo,
                ':branch'     => $branch,
                ':name'       => $name,
                ':age'        => $data['age'] ?? '',
                ':weight'     => $data['weight'] ?? '',
                ':gender'     => $data['gender'] ?? 'M',
                ':phone'      => $data['phone'] ?? '',
                ':whatsapp'   => $data['whatsapp'] ?? ($data['phone'] ?? ''),
                ':address'    => $data['address'] ?? '',
                ':complaints' => $complaintsVal,
                ':history'    => $historyVal,
                ':reg_date'   => $data['regDate'] ?? date('Y-m-d')
            ]);
        }

        sendResponse([
            'status'  => 'success',
            'message' => 'Patient saved successfully',
            'id'      => $uid
        ]);
    } catch (Throwable $e) {
        sendResponse([
            'status'  => 'error',
            'message' => 'Failed to save patient: ' . $e->getMessage()
        ], 500);
    }
}

// 3. DELETE: REMOVE PATIENT
if ($method === 'DELETE') {
    $uid = $_GET['id'] ?? '';
    if (empty($uid)) {
        sendResponse(['status' => 'error', 'message' => 'Patient ID required'], 400);
    }

    // Remove patient, visits, and prescriptions
    $stmt1 = $pdo->prepare("DELETE FROM prana_patients WHERE patient_uid = ? OR id = ?");
    $stmt1->execute([$uid, $uid]);

    $stmt2 = $pdo->prepare("DELETE FROM prana_visits WHERE patient_id = ?");
    $stmt2->execute([$uid]);

    try {
        $stmt3 = $pdo->prepare("DELETE FROM prana_prescriptions WHERE patient_id = ?");
        $stmt3->execute([$uid]);
    } catch (Exception $e) {}

    sendResponse(['status' => 'success', 'message' => 'Patient and visit records removed successfully']);
}

