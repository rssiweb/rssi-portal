<?php
// CORS headers - Add these at the very beginning
header("Access-Control-Allow-Origin: https://www.rssi.in");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . "/../../../bootstrap.php";

// Database connection
global $con;
if (!isset($con)) {
    die('Database connection not established');
}

// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', $_ENV['GOOGLE_OAUTH_CLIENT_ID']);
define('GOOGLE_CLIENT_SECRET', $_ENV['GOOGLE_OAUTH_CLIENT_SECRET']);

// Start session with security settings
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => true,
        'cookie_samesite' => 'None',
        'use_strict_mode' => true
    ]);
}

function verifyGoogleToken($idToken)
{
    $client = new Google_Client(['client_id' => GOOGLE_CLIENT_ID]);

    try {
        $payload = $client->verifyIdToken($idToken);
        if ($payload) {
            return [
                'sub' => $payload['sub'],
                'email' => $payload['email'],
                'email_verified' => $payload['email_verified'] ?? false,
                'name' => $payload['name'] ?? '',
                'picture' => $payload['picture'] ?? '',
                'given_name' => $payload['given_name'] ?? '',
                'family_name' => $payload['family_name'] ?? ''
            ];
        }
        return ['error' => 'Invalid token'];
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

function isLoggedIn()
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) &&
        isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function getCurrentUser()
{
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'picture' => $_SESSION['user_picture'] ?? null,
            'google_id' => $_SESSION['google_id']
        ];
    }
    return null;
}

// Add CSRF protection
function generateCSRFToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
