<?php
/**
 * PRANA HOMEO CARE — PHARMACY INVENTORY API
 */

require_once __DIR__ . '/db.php';

$pdo = getDbConnection();
$method = $_SERVER['REQUEST_METHOD'];

// 1. GET: FETCH MEDICINE STOCK
if ($method === 'GET') {
    $branch = $_GET['clinic'] ?? 'KATTUPAKKAM';
    $search = trim($_GET['search'] ?? '');

    $sql = "SELECT * FROM prana_pharmacy WHERE clinic_branch = :branch";
    $params = [':branch' => $branch];

    if (!empty($search)) {
        $sql .= " AND (name LIKE :search OR potency LIKE :search OR form LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }

    $sql .= " ORDER BY name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $meds = $stmt->fetchAll();

    $output = array_map(function($m) {
        $reorder = intval($m['reorder_level']);
        $exp = $m['expiry_date'] ?? '';
        return [
            'id'           => $m['medicine_uid'],
            'name'         => $m['name'],
            'potency'      => $m['potency'],
            'form'         => $m['form'],
            'batchNo'      => $m['batch_no'],
            'quantity'     => intval($m['quantity']),
            'minStock'     => $reorder,
            'reorderLevel' => $reorder,
            'expiry'       => $exp,
            'expiryDate'   => $exp,
            'branch'       => $m['clinic_branch']
        ];
    }, $meds);

    sendResponse(['status' => 'success', 'data' => $output]);
}

// 2. POST: ADD OR UPDATE MEDICINE
if ($method === 'POST') {
    $data = getJsonInput();
    $branch = $data['branch'] ?? $data['clinic'] ?? 'KATTUPAKKAM';
    $name   = trim($data['name'] ?? '');
    $uid    = $data['id'] ?? ('m_' . bin2hex(random_bytes(8)));

    if (empty($name)) {
        sendResponse(['status' => 'error', 'message' => 'Medicine Name is required'], 400);
    }

    $minStockVal = intval($data['minStock'] ?? $data['reorderLevel'] ?? $data['reorder_level'] ?? 10);
    $expiryVal   = trim($data['expiry'] ?? $data['expiryDate'] ?? $data['expiry_date'] ?? '');

    try {
        $checkStmt = $pdo->prepare("SELECT id FROM prana_pharmacy WHERE medicine_uid = ? LIMIT 1");
        $checkStmt->execute([$uid]);
        $exists = $checkStmt->fetch();

        if ($exists) {
            $sql = "UPDATE prana_pharmacy SET
                        clinic_branch = :branch,
                        name = :name,
                        potency = :potency,
                        form = :form,
                        batch_no = :batch_no,
                        quantity = :quantity,
                        expiry_date = :expiry_date,
                        reorder_level = :reorder_level
                    WHERE medicine_uid = :uid";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':uid'           => $uid,
                ':branch'        => $branch,
                ':name'          => $name,
                ':potency'       => $data['potency'] ?? '30C',
                ':form'          => $data['form'] ?? 'Dilution',
                ':batch_no'      => $data['batchNo'] ?? $data['batch_no'] ?? '',
                ':quantity'      => intval($data['quantity'] ?? 0),
                ':expiry_date'   => $expiryVal,
                ':reorder_level' => $minStockVal
            ]);
        } else {
            $sql = "INSERT INTO prana_pharmacy (
                        medicine_uid, clinic_branch, name, potency, form, batch_no, quantity, expiry_date, reorder_level
                    ) VALUES (
                        :uid, :branch, :name, :potency, :form, :batch_no, :quantity, :expiry_date, :reorder_level
                    )";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':uid'           => $uid,
                ':branch'        => $branch,
                ':name'          => $name,
                ':potency'       => $data['potency'] ?? '30C',
                ':form'          => $data['form'] ?? 'Dilution',
                ':batch_no'      => $data['batchNo'] ?? $data['batch_no'] ?? '',
                ':quantity'      => intval($data['quantity'] ?? 0),
                ':expiry_date'   => $expiryVal,
                ':reorder_level' => $minStockVal
            ]);
        }

        sendResponse([
            'status'  => 'success',
            'message' => 'Medicine saved to pharmacy inventory',
            'id'      => $uid
        ]);
    } catch (Throwable $e) {
        sendResponse([
            'status'  => 'error',
            'message' => 'Failed to save medicine: ' . $e->getMessage()
        ], 500);
    }
}

// 3. DELETE: REMOVE MEDICINE
if ($method === 'DELETE') {
    $uid = $_GET['id'] ?? '';
    if (empty($uid)) {
        sendResponse(['status' => 'error', 'message' => 'Medicine ID is required'], 400);
    }

    $stmt = $pdo->prepare("DELETE FROM prana_pharmacy WHERE medicine_uid = ?");
    $stmt->execute([$uid]);

    sendResponse(['status' => 'success', 'message' => 'Medicine removed from stock']);
}
