<?php
/**
 * PRANA HOMEO CARE — GOOGLE OAUTH 2.0 TOKEN GENERATOR
 * 
 * Generates token.json for pranaclinicbooking@gmail.com with:
 * - Google Calendar Events API (Generates real dynamic Google Meet rooms)
 * - Gmail REST API (gmail.send: 100% Inbox delivery, zero spam)
 */
session_start();

$credPaths = [
    __DIR__ . '/credentials_oauth.json',
    __DIR__ . '/../credentials_oauth.json'
];

$clientId = '';
$clientSecret = '';

foreach ($credPaths as $p) {
    if (file_exists($p)) {
        $c = json_decode(file_get_contents($p), true);
        $clientId = $c['web']['client_id'] ?? $c['installed']['client_id'] ?? '';
        $clientSecret = $c['web']['client_secret'] ?? $c['installed']['client_secret'] ?? '';
        break;
    }
}

// Allow entering credentials via form if credentials_oauth.json is not uploaded yet
if (isset($_POST['save_creds'])) {
    $clientId = trim($_POST['client_id'] ?? '');
    $clientSecret = trim($_POST['client_secret'] ?? '');
    if ($clientId && $clientSecret) {
        $json = json_encode([
            'web' => [
                'client_id' => $clientId,
                'client_secret' => $clientSecret
            ]
        ], JSON_PRETTY_PRINT);
        file_put_contents(__DIR__ . '/credentials_oauth.json', $json);
        $_SESSION['client_id'] = $clientId;
        $_SESSION['client_secret'] = $clientSecret;
    }
}

if (!empty($_SESSION['client_id'])) $clientId = $_SESSION['client_id'];
if (!empty($_SESSION['client_secret'])) $clientSecret = $_SESSION['client_secret'];

// Detect redirect URI (prioritize credentials_oauth.json to prevent redirect_uri_mismatch)
$redirect_uri = $c['web']['redirect_uris'][0] ?? 'https://pranahomeocare.in/oauth_generate.php';
if (empty($redirect_uri)) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $uri = explode('?', $_SERVER['REQUEST_URI'] ?? '/oauth_generate.php')[0];
    $redirect_uri = $protocol . $host . $uri;
}

if (empty($clientId) || empty($clientSecret)) {
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Prana Homeo Care — Google OAuth Setup</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f8fafc; color: #0f172a; padding: 40px; display: flex; justify-content: center; }
        .card { background: white; max-width: 580px; width: 100%; padding: 32px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; }
        h1 { color: #073B42; font-size: 22px; margin-top: 0; }
        p { font-size: 13px; color: #64748b; line-height: 1.5; }
        label { font-size: 12px; font-weight: bold; color: #334155; display: block; margin-top: 15px; margin-bottom: 5px; }
        input { width: 100%; box-sizing: border-box; padding: 10px 14px; border-radius: 10px; border: 1px solid #cbd5e1; font-size: 13px; font-family: monospace; }
        button { margin-top: 20px; width: 100%; background: #073B42; color: white; padding: 12px; border: none; border-radius: 10px; font-weight: bold; font-size: 14px; cursor: pointer; }
        button:hover { background: #0b5c66; }
        .uri-box { background: #f1f5f9; padding: 10px 14px; border-radius: 8px; font-family: monospace; font-size: 12px; color: #0f172a; word-break: break-all; margin-top: 6px; }
        .steps { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 14px; margin: 15px 0; font-size: 12px; color: #1e3a8a; }
        .steps ol { margin: 0; padding-left: 20px; }
        .steps li { margin-bottom: 6px; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Connect Google Calendar & Gmail API</h1>
        <p>This setup allows Prana Homeo Care to automatically generate real Google Meet rooms for every consultation and dispatch emails directly via official Google Gmail REST API (zero spam).</p>

        <div class="steps">
            <strong>3 Easy Steps in Google Cloud Console:</strong>
            <ol>
                <li>In your Google Cloud project (<code>prana-homeo-care</code>), go to <strong>APIs & Services > Credentials</strong>.</li>
                <li>Click <strong>Create Credentials > OAuth client ID</strong> &rarr; Select <strong>Web application</strong>.</li>
                <li>Under <strong>Authorized redirect URIs</strong>, paste this exact URI:
                    <div class="uri-box"><?php echo htmlspecialchars($redirect_uri); ?></div>
                </li>
            </ol>
        </div>

        <form method="POST">
            <input type="hidden" name="save_creds" value="1">
            <label>Client ID</label>
            <input type="text" name="client_id" placeholder="e.g. 123456789-abcdef.apps.googleusercontent.com" required>
            
            <label>Client Secret</label>
            <input type="text" name="client_secret" placeholder="e.g. GOCSPX-xxxxxxxxxxxxxxxx" required>
            
            <button type="submit">Save & Connect with Google &rarr;</button>
        </form>
    </div>
</body>
</html>
<?php
    exit;
}

if (!isset($_GET['code'])) {
    $auth_url = 'https://accounts.google.com/o/oauth2/auth?' . http_build_query([
        'client_id'     => $clientId,
        'redirect_uri'  => $redirect_uri,
        'response_type' => 'code',
        'scope'         => 'https://www.googleapis.com/auth/calendar.events https://www.googleapis.com/auth/gmail.send',
        'access_type'   => 'offline',
        'prompt'        => 'consent' 
    ]);
    header('Location: ' . $auth_url);
    exit;
} else {
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'client_id'     => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri'  => $redirect_uri,
        'grant_type'    => 'authorization_code',
        'code'          => $_GET['code']
    ]));
    
    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response, true);
    
    if (isset($data['refresh_token'])) {
        $tokenSavePath = __DIR__ . '/token.json';
        file_put_contents($tokenSavePath, json_encode($data, JSON_PRETTY_PRINT));
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Connected Successfully!</title>
            <style>
                body { font-family: system-ui, sans-serif; background: #f8fafc; padding: 40px; text-align: center; }
                .card { background: white; max-width: 500px; margin: 40px auto; padding: 40px; border-radius: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; }
                h1 { color: #059669; font-size: 24px; }
                p { color: #475569; font-size: 14px; line-height: 1.5; }
                a { display: inline-block; margin-top: 20px; background: #073B42; color: white; padding: 12px 24px; border-radius: 12px; font-weight: bold; text-decoration: none; }
            </style>
        </head>
        <body>
            <div class="card">
                <div style="font-size: 48px; margin-bottom: 10px;">🎉</div>
                <h1>Google OAuth Connected!</h1>
                <p><strong>token.json</strong> has been successfully generated for Prana Homeo Care!</p>
                <p>Google Meet automatic room generation and Gmail REST API inbox delivery are now 100% active.</p>
                <a href="../case.html">Go to Clinical Dashboard &rarr;</a>
            </div>
        </body>
        </html>
        <?php
    } else {
        echo "<h2 style='color:red;'>Error generating token.</h2>";
        echo "<pre>" . htmlspecialchars(print_r($data, true)) . "</pre>";
    }
}
?>
