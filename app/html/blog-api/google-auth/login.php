<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$idToken = $data['id_token'] ?? '';

if (empty($idToken)) {
    echo json_encode(['success' => false, 'message' => 'No token provided']);
    exit;
}

// Verify the Google ID token
$tokenInfo = verifyGoogleToken($idToken);

if (!$tokenInfo || isset($tokenInfo['error'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit;
}

// Extract user data
$googleId = $tokenInfo['sub'];
$email = $tokenInfo['email'];
$name = $tokenInfo['name'] ?? '';
$picture = $tokenInfo['picture'] ?? '';

// Validate
if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email is required']);
    exit;
}

// Check if user exists in database
$sql = "SELECT id, name, email, profile_picture FROM blog_users WHERE google_id = $1 OR email = $2 LIMIT 1";
$result = pg_query_params($con, $sql, [$googleId, $email]);

if ($row = pg_fetch_assoc($result)) {
    // Existing user
    $userId = $row['id'];
    
    // Update user info
    $updateSql = "UPDATE blog_users SET 
                 name = $1, 
                 profile_picture = $2,
                 last_login = NOW(),
                 google_id = COALESCE(google_id, $3)
                 WHERE id = $4";
    pg_query_params($con, $updateSql, [$name, $picture, $googleId, $userId]);
} else {
    // New user
    $insertSql = "INSERT INTO blog_users (google_id, name, email, profile_picture, created_at, last_login) 
                 VALUES ($1, $2, $3, $4, NOW(), NOW()) 
                 RETURNING id";
    $result = pg_query_params($con, $insertSql, [$googleId, $name, $email, $picture]);
    
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Failed to create user']);
        exit;
    }
    
    $row = pg_fetch_assoc($result);
    $userId = $row['id'];
}

// Store in session
$_SESSION['user_id'] = $userId;
$_SESSION['google_id'] = $googleId;
$_SESSION['user_name'] = $name;
$_SESSION['user_email'] = $email;
$_SESSION['user_picture'] = $picture;
$_SESSION['logged_in'] = true;

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
?>