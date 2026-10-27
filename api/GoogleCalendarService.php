<?php
/**
 * PRANA HOMEO CARE — GOOGLE CALENDAR, GOOGLE MEET & GMAIL REST SERVICE
 * 
 * Architecture inspired by production multi-tenant clinic standards:
 * 1. Primary Engine: Google OAuth 2.0 User Token (token.json + credentials_oauth.json)
 *    - Dynamically provisions real Google Meet video rooms (hangoutLink)
 *    - Sends 100% inbox deliverable emails via official Google Gmail REST API (bypassing cPanel SMTP spam traps)
 * 2. Fallback Engine: Google Service Account (JWT OAuth 2.0)
 *    - Inserts appointments directly into Dr. Srinivasan's Google Calendar
 * 
 * No Composer or external dependencies needed — runs 100% on native cPanel PHP with OpenSSL + cURL.
 */

class GoogleCalendarService {
    private $serviceAccountPath;
    private $calendarId;
    private $tokenPath;
    private $oauthCredPath;

    public function __construct($serviceAccountPath = null, $calendarId = 'pranaclinicbooking@gmail.com') {
        if ($serviceAccountPath === null) {
            $defaultPaths = [
                __DIR__ . '/google_service_account.json',
                __DIR__ . '/../google_service_account.json',
                __DIR__ . '/../prana-homeo-care-1fc51b121d95.json'
            ];
            foreach ($defaultPaths as $p) {
                if (file_exists($p)) {
                    $serviceAccountPath = $p;
                    break;
                }
            }
        }
        $this->serviceAccountPath = $serviceAccountPath;
        $this->calendarId = $calendarId;

        // Check for User OAuth Tokens (Barun architecture)
        $tokenSearch = [
            __DIR__ . '/token.json',
            __DIR__ . '/../token.json'
        ];
        foreach ($tokenSearch as $tp) {
            if (file_exists($tp)) {
                $this->tokenPath = $tp;
                break;
            }
        }

        $oauthCredSearch = [
            __DIR__ . '/credentials_oauth.json',
            __DIR__ . '/../credentials_oauth.json'
        ];
        foreach ($oauthCredSearch as $cp) {
            if (file_exists($cp)) {
                $this->oauthCredPath = $cp;
                break;
            }
        }
    }

    public function hasOAuthToken() {
        return !empty($this->tokenPath) && file_exists($this->tokenPath) &&
               !empty($this->oauthCredPath) && file_exists($this->oauthCredPath);
    }

