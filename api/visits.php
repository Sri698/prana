<?php
/**
 * PRANA HOMEO CARE — CLINICAL VISITS & CASE SHEETS API
 */

require_once __DIR__ . '/db.php';

$pdo = getDbConnection();
$method = $_SERVER['REQUEST_METHOD'];

// 1. GET: FETCH VISITS FOR A PATIENT
if ($method === 'GET') {
    $patientId = $_GET['patient_id'] ?? '';
    if (empty($patientId)) {
        sendResponse(['status' => 'error', 'message' => 'patient_id parameter is required'], 400);
    }

    $stmt = $pdo->prepare("SELECT * FROM prana_visits WHERE patient_id = ? ORDER BY visit_date DESC, id DESC");
    $stmt->execute([$patientId]);
    $visits = $stmt->fetchAll();

    $output = array_map(function($v) {
        $rx = json_decode($v['prescription_data'], true) ?? [];
        return [
            'id'           => $v['visit_uid'],
            'visit_uid'    => $v['visit_uid'],
            'patientId'    => $v['patient_id'],
            'clinic'       => $v['clinic_branch'],
            'date'         => $v['visit_date'],
            'complaints'   => json_decode($v['complaints'], true) ?? ($v['complaints'] ? explode("\n", $v['complaints']) : []),
            'mindRubrics'  => json_decode($v['mind_rubrics'], true) ?? [],
            'generals'     => json_decode($v['generals'], true) ?? [],
            'diagnosis'    => $v['diagnosis'] ?? '',
            'observations' => $v['observations'] ?? '',
            'medicines'    => $rx,
            'mainRx'       => $rx,
            'images'       => json_decode($v['images_data'], true) ?? [],
            'money'        => $v['payment_amount'],
            'moneyReceived'=> $v['payment_amount'],
            'paymentMode'  => $v['payment_mode'],
            'remarks'      => $v['remarks'],
            'createdAt'    => $v['created_at']
        ];
    }, $visits);

    sendResponse(['status' => 'success', 'data' => $output]);
}

// 2. POST: SAVE CONSULTATION VISIT
if ($method === 'POST') {
    $data = getJsonInput();
    $patientId = trim($data['patientId'] ?? $data['patient_id'] ?? '');
    $branch    = $data['clinic'] ?? $data['branch'] ?? 'KATTUPAKKAM';
    $visitDate = $data['date'] ?? date('Y-m-d');
    $uid       = $data['id'] ?? ('v_' . bin2hex(random_bytes(8)));

    if (empty($patientId)) {
        sendResponse(['status' => 'error', 'message' => 'Patient ID is required'], 400);
    }

    $complaints  = json_encode($data['complaints'] ?? []);
    $mindRubrics = json_encode($data['mindRubrics'] ?? $data['mind_rubrics'] ?? []);
    $generals    = json_encode($data['generals'] ?? []);
    $medicines   = json_encode($data['mainRx'] ?? $data['medicines'] ?? $data['prescription'] ?? []);
    $images      = json_encode($data['images'] ?? []);

    try {
        // Universal Upsert: Check if visit exists first
        $checkStmt = $pdo->prepare("SELECT id, visit_uid FROM prana_visits WHERE visit_uid = :uid OR (id = :uid_int AND :uid_int > 0) LIMIT 1");
        $checkStmt->execute([
            ':uid'     => $uid,
            ':uid_int' => is_numeric($uid) ? intval($uid) : 0
        ]);
        $exists = $checkStmt->fetch();
        if ($exists && !empty($exists['visit_uid'])) {
            $uid = $exists['visit_uid'];
        }

        if ($exists) {
            $sql = "UPDATE prana_visits SET
                        patient_id = :patient_id,
                        clinic_branch = :branch,
                        visit_date = :visit_date,
                        complaints = :complaints,
                        mind_rubrics = :mind_rubrics,
                        generals = :generals,
                        diagnosis = :diagnosis,
                        observations = :observations,
                        prescription_data = :prescription,
                        images_data = :images,
                        payment_amount = :payment_amount,
                        payment_mode = :payment_mode,
                        remarks = :remarks
                    WHERE visit_uid = :uid";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':uid'            => $uid,
                ':patient_id'     => $patientId,
                ':branch'         => $branch,
                ':visit_date'     => $visitDate,
                ':complaints'     => $complaints,
                ':mind_rubrics'   => $mindRubrics,
                ':generals'       => $generals,
                ':diagnosis'      => $data['diagnosis'] ?? '',
                ':observations'   => $data['observations'] ?? '',
                ':prescription'   => $medicines,
                ':images'         => $images,
                ':payment_amount' => floatval($data['moneyReceived'] ?? $data['money'] ?? $data['payment_amount'] ?? 0),
                ':payment_mode'   => $data['paymentMode'] ?? $data['payment_mode'] ?? 'Cash',
                ':remarks'        => $data['remarks'] ?? ''
            ]);
        } else {
            $sql = "INSERT INTO prana_visits (
                        visit_uid, patient_id, clinic_branch, visit_date, complaints,
                        mind_rubrics, generals, diagnosis, observations, prescription_data,
                        images_data, payment_amount, payment_mode, remarks
                    ) VALUES (
                        :uid, :patient_id, :branch, :visit_date, :complaints,
                        :mind_rubrics, :generals, :diagnosis, :observations, :prescription,
                        :images, :payment_amount, :payment_mode, :remarks
                    )";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':uid'            => $uid,
                ':patient_id'     => $patientId,
                ':branch'         => $branch,
                ':visit_date'     => $visitDate,
                ':complaints'     => $complaints,
                ':mind_rubrics'   => $mindRubrics,
                ':generals'       => $generals,
                ':diagnosis'      => $data['diagnosis'] ?? '',
                ':observations'   => $data['observations'] ?? '',
                ':prescription'   => $medicines,
                ':images'         => $images,
                ':payment_amount' => floatval($data['moneyReceived'] ?? $data['money'] ?? $data['payment_amount'] ?? 0),
                ':payment_mode'   => $data['paymentMode'] ?? $data['payment_mode'] ?? 'Cash',
                ':remarks'        => $data['remarks'] ?? ''
            ]);
        }

        sendResponse([
            'status'  => 'success',
            'message' => 'Consultation visit recorded successfully',
            'id'      => $uid
        ]);
    } catch (Throwable $e) {
        sendResponse([
            'status'  => 'error',
            'message' => 'Failed to record visit: ' . $e->getMessage()
        ], 500);
    }
}

// 3. DELETE: REMOVE A VISIT
if ($method === 'DELETE') {
    $uid = $_GET['id'] ?? '';
    if (empty($uid)) {
        sendResponse(['status' => 'error', 'message' => 'Visit ID is required'], 400);
    }

    $stmt = $pdo->prepare("DELETE FROM prana_visits WHERE visit_uid = ?");
    $stmt->execute([$uid]);

    sendResponse(['status' => 'success', 'message' => 'Visit deleted successfully']);
}
