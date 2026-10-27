<?php
/**
 * PRANA HOMEO CARE — ONE-CLICK DATABASE INSTALLER
 * Open this file in your browser to automatically set up the MySQL tables.
 */

require_once __DIR__ . '/config.php';

$message = '';
$status = 'idle';
$details = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['auto'])) {
    try {
        // First try connecting to MySQL server directly
        $dsnWithoutDb = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
        $pdoServer = new PDO($dsnWithoutDb, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Create database if it does not exist
        $pdoServer->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $details[] = "Database `" . DB_NAME . "` verified / created successfully.";

        // Connect to the specific database
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Load and execute schema
        $schemaFile = __DIR__ . '/../database.sql';
        if (file_exists($schemaFile)) {
            $sql = file_get_contents($schemaFile);
            $pdo->exec($sql);
            $details[] = "All clinical tables created successfully (patients, visits, pharmacy, users, attachments).";
        } else {
            throw new Exception("database.sql file not found!");
        }

        // Verify uploads directory
        if (!is_dir(UPLOAD_DIR)) {
            @mkdir(UPLOAD_DIR, 0755, true);
            $details[] = "Uploads directory created at: " . UPLOAD_DIR;
        } else {
            $details[] = "Uploads directory verified.";
        }

        // Ensure default doctor user exists with known password
        $stmt = $pdo->prepare("SELECT id FROM prana_users WHERE email = ?");
        $stmt->execute(['clinic@email.com']);
        if (!$stmt->fetch()) {
            $hash = password_hash('password123', PASSWORD_BCRYPT);
            $insert = $pdo->prepare("INSERT INTO prana_users (username, email, password_hash, full_name, role, clinic_branch) VALUES (?, ?, ?, ?, ?, ?)");
            $insert->execute(['doctor', 'clinic@email.com', $hash, 'Dr. G. Srinivasan M.D(Hom)', 'doctor', 'KATTUPAKKAM']);
            $details[] = "Default doctor login created: clinic@email.com / password123";
        } else {
            $details[] = "Doctor account verified.";
        }

        $status = 'success';
        $message = "Installation completed successfully! Prana Clinical Database is ready.";

        // If requested via JSON API
        if (isset($_GET['json']) || strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'message' => $message,
                'details' => $details
            ]);
            exit();
        }

    } catch (Exception $e) {
        $status = 'error';
        $message = "Installation Error: " . $e->getMessage();
        $details[] = "Tip: Make sure DB_NAME, DB_USER, and DB_PASS in api/config.php match your cPanel MySQL credentials.";

        if (isset($_GET['json'])) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => $message,
                'details' => $details
            ]);
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Prana Homeo Care — Database Installer</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: #f0fdfa;
      color: #0f172a;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      margin: 0;
      padding: 1.5rem;
    }
    .box {
      background: #ffffff;
      max-width: 540px;
      width: 100%;
      border-radius: 28px;
      padding: 2.5rem;
      box-shadow: 0 25px 60px -15px rgba(7, 59, 66, 0.15);
      border: 1px solid rgba(7, 59, 66, 0.1);
    }
    .badge {
      display: inline-block;
      background: #e6f7f9;
      color: #0b5c66;
      font-size: 11px;
      font-weight: 800;
      padding: 4px 12px;
      border-radius: 999px;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 1rem;
    }
    h1 {
      font-size: 1.6rem;
      font-weight: 800;
      color: #073B42;
      margin: 0 0 0.5rem;
    }
    p {
      color: #475569;
      font-size: 0.9rem;
      line-height: 1.6;
      margin: 0 0 1.5rem;
    }
    .status-box {
      padding: 1.2rem;
      border-radius: 16px;
      font-size: 0.85rem;
      margin-bottom: 1.5rem;
    }
    .status-success {
      background: #ecfdf5;
      color: #065f46;
      border: 1px solid #a7f3d0;
    }
    .status-error {
      background: #fef2f2;
      color: #991b1b;
      border: 1px solid #fecaca;
    }
    ul {
      margin: 0.5rem 0 0;
      padding-left: 1.2rem;
    }
    li {
      margin-bottom: 0.3rem;
    }
    .btn {
      display: block;
      width: 100%;
      padding: 1rem;
      border-radius: 14px;
      border: none;
      font-weight: 800;
      font-size: 0.92rem;
      cursor: pointer;
      text-align: center;
      text-decoration: none;
      box-sizing: border-box;
      transition: all 0.2s;
    }
    .btn-primary {
      background: #073B42;
      color: #ffffff;
      box-shadow: 0 10px 25px -5px rgba(7, 59, 66, 0.3);
    }
    .btn-primary:hover {
      background: #0B5C66;
      transform: translateY(-2px);
    }
    .btn-secondary {
      background: #f1f5f9;
      color: #0f172a;
      margin-top: 0.75rem;
    }
  </style>
</head>
<body>
  <div class="box">
    <span class="badge">MySQL Database Setup</span>
    <h1>Prana Homeo Care</h1>
    <p>This utility sets up all required MySQL tables for clinical case taking, pharmacy inventory, patient visits, and image attachments.</p>

    <?php if ($status === 'success'): ?>
      <div class="status-box status-success">
        <strong>✓ <?php echo htmlspecialchars($message); ?></strong>
        <ul>
          <?php foreach ($details as $d): ?>
            <li><?php echo htmlspecialchars($d); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <a href="../case.html" class="btn btn-primary">Open Clinical App (case.html) →</a>
      <a href="../index.html" class="btn btn-secondary">Visit Website Homepage</a>
    <?php elseif ($status === 'error'): ?>
      <div class="status-box status-error">
        <strong>✕ <?php echo htmlspecialchars($message); ?></strong>
        <ul>
          <?php foreach ($details as $d): ?>
            <li><?php echo htmlspecialchars($d); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <form method="POST">
        <button type="submit" class="btn btn-primary">Retry Setup</button>
      </form>
    <?php else: ?>
      <div style="background:#f8fafc; padding:1.2rem; border-radius:16px; margin-bottom:1.5rem; font-size:0.85rem; border:1px solid #e2e8f0;">
        <strong>Target Database:</strong> <code><?php echo DB_NAME; ?></code><br>
        <strong>Host:</strong> <code><?php echo DB_HOST; ?></code><br>
        <strong>User:</strong> <code><?php echo DB_USER; ?></code><br>
        <span style="color:#64748b; font-size:0.78rem;">To customize, update <code>api/config.php</code></span>
      </div>
      <form method="POST">
        <button type="submit" class="btn btn-primary">Run 1-Click Installation Now</button>
      </form>
      <a href="../case.html" class="btn btn-secondary">Go Back to App</a>
    <?php endif; ?>
  </div>
</body>
</html>
