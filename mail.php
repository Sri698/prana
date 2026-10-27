<?php
/**
 * PRANA HOMEO CARE — APPOINTMENT BOOKING & CALENDAR ENGINE
 * Features:
 *  - Dynamic Available Slot Checking (Morning & Evening sessions)
 *  - Dual Email Dispatch (Patient + Clinic Doctor / Admin)
 *  - Google Meet Video Consultations & Google Calendar Invites
 *  - Google Cloud Service Account Integration
 *  - MySQL Database Persistence (prana_appointments)
 *  - Admin Dashboard API Integration
 *
 * Crafted with Excellence for Dr. G. Srinivasan M.D (Hom)
 * Prana Homeo Care — Door of Hope
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Global Config Inclusion
$configPath = __DIR__ . '/api/config.php';
if (!file_exists($configPath)) {
    $configPath = __DIR__ . '/config.php';
}
if (file_exists($configPath)) {
    require_once $configPath;
}

// -------------------------------------------------------------
// Database Connection Helper
// -------------------------------------------------------------
function getPranaPdo() {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $configPath = __DIR__ . '/api/config.php';
    if (!file_exists($configPath)) {
        $configPath = __DIR__ . '/config.php';
    }

    if (file_exists($configPath)) {
        require_once $configPath;
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    } else {
        // Fallback default
        $pdo = new PDO("mysql:host=localhost;dbname=prana_clinic;charset=utf8mb4", "root", "", [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }

    // Ensure table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS `prana_appointments` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    return $pdo;
}

// -------------------------------------------------------------
// ACTION: GET AVAILABLE SLOTS FOR A SPECIFIC DATE
// -------------------------------------------------------------
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'slots') {
    $date = trim($_GET['date'] ?? date('Y-m-d'));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $date = date('Y-m-d');
    }

    $dayOfWeek = date('N', strtotime($date)); // 1 (Mon) to 7 (Sun)

    // Standard clinical slots:
    // Sunday (7): Morning only (10:00 AM - 1:00 PM)
    // Mon-Sat (1-6): Morning (10:00 AM - 1:00 PM) & Evening (5:00 PM - 9:00 PM)
    $morningSlots = [
        '10:00 AM', '10:30 AM', '11:00 AM', '11:30 AM', '12:00 PM', '12:30 PM'
    ];
    $eveningSlots = [
        '5:00 PM', '5:30 PM', '6:00 PM', '6:30 PM', '7:00 PM', '7:30 PM', '8:00 PM', '8:30 PM'
    ];

    $allSlots = ($dayOfWeek == 7) ? $morningSlots : array_merge($morningSlots, $eveningSlots);

    // Robust slot normalizer to guarantee exact matching
    $normalizeSlot = function($str) {
        $clean = trim(preg_replace('/\s+/', ' ', strtoupper($str)));
        if (preg_match('/(\d{1,2})[:.](\d{2})\s*(AM|PM)/i', $clean, $m)) {
            $h = intval($m[1]);
            $min = $m[2];
            $ampm = strtoupper($m[3]);
            return sprintf("%d:%s %s", $h, $min, $ampm);
        }
        return $clean;
    };

    // Fetch existing bookings for this date
    $bookedTimes = [];
    try {
        $pdo = getPranaPdo();
        $stmt = $pdo->prepare("SELECT appointment_time FROM prana_appointments WHERE appointment_date = ? AND status != 'cancelled'");
        $stmt->execute([$date]);
        $rows = $stmt->fetchAll();
        foreach ($rows as $r) {
            $bookedTimes[] = $normalizeSlot($r['appointment_time']);
        }
    } catch (Exception $e) {}

    $slotList = [];
    foreach ($allSlots as $s) {
        $norm = $normalizeSlot($s);
        $isBooked = in_array($norm, $bookedTimes);
        $slotList[] = [
            'time'      => $s,
            'period'    => (strtotime($date . ' ' . $s) < strtotime($date . ' 13:30:00')) ? 'Morning' : 'Evening',
            'available' => !$isBooked,
            'booked'    => $isBooked
        ];
    }

    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'status'          => 'success',
        'date'            => $date,
        'day_of_week'     => date('l', strtotime($date)),
        'total_slots'     => count($slotList),
        'available_count' => count(array_filter($slotList, fn($x) => $x['available'])),
        'booked_count'    => count(array_filter($slotList, fn($x) => $x['booked'])),
        'slots'           => $slotList
    ]);
    exit();
}

// -------------------------------------------------------------
// ACTION: GET ALL APPOINTMENTS (FOR ADMIN DASHBOARD)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($action === '' || $action === 'list')) {
    try {
        $pdo = getPranaPdo();
        $statusFilter = $_GET['status'] ?? '';
        $modeFilter   = $_GET['mode'] ?? '';

        $sql = "SELECT * FROM prana_appointments WHERE 1=1";
        $params = [];

        if (!empty($statusFilter) && $statusFilter !== 'ALL') {
            $sql .= " AND status = ?";
            $params[] = $statusFilter;
        }
        if (!empty($modeFilter) && $modeFilter !== 'ALL') {
            $sql .= " AND mode LIKE ?";
            $params[] = "%{$modeFilter}%";
        }

        $sql .= " ORDER BY appointment_date DESC, id DESC LIMIT 150";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'status' => 'success',
            'data'   => $rows,
            'count'  => count($rows)
        ]);
        exit();
    } catch (Exception $e) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit();
    }
}

// -------------------------------------------------------------
// ACTION: UPDATE STATUS (PUT / POST update_status)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'PUT' || $action === 'update_status') {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?? $_POST;
    $id = intval($input['id'] ?? $_GET['id'] ?? 0);
    $newStatus = trim($input['status'] ?? 'completed');

    if ($id > 0) {
        try {
            $pdo = getPranaPdo();
            $stmt = $pdo->prepare("UPDATE prana_appointments SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $id]);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['status' => 'success', 'message' => 'Appointment status updated']);
            exit();
        } catch (Exception $e) {
            header('Content-Type: application/json; charset=UTF-8', true, 500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit();
        }
    }
}

// -------------------------------------------------------------
// ACTION: BOOK NEW APPOINTMENT (POST)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = $_POST;
    if (empty($input)) {
        $raw = file_get_contents('php://input');
        if (!empty($raw)) {
            $input = json_decode($raw, true) ?? [];
        }
    }

    $name    = htmlspecialchars(trim($input['name'] ?? 'Guest Patient'));
    $age     = htmlspecialchars(trim($input['age'] ?? $input['ageGroup'] ?? 'Not specified'));
    $phone   = htmlspecialchars(trim($input['phone'] ?? ''));
    $email   = htmlspecialchars(trim($input['email'] ?? ''));
    $date    = htmlspecialchars(trim($input['appointment_date'] ?? $input['date'] ?? date('Y-m-d')));
    $time    = htmlspecialchars(trim($input['appointment_time'] ?? $input['time'] ?? '10:00 AM'));
    $mode    = htmlspecialchars(trim($input['mode'] ?? 'In-Clinic (Kattupakkam, Chennai)'));
    $concern = htmlspecialchars(trim($input['concern'] ?? 'General Consultation'));
    $message = htmlspecialchars(trim($input['message'] ?? $input['notes'] ?? ''));

    // Google Meet Link
    $isOnline = (stripos($mode, 'Online') !== false || stripos($mode, 'Video') !== false || stripos($mode, 'Meet') !== false);
    $defaultMeet = defined('CLINIC_MEET_LINK') ? CLINIC_MEET_LINK : 'https://meet.google.com/new';
    $meetLink = $isOnline ? $defaultMeet : '';

    // Google Cloud Service Account checked below

    // Clean phone number for WhatsApp
    $cleanPhone = preg_replace('/\D/', '', $phone);
    if (strlen($cleanPhone) === 10) {
        $cleanPhone = '91' . $cleanPhone;
    }

    // Pre-formatted WhatsApp confirmation message
    $waText = "Hello Dr. G. Srinivasan (Prana Homeo Care),\n\n" .
              "I have booked a consultation:\n" .
              "• Patient: {$name} ({$age})\n" .
              "• Phone: {$phone}\n" .
              "• Date: {$date} at {$time}\n" .
              "• Mode: {$mode}\n" .
              "• Concern: {$concern}\n\n" .
              "Kindly confirm my slot. Thank you!";
    $whatsappUrl = 'https://wa.me/919884223007?text=' . rawurlencode($waText);

    // Parse selected appointment time into proper start & end timestamp for Google Calendar
    $cleanTime = trim(explode('-', $time)[0]); // Handles "5:30 PM - 6:00 PM" or "10:00 AM"
    $startUnix = strtotime("{$date} {$cleanTime}");
    if (!$startUnix) {
        $startUnix = strtotime("{$date} 10:00:00");
    }
    $endUnix = $startUnix + (30 * 60); // 30 minutes duration
    $calStart = gmdate('Ymd\THis\Z', $startUnix);
    $calEnd   = gmdate('Ymd\THis\Z', $endUnix);

    // Dynamic Google Calendar & Google Meet API Integration
    $gcalEventLink = null;
    $gcalServicePath = __DIR__ . '/api/GoogleCalendarService.php';
    if (!file_exists($gcalServicePath)) {
        $gcalServicePath = __DIR__ . '/GoogleCalendarService.php';
    }
    if (file_exists($gcalServicePath)) {
        require_once $gcalServicePath;
        $gcal = new GoogleCalendarService();
        if ($gcal->isConfigured()) {
            $startIso = date('Y-m-d\TH:i:s+05:30', $startUnix);
            $endIso   = date('Y-m-d\TH:i:s+05:30', $endUnix);
            $gcalRes  = $gcal->createAppointmentWithMeet(
                "Prana Homeo Care Consultation — Dr. G. Srinivasan ({$name})",
                "Patient: {$name}\nPhone: {$phone}\nEmail: {$email}\nSlot: {$date} at {$time}\nMode: {$mode}\nConcern: {$concern}\nNotes: {$message}",
                $startIso,
                $endIso,
                $defaultMeet
            );
            if (!empty($gcalRes['success']) && !empty($gcalRes['meet_link']) && is_string($gcalRes['meet_link'])) {
                $meetLink = $gcalRes['meet_link']; // Real active Google Meet room
                $gcalEventLink = $gcalRes['html_link'] ?? null;
            }
        }
    }

    if (is_array($meetLink)) {
        $meetLink = $defaultMeet;
    }
    if (!$isOnline) {
        $meetLink = '';
    }

    $gcalTitle = rawurlencode("Prana Homeo Care Consultation — Dr. G. Srinivasan");
    $gcalDesc  = rawurlencode("Doctor: Dr. G. Srinivasan, M.D(Hom)\nPatient: {$name}\nMode: {$mode}" . ($meetLink ? "\nGoogle Meet Link: {$meetLink}" : ""));
    $gcalLoc   = rawurlencode($meetLink ? "Google Meet: {$meetLink}" : "Plot no 121, 2/94A Jayalakshmi Nagar, Kattupakkam, Chennai 600056");
    $gcalLink  = "https://calendar.google.com/calendar/render?action=TEMPLATE&text={$gcalTitle}&dates={$calStart}/{$calEnd}&details={$gcalDesc}&location={$gcalLoc}";

    // Generate iCalendar (.ics) string for native Gmail & Google Calendar integration
    $dtStamp = gmdate('Ymd\THis\Z');
    $icsUid  = "prana-appt-" . uniqid() . "@pranahomeocare.in";
    $icsContent = "BEGIN:VCALENDAR\r\n" .
                  "VERSION:2.0\r\n" .
                  "PRODID:-//Prana Homeo Care//Consultation Booking//EN\r\n" .
                  "CALSCALE:GREGORIAN\r\n" .
                  "METHOD:REQUEST\r\n" .
                  "BEGIN:VEVENT\r\n" .
                  "UID:{$icsUid}\r\n" .
                  "DTSTAMP:{$dtStamp}\r\n" .
                  "DTSTART:{$calStart}\r\n" .
                  "DTEND:{$calEnd}\r\n" .
                  "SUMMARY:Prana Homeo Care Consultation — Dr. G. Srinivasan\r\n" .
                  "DESCRIPTION:Patient: {$name}\\nSlot: {$date} at {$time}\\nMode: {$mode}\\nConcern: {$concern}" . ($meetLink ? "\\nGoogle Meet: {$meetLink}" : "") . "\r\n" .
                  "LOCATION:" . ($meetLink ? "Google Meet: {$meetLink}" : "Plot no 121, 2/94A Jayalakshmi Nagar, Kattupakkam, Chennai 600056") . "\r\n" .
                  "STATUS:CONFIRMED\r\n" .
                  "ORGANIZER;CN=\"Prana Homeo Care\":mailto:pranaclinicbooking@gmail.com\r\n" .
                  "ATTENDEE;ROLE=REQ-PARTICIPANT;PARTSTAT=ACCEPTED;CN=\"{$name}\":mailto:" . ($email ?: 'patient@pranahomeocare.in') . "\r\n" .
                  "END:VEVENT\r\n" .
                  "END:VCALENDAR\r\n";

    $rescheduleUrl = "https://pranahomeocare.in/appointment.html?reschedule=1&phone=" . urlencode($phone);

    // 1. Save to Database
    $appointmentId = 0;
    try {
        $pdo = getPranaPdo();
        $stmt = $pdo->prepare("INSERT INTO prana_appointments (name, age, phone, email, appointment_date, appointment_time, mode, concern, message, status, meet_link, calendar_link) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', ?, ?)");
        $stmt->execute([$name, $age, $phone, $email, $date, $time, $mode, $concern, $message, $meetLink, $gcalLink]);
        $appointmentId = $pdo->lastInsertId();
    } catch (Exception $dbErr) {
        error_log("Database appointment log error: " . $dbErr->getMessage());
    }

    // 2. DUAL EMAIL DISPATCH (Patient + Clinic Doctor / Admin)
    $phpMailerPath = __DIR__ . '/api/PHPMailer/PHPMailer.php';
    if (!file_exists($phpMailerPath)) {
        $phpMailerPath = __DIR__ . '/PHPMailer/PHPMailer.php';
    }

    $clinicMailSent = false;
    $patientMailSent = false;

    // Doctor / Clinic Email HTML
    $clinicMailHtml = "
    <div style='font-family:Arial,sans-serif; max-width:620px; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden;'>
        <div style='background:#073B42; color:#ffffff; padding:22px;'>
            <h2 style='margin:0; font-size:20px; color:#C9A24A;'>PRANA HOMEO CARE</h2>
            <p style='margin:4px 0 0; font-size:13px; color:#BFE3E8;'>New Consultation Request Received</p>
        </div>
        <div style='padding:24px; background:#fafdfd;'>
            <table style='width:100%; border-collapse:collapse; font-size:14px;'>
                <tr style='background:#f1f7f8;'><td style='padding:10px; font-weight:bold; width:35%;'>Patient Name</td><td style='padding:10px;'><b>{$name}</b></td></tr>
                <tr><td style='padding:10px; font-weight:bold;'>Age / Category</td><td style='padding:10px;'>{$age}</td></tr>
                <tr style='background:#f1f7f8;'><td style='padding:10px; font-weight:bold;'>Phone</td><td style='padding:10px;'><a href='tel:{$phone}' style='color:#073B42; font-weight:bold;'>{$phone}</a></td></tr>
                <tr><td style='padding:10px; font-weight:bold;'>Email</td><td style='padding:10px;'>{$email}</td></tr>
                <tr style='background:#f1f7f8;'><td style='padding:10px; font-weight:bold;'>Scheduled Date</td><td style='padding:10px;'><b>{$date}</b> at {$time}</td></tr>
                <tr><td style='padding:10px; font-weight:bold;'>Consultation Mode</td><td style='padding:10px;'><b>{$mode}</b></td></tr>
                <tr style='background:#f1f7f8;'><td style='padding:10px; font-weight:bold;'>Clinical Concern</td><td style='padding:10px;'>{$concern}</td></tr>
                <tr><td style='padding:10px; font-weight:bold;'>Patient Notes</td><td style='padding:10px;'>{$message}</td></tr>
                " . ($meetLink ? "<tr style='background:#ecfdf5;'><td style='padding:10px; font-weight:bold;'>Google Meet</td><td style='padding:10px;'><a href='{$meetLink}' style='color:#059669; font-weight:bold;'>{$meetLink}</a></td></tr>" : "") . "
            </table>
            <div style='margin-top:20px; text-align:center;'>
                <a href='https://wa.me/{$cleanPhone}' style='display:inline-block; background:#25D366; color:#ffffff; font-weight:bold; text-decoration:none; padding:10px 18px; border-radius:8px;'>WhatsApp Patient Directly</a>
            </div>
        </div>
        <div style='background:#073B42; color:#88aeb3; padding:12px; font-size:11px; text-align:center;'>
            Dr. G. Srinivasan, M.D (Hom) · Kattupakkam, Chennai · +91 98842 23007
        </div>
    </div>";

    // Patient Confirmation Email HTML
    $patientMailHtml = "
    <div style='font-family:Arial,sans-serif; max-width:600px; color:#1e293b; border:1px solid #e2e8f0; border-radius:14px; overflow:hidden;'>
        <div style='background:#073B42; padding:24px; text-align:center;'>
            <h1 style='color:#ffffff; margin:0; font-size:22px; letter-spacing:1px;'>PRANA HOMEO CARE</h1>
            <p style='color:#C9A24A; margin:4px 0 0; font-size:12px; font-weight:bold; letter-spacing:2px; text-transform:uppercase;'>Door of Hope · Chennai</p>
            <p style='color:#BFE3E8; margin:6px 0 0; font-size:12px;'>Dr. G. Srinivasan M.D(Hom) — Consultant Homoeopathic Paediatrician</p>
        </div>
        <div style='padding:26px; background:#ffffff;'>
            <h2 style='color:#073B42; margin-top:0;'>Thank you, {$name}!</h2>
            <p style='color:#475569; font-size:14px; line-height:1.6;'>
                Your appointment has been successfully recorded at Prana Homeo Care. Below are your consultation details:
            </p>
            <div style='background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:18px; margin:20px 0;'>
                <p style='margin:0 0 8px; font-size:14px;'><strong>📅 Date &amp; Time:</strong> {$date} at {$time}</p>
                <p style='margin:0 0 8px; font-size:14px;'><strong>📍 Consultation Mode:</strong> {$mode}</p>
                <p style='margin:0 0 8px; font-size:14px;'><strong>🩺 Primary Concern:</strong> {$concern}</p>
                " . ($meetLink ? "
                <div style='margin-top:14px; padding:12px; background:#ecfdf5; border-radius:8px; border:1px solid #a7f3d0;'>
                    <p style='margin:0 0 6px; font-size:13px; color:#065f46;'><strong>📹 Google Meet Video Link:</strong></p>
                    <a href='{$meetLink}' style='color:#059669; font-weight:bold; font-size:14px;'>{$meetLink}</a>
                </div>
                <div style='margin-top:10px;'>
                    <a href='{$gcalLink}' target='_blank' style='display:inline-block; background:#073B42; color:#ffffff; font-size:12px; font-weight:bold; padding:8px 14px; border-radius:6px; text-decoration:none;'>+ Add to Google Calendar</a>
                </div>
                " : "
                <p style='margin:10px 0 0; font-size:13px; color:#475569;'>
                    <strong>Clinic Address:</strong> Plot no 121, 2/94A Jayalakshmi Nagar, Kattupakkam, Chennai 600056 (Near Porur &amp; Iyyappanthangal)
                </p>
                ") . "
            </div>
            <div style='text-align:center; margin-top:20px;'>
                <p style='font-size:12px; color:#64748b;'>Need to reschedule or have questions?</p>
                <a href='{$rescheduleUrl}' style='color:#0B5C66; font-size:12px; font-weight:bold; text-decoration:underline; margin-right:15px;'>Reschedule Appointment</a>
                <a href='https://wa.me/919884223007' style='color:#25D366; font-size:12px; font-weight:bold; text-decoration:none;'>WhatsApp Helpline: +91 98842 23007</a>
            </div>
        </div>
        <div style='background:#f1f5f9; padding:14px; text-align:center; font-size:11px; color:#64748b;'>
            Prana Homeo Care · Dr. G. Srinivasan M.D(Hom) · Timings: Mon–Sat 10 AM–1 PM &amp; 5:30 PM–9 PM · Sun 10 AM–1 PM
        </div>
    </div>";

    // Determine Doctor Notification Recipients
    $doctorRecipientList = defined('DOCTOR_ALERT_EMAILS') ? DOCTOR_ALERT_EMAILS : [
        'pranahomeocare@gmail.com',
        'dr.srinivasan@pranahomeocare.in'
    ];

    // PRIORITY 1: OFFICIAL GMAIL REST API (Bypasses cPanel SMTP & Spam filters if OAuth token is active)
    if (isset($gcal) && $gcal instanceof GoogleCalendarService && $gcal->hasOAuthToken()) {
        if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $gResPat = $gcal->sendGmailREST($email, "Appointment Confirmed: Prana Homeo Care — Dr. G. Srinivasan", $patientMailHtml);
            if (!empty($gResPat['success'])) {
                $patientMailSent = true;
            }
        }
        $gResDoc = $gcal->sendGmailREST('pranaclinicbooking@gmail.com', "New Consultation Booking: {$name} - {$concern} ({$date})", $clinicMailHtml);
        if (!empty($gResDoc['success'])) {
            $clinicMailSent = true;
        }
    }

    // PRIORITY 2: AUTHENTICATED GMAIL SMTP VIA PHPMAILER
    if ((!$patientMailSent || !$clinicMailSent) && file_exists($phpMailerPath)) {
        try {
            $baseDir = dirname($phpMailerPath);
            require_once $baseDir . '/Exception.php';
            require_once $baseDir . '/PHPMailer.php';
            require_once $baseDir . '/SMTP.php';

            // Configure Official Gmail SMTP
            // Configure Official Gmail SMTP
            $mailConfig = function($mailObj) {
                $mailObj->isSMTP();
                $mailObj->Host       = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com';
                $mailObj->SMTPAuth   = true;
                $mailObj->Username   = defined('SMTP_USER') ? SMTP_USER : 'pranaclinicbooking@gmail.com';
                $mailObj->Password   = defined('SMTP_PASS') ? SMTP_PASS : 'kelmozrowyyvplqm';
                $mailObj->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mailObj->Port       = defined('SMTP_PORT') ? SMTP_PORT : 587;
                $mailObj->CharSet    = 'UTF-8';
                $fromEmail = defined('MAIL_FROM_EMAIL') ? MAIL_FROM_EMAIL : 'pranaclinicbooking@gmail.com';
                $fromName  = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'Prana Homeo Care';
                $mailObj->setFrom($fromEmail, $fromName);
                $mailObj->Sender = $fromEmail; // Explicit Return-Path prevents SPF failure
            };

            // PRIORITY 1: DISPATCH PATIENT CONFIRMATION FIRST
            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                try {
                    $mailPat = new \PHPMailer\PHPMailer\PHPMailer(true);
                    $mailConfig($mailPat);
                    $mailPat->addAddress($email, $name);
                    $mailPat->addReplyTo('pranahomeocare@gmail.com', 'Dr. G. Srinivasan - Prana Homeo Care');
                    $mailPat->isHTML(true);
                    $mailPat->Subject = "Appointment Confirmed: Prana Homeo Care — Dr. G. Srinivasan";
                    $mailPat->Body = $patientMailHtml;
                    $mailPat->AltBody = "Hello {$name},\n\nYour consultation with Dr. G. Srinivasan, M.D(Hom) at Prana Homeo Care is confirmed.\n\nDate & Time: {$date} at {$time}\nMode: {$mode}\nPrimary Concern: {$concern}\n" . ($meetLink ? "Google Meet Link: {$meetLink}\n" : "Clinic Address: Plot no 121, 2/94A Jayalakshmi Nagar, Kattupakkam, Chennai 600056\n") . "\nWhatsApp Helpline: +91 98842 23007\n\nPrana Homeo Care";
                    $mailPat->addStringAttachment($icsContent, 'appointment.ics', 'base64', 'text/calendar');
                    $mailPat->send();
                    $patientMailSent = true;
                } catch (Exception $ePat) {
                    error_log("Patient mail delivery notice: " . $ePat->getMessage());
                    $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: Prana Homeo Care <pranaclinicbooking@gmail.com>\r\nReply-To: pranahomeocare@gmail.com\r\n";
                    @mail($email, "Appointment Confirmed: Prana Homeo Care", $patientMailHtml, $headers);
                }
            }

            // PRIORITY 2: DISPATCH DOCTOR & CLINIC RECEPTION NOTIFICATION
            try {
                $mailDoc = new \PHPMailer\PHPMailer\PHPMailer(true);
                $mailConfig($mailDoc);
                $mailDoc->addAddress('pranaclinicbooking@gmail.com', 'Prana Clinic Reception');
                $mailDoc->addCC('pranahomeocare@gmail.com', 'Dr. G. Srinivasan');
                if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $mailDoc->addReplyTo($email, $name);
                }
                $mailDoc->isHTML(true);
                $mailDoc->Subject = "New Consultation Booking: {$name} - {$concern} ({$date})";
                $mailDoc->Body = $clinicMailHtml;
                $mailDoc->Ical = $icsContent;
                $mailDoc->addStringAttachment($icsContent, 'invite.ics', 'base64', 'text/calendar; charset=utf-8; method=REQUEST');
                $mailDoc->send();
                $clinicMailSent = true;
            } catch (Exception $eDoc) {
                error_log("Doctor mail delivery notice: " . $eDoc->getMessage());
                $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: Prana Homeo Care <pranaclinicbooking@gmail.com>\r\n";
                @mail('pranahomeocare@gmail.com', "New Consultation Booking: {$name} - {$concern} ({$date})", $clinicMailHtml, $headers);
            }
        } catch (Exception $e) {
            error_log("PHPMailer setup error: " . $e->getMessage());
        }
    }

    // Direct native mail() fallback if PHPMailer was not executed
    if (!$patientMailSent && !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: Prana Homeo Care <pranaclinicbooking@gmail.com>\r\n";
        @mail($email, "Consultation Confirmed: Prana Homeo Care", $patientMailHtml, $headers);
    }
    if (!$clinicMailSent) {
        $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: Prana Homeo Care <pranaclinicbooking@gmail.com>\r\n";
        @mail('pranaclinicbooking@gmail.com', "New Consultation Booking: {$name} - {$concern} ({$date})", $clinicMailHtml, $headers);
        @mail('pranahomeocare@gmail.com', "New Consultation Booking: {$name} - {$concern} ({$date})", $clinicMailHtml, $headers);
    }

    // Output JSON response
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'status'         => 'success',
        'message'        => 'Appointment booked successfully!',
        'appointment_id' => $appointmentId,
        'whatsapp_url'   => $whatsappUrl,
        'meet_link'      => $meetLink,
        'gcal_link'      => $gcalLink,
        'reschedule_url' => $rescheduleUrl
    ]);
    exit();
}