    public function isConfigured() {
        return $this->hasOAuthToken() || (!empty($this->serviceAccountPath) && file_exists($this->serviceAccountPath));
    }

    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Refreshes and returns User OAuth 2.0 Access Token
     */
    public function getOAuthAccessToken() {
        if (!$this->hasOAuthToken()) return null;

        $tokens = json_decode(file_get_contents($this->tokenPath), true);
        $creds  = json_decode(file_get_contents($this->oauthCredPath), true);

        $clientId     = $creds['web']['client_id'] ?? $creds['installed']['client_id'] ?? '';
        $clientSecret = $creds['web']['client_secret'] ?? $creds['installed']['client_secret'] ?? '';
        $refreshToken = $tokens['refresh_token'] ?? null;

        if (!$clientId || !$clientSecret || !$refreshToken) return null;

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken,
                'grant_type'    => 'refresh_token'
            ]),
            CURLOPT_TIMEOUT        => 12
        ]);

        $res = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($res, true);
        if (!empty($data['access_token'])) {
            // Update token with latest values
            $tokens['access_token'] = $data['access_token'];
            if (!empty($data['expires_in'])) $tokens['expires_in'] = $data['expires_in'];
            file_put_contents($this->tokenPath, json_encode($tokens, JSON_PRETTY_PRINT));
            return $data['access_token'];
        }

        return null;
    }

    /**
     * Generates a signed OAuth 2.0 JWT Access Token using Service Account Private Key
     */
    public function getServiceAccountAccessToken() {
        if (empty($this->serviceAccountPath) || !file_exists($this->serviceAccountPath)) {
            return null;
        }

        $jsonStr = file_get_contents($this->serviceAccountPath);
        $creds = json_decode($jsonStr, true);
        if (!$creds || empty($creds['client_email']) || empty($creds['private_key'])) {
            return null;
        }

        $now = time();
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $claims = [
            'iss'   => $creds['client_email'],
            'scope' => 'https://www.googleapis.com/auth/calendar',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'exp'   => $now + 3600,
            'iat'   => $now
        ];

        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $claimsEncoded = $this->base64UrlEncode(json_encode($claims));
        $dataToSign    = $headerEncoded . '.' . $claimsEncoded;

        $privateKey = openssl_pkey_get_private($creds['private_key']);
        if (!$privateKey) return null;

        $binarySignature = '';
        if (!openssl_sign($dataToSign, $binarySignature, $privateKey, OPENSSL_ALGO_SHA256)) {
            return null;
        }

        $jwt = $dataToSign . '.' . $this->base64UrlEncode($binarySignature);

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt
            ]),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT        => 12
        ]);

        $res = curl_exec($ch);
        curl_close($ch);

        $resData = json_decode($res, true);
        return $resData['access_token'] ?? null;
    }

    public function getAccessToken() {
        if ($this->hasOAuthToken()) {
            $userToken = $this->getOAuthAccessToken();
            if ($userToken) return $userToken;
        }
        return $this->getServiceAccountAccessToken();
    }

    /**
     * Creates an Event in Google Calendar with a real active Google Meet room
     * 
     * @param string $summary Event Title
     * @param string $description Event Details
     * @param string $startIso ISO8601 string (e.g. 2026-10-09T17:30:00+05:30)
     * @param string $endIso ISO8601 string (e.g. 2026-10-09T18:00:00+05:30)
     * @param string $fallbackMeetUrl Fallback meet URL
     * @return array [success => bool, meet_link => string, event_id => string|null, html_link => string|null]
     */
    public function createAppointmentWithMeet($summary, $description, $startIso, $endIso, $fallbackMeetUrl = 'https://meet.google.com/new') {
        if (is_array($fallbackMeetUrl) || empty($fallbackMeetUrl) || !is_string($fallbackMeetUrl)) {
            $fallbackMeetUrl = defined('CLINIC_MEET_LINK') ? CLINIC_MEET_LINK : 'https://meet.google.com/new';
        }
        $fallbackMeetUrl = trim($fallbackMeetUrl);

        // 1. Try Primary OAuth User Engine (Barun pattern)
        if ($this->hasOAuthToken()) {
            $userToken = $this->getOAuthAccessToken();
            if ($userToken) {
                $eventData = [
                    'summary'        => $summary,
                    'description'    => $description,
                    'start'          => ['dateTime' => $startIso, 'timeZone' => 'Asia/Kolkata'],
                    'end'            => ['dateTime' => $endIso, 'timeZone' => 'Asia/Kolkata'],
                    'conferenceData' => [
                        'createRequest' => [
                            'requestId' => uniqid('meet_', true),
                            'conferenceSolutionKey' => ['type' => 'hangoutsMeet']
                        ]
                    ]
                ];

                $ch = curl_init('https://www.googleapis.com/calendar/v3/calendars/primary/events?conferenceDataVersion=1');
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => json_encode($eventData),
                    CURLOPT_HTTPHEADER     => [
                        'Authorization: Bearer ' . $userToken,
                        'Content-Type: application/json'
                    ],
                    CURLOPT_TIMEOUT        => 12
                ]);
                $res = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                $data = json_decode($res, true);
                if ($httpCode >= 200 && $httpCode < 300 && !empty($data['hangoutLink'])) {
                    return [
                        'success'   => true,
                        'meet_link' => $data['hangoutLink'],
                        'event_id'  => $data['id'] ?? null,
                        'html_link' => $data['htmlLink'] ?? null,
                        'engine'    => 'OAuth_REST'
                    ];
                }
            }
        }

        // 2. Fallback Engine: Service Account
        $token = $this->getServiceAccountAccessToken();
        if (!$token) {
            return [
                'success'   => false,
                'meet_link' => $fallbackMeetUrl,
                'error'     => 'Could not obtain Google OAuth token'
            ];
        }

        $calendarId = urlencode($this->calendarId);
        $plainUrl = "https://www.googleapis.com/calendar/v3/calendars/{$calendarId}/events";
        $eventPayload = [
            'summary'     => $summary,
            'description' => $description . "\n\nGoogle Meet: " . $fallbackMeetUrl,
            'location'    => "Google Meet: " . $fallbackMeetUrl,
            'start'       => ['dateTime' => $startIso, 'timeZone' => 'Asia/Kolkata'],
            'end'         => ['dateTime' => $endIso, 'timeZone' => 'Asia/Kolkata']
        ];

        $ch = curl_init($plainUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($eventPayload),
            CURLOPT_HTTPHEADER     => [
                "Authorization: Bearer {$token}",
                "Content-Type: application/json"
            ],
            CURLOPT_TIMEOUT        => 12
        ]);

        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($res, true);
        if ($httpCode >= 200 && $httpCode < 300 && is_array($data)) {
            return [
                'success'   => true,
                'meet_link' => $fallbackMeetUrl,
                'event_id'  => $data['id'] ?? null,
                'html_link' => $data['htmlLink'] ?? null,
                'engine'    => 'ServiceAccount'
            ];
        }

        return [
            'success'   => false,
            'meet_link' => $fallbackMeetUrl,
            'error'     => "Calendar insert returned HTTP {$httpCode}"
        ];
    }

    /**
     * Sends email directly via Google Gmail REST API (Bypasses cPanel SMTP & Spam filters)
     */
    public function sendGmailREST($to, $subject, $htmlBody, $accessToken = null) {
        if ($accessToken === null && $this->hasOAuthToken()) {
            $accessToken = $this->getOAuthAccessToken();
        }
        if (!$accessToken) return ['success' => false, 'error' => 'No OAuth access token for Gmail REST API'];

        $rawMessage = "To: {$to}\r\n";
        $rawMessage .= "Subject: =?utf-8?B?" . base64_encode($subject) . "?=\r\n";
        $rawMessage .= "MIME-Version: 1.0\r\n";
        $rawMessage .= "Content-Type: text/html; charset=utf-8\r\n\r\n";
        $rawMessage .= $htmlBody;

        $encodedMessage = $this->base64UrlEncode($rawMessage);

        $ch = curl_init('https://gmail.googleapis.com/gmail/v1/users/me/messages/send');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(['raw' => $encodedMessage]),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT        => 12
        ]);

        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($res, true);
        if ($httpCode >= 200 && $httpCode < 300 && !empty($data['id'])) {
            return ['success' => true, 'message_id' => $data['id']];
        }

        return ['success' => false, 'error' => "Gmail REST returned HTTP {$httpCode}", 'response' => $data];
    }
}
