<?php
require_once __DIR__ . '/config.php';

// Add CORS headers if needed
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*'));
header('Access-Control-Allow-Credentials: true');

$data = json_decode(file_get_contents('php://input'), true);
$idToken = $data['id_token'] ?? '';

if (empty($idToken)) {
    echo json_encode(['success' => false, 'message' => 'No token provided']);
    exit;
}

// Verify the Google ID token
$tokenInfo = verifyGoogleToken($idToken);

if (!$tokenInfo || isset($tokenInfo['error'])) {
    error_log('Google token verification failed: ' . ($tokenInfo['error'] ?? 'Unknown error'));
    echo json_encode(['success' => false, 'message' => 'Invalid Google token. Please try again.']);
    exit;
}

// Check if email is verified
if (empty($tokenInfo['email_verified']) || $tokenInfo['email_verified'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Email not verified by Google']);
    exit;
}

// Extract user data
$googleId = $tokenInfo['sub'];
$email = $tokenInfo['email'];
$name = $tokenInfo['name'] ?? ($tokenInfo['given_name'] ?? 'User');
$picture = $tokenInfo['picture'] ?? '';

// Validate email
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

// Check if user exists in database
$sql = "SELECT id, name, email, profile_picture, google_id FROM blog_users WHERE google_id = $1 OR email = $2 LIMIT 1";
$result = pg_query_params($con, $sql, [$googleId, $email]);

if (!$result) {
    error_log('Database query failed: ' . pg_last_error($con));
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

if ($row = pg_fetch_assoc($result)) {
    // Existing user
    $userId = $row['id'];

    // Update user info if needed
    $updateSql = "UPDATE blog_users SET 
                 name = $1, 
                 profile_picture = $2,
                 last_login = NOW(),
                 google_id = COALESCE(google_id, $3)
                 WHERE id = $4 
                 AND (google_id IS NULL OR google_id = $3)";

    $updateResult = pg_query_params($con, $updateSql, [
        $name,
        $picture,
        $googleId,
        $userId
    ]);

    if (!$updateResult) {
        error_log('Update failed: ' . pg_last_error($con));
    }
} else {
    // New user - check if email exists with different google_id
    $checkSql = "SELECT id FROM blog_users WHERE email = $1 AND google_id IS NOT NULL AND google_id != $2";
    $checkResult = pg_query_params($con, $checkSql, [$email, $googleId]);

    if (pg_num_rows($checkResult) > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Email already registered with different Google account'
        ]);
        exit;
    }

    // Insert new user
    $insertSql = "INSERT INTO blog_users (google_id, name, email, profile_picture, created_at, last_login) 
                 VALUES ($1, $2, $3, $4, NOW(), NOW()) 
                 RETURNING id";

    $result = pg_query_params($con, $insertSql, [$googleId, $name, $email, $picture]);

    if (!$result) {
        error_log('Insert failed: ' . pg_last_error($con));
        echo json_encode(['success' => false, 'message' => 'Failed to create user account']);
        exit;
    }

    $row = pg_fetch_assoc($result);
    $userId = $row['id'];
}

// Regenerate session ID for security
session_regenerate_id(true);

// Store in session
$_SESSION['user_id'] = $userId;
$_SESSION['google_id'] = $googleId;
$_SESSION['user_name'] = $name;
$_SESSION['user_email'] = $email;
$_SESSION['user_picture'] = $picture;
$_SESSION['logged_in'] = true;
$_SESSION['login_time'] = time();

// Set session cookie with secure parameters
// setcookie(session_name(), session_id(), [
//     'expires' => time() + (24 * 3600), // 24 hours
//     'path' => '/',
//     'domain' => $_SERVER['HTTP_HOST'],
//     'secure' => isset($_SERVER['HTTPS']),
//     'httponly' => true,
//     'samesite' => 'Lax'
// ]);

// Return user data
echo json_encode([
    'success' => true,
    'user' => [
        'id' => $userId,
        'name' => $name,
        'email' => $email,
        'picture' => $picture,
        'google_id' => $googleId
    ]
]);
