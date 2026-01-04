<?php
require_once __DIR__ . "/../../bootstrap.php";

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Get user_id from query parameter
$user_id = isset($_GET['user_id']) ? trim($_GET['user_id']) : null;

if (!$user_id) {
    echo json_encode([
        'success' => false,
        'message' => 'User ID is required',
        'is_admin' => false
    ]);
    exit;
}

// Validate user_id is not empty
if (empty($user_id)) {
    echo json_encode([
        'success' => false,
        'message' => 'User ID cannot be empty',
        'is_admin' => false
    ]);
    exit;
}

try {
    // Escape the user_id for security
    $escaped_user_id = pg_escape_string($con, $user_id);

    // Check if user exists in blog_users table and get admin status
    $sql = "SELECT id, is_admin FROM blog_users WHERE id = '$escaped_user_id' LIMIT 1";

    error_log("Checking admin status for user: " . $user_id);
    error_log("SQL Query: " . $sql);

    $result = pg_query($con, $sql);

    if (!$result) {
        $error = pg_last_error($con);
        error_log("Database query error: " . $error);
        throw new Exception('Database query failed');
    }

    if (pg_num_rows($result) === 0) {
        // User doesn't exist in blog_users table
        error_log("User not found in blog_users table: " . $user_id);

        echo json_encode([
            'success' => true,
            'is_admin' => false,
            'message' => 'User not found in system',
            'user_exists' => false
        ]);
        exit;
    }

    // User exists, get admin status
    $userData = pg_fetch_assoc($result);
    $isAdmin = ($userData['is_admin'] === 't' || $userData['is_admin'] === true);

    error_log("User found. Admin status: " . ($isAdmin ? 'true' : 'false'));

    echo json_encode([
        'success' => true,
        'is_admin' => $isAdmin,
        'user_id' => $user_id,
        'user_exists' => true,
        'message' => $isAdmin ? 'User is administrator' : 'User is not administrator'
    ]);
} catch (Exception $e) {
    error_log("Exception in check_admin.php: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'is_admin' => false,
        'message' => 'Error checking admin status: ' . $e->getMessage(),
        'user_exists' => false
    ]);
}
