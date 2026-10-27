<?php
/**
 * PRANA HOMEO CARE — DATABASE CONFIGURATION
 * Configure your cPanel MySQL Database credentials here.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'i6463580_rqp61'); // Replace with your cPanel database name (e.g. username_prana)
define('DB_USER', 'i6463580_rqp61');              // Replace with your cPanel database username
define('DB_PASS', 'H.qunoccS3khkc9cWHP31');                  // Replace with your cPanel database password
define('DB_CHARSET', 'utf8mb4');

// Upload directory for clinical photos and test reports
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', 'uploads/');

// Allowed file types for clinical attachments
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf']);
define('MAX_UPLOAD_SIZE', 25 * 1024 * 1024); // 25 MB

// Official Gmail SMTP Configuration (Powered by Google Workspace/Gmail)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_AUTH', true);
define('SMTP_USER', 'pranaclinicbooking@gmail.com');
define('SMTP_PASS', 'kelmozrowyyvplqm'); // Google App Password
define('SMTP_SECURE', 'tls');
define('SMTP_PORT', 587);
define('MAIL_FROM_EMAIL', 'pranaclinicbooking@gmail.com');
define('MAIL_FROM_NAME', 'Prana Homeo Care');

// Doctor / Clinic Notification Recipient Emails
define('DOCTOR_ALERT_EMAILS', [
    'pranaclinicbooking@gmail.com',
    'pranahomeocare@gmail.com',
    'dr.srinivasan@pranahomeocare.in'
]);

// Clinic's Google Meet Consultation Link
// Instant live meeting launcher (https://meet.google.com/new) or set your permanent room code (e.g. https://meet.google.com/abc-defg-hij)
define('CLINIC_MEET_LINK', 'https://meet.google.com/new');


