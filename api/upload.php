<?php
/**
 * PRANA HOMEO CARE — CLINICAL FILE & PHOTO UPLOAD API
 * Saves patient clinical photographs & test reports directly on your cPanel server.
 */

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(['status' => 'error', 'message' => 'POST method required for uploads'], 405);
}

// Check if upload directory exists
if (!is_dir(UPLOAD_DIR)) {
    if (!@mkdir(UPLOAD_DIR, 0755, true)) {
        sendResponse(['status' => 'error', 'message' => 'Upload directory does not exist and could not be created'], 500);
    }
}

// Support single or multiple files
$files = [];
if (!empty($_FILES['image'])) {
    $files[] = $_FILES['image'];
} elseif (!empty($_FILES['file'])) {
    $files[] = $_FILES['file'];
} elseif (!empty($_FILES['images'])) {
    // Multiple files
    if (is_array($_FILES['images']['name'])) {
        for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
            $files[] = [
                'name'     => $_FILES['images']['name'][$i],
                'type'     => $_FILES['images']['type'][$i],
                'tmp_name' => $_FILES['images']['tmp_name'][$i],
                'error'    => $_FILES['images']['error'][$i],
                'size'     => $_FILES['images']['size'][$i],
            ];
        }
    } else {
        $files[] = $_FILES['images'];
    }
}

// Support Base64 JSON upload fallback
$input = getJsonInput();
if (empty($files) && !empty($input['base64'])) {
    $base64Data = $input['base64'];
    $patientId  = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['patientId'] ?? 'general');
    $ext        = 'webp';

    if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
        $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
        $type = strtolower($type[1]);
        if (in_array($type, ALLOWED_EXTENSIONS)) {
            $ext = $type;
        }
    }

    $decoded = base64_decode($base64Data);
    if ($decoded === false) {
        sendResponse(['status' => 'error', 'message' => 'Invalid base64 payload'], 400);
    }

    $filename = 'clinical_' . $patientId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetPath = UPLOAD_DIR . $filename;

    if (file_put_contents($targetPath, $decoded) !== false) {
        sendResponse([
            'status' => 'success',
            'url'    => UPLOAD_URL . $filename,
            'name'   => $filename
        ]);
    } else {
        sendResponse(['status' => 'error', 'message' => 'Failed to save base64 image on server'], 500);
    }
}

if (empty($files)) {
    sendResponse(['status' => 'error', 'message' => 'No file received in upload request'], 400);
}

$uploadedUrls = [];
$pdo = getDbConnection();

foreach ($files as $file) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        continue;
    }

    if ($file['size'] > MAX_UPLOAD_SIZE) {
        continue;
    }

    $origName = basename($file['name']);
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        continue;
    }

    $patientId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['patientId'] ?? 'patient');
    $visitId   = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['visitId'] ?? 'visit');

    $safeName = 'p_' . $patientId . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
    $dest = UPLOAD_DIR . $safeName;

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        $url = UPLOAD_URL . $safeName;
        $uploadedUrls[] = $url;

        // Record in attachments table
        try {
            $stmt = $pdo->prepare("INSERT INTO prana_attachments (patient_id, visit_id, file_name, file_path, file_size, mime_type) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$patientId, $visitId, $origName, $url, $file['size'], $file['type']]);
        } catch (Exception $e) {}
    }
}

if (empty($uploadedUrls)) {
    sendResponse(['status' => 'error', 'message' => 'File upload could not be processed'], 500);
}

sendResponse([
    'status' => 'success',
    'urls'   => $uploadedUrls,
    'url'    => $uploadedUrls[0] ?? ''
]);
