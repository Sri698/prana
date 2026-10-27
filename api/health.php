<?php
/**
 * PRANA HOMEO CARE — SYSTEM HEALTH & DIAGNOSTIC ENDPOINT
 */

require_once __DIR__ . '/db.php';

$response = [
    'status' => 'healthy',
    'app' => 'Prana Homeo Care Clinical API',
    'version' => '1.0.0',
    'php_version' => PHP_VERSION,
    'server_time' => date('Y-m-d H:i:s'),
    'database' => [
        'connected' => false,
        'name' => DB_NAME,
        'tables' => []
    ],
    'uploads' => [
        'dir_exists' => is_dir(UPLOAD_DIR),
        'writable'   => is_writable(UPLOAD_DIR)
    ]
];

try {
    $pdo = getDbConnection();
    $response['database']['connected'] = true;

    // Check existing tables
    $stmt = $pdo->query("SHOW TABLES LIKE 'prana_%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $response['database']['tables'] = $tables;
    $response['database']['installed'] = count($tables) >= 3;
} catch (Exception $e) {
    $response['database']['error'] = $e->getMessage();
}

sendResponse($response);
