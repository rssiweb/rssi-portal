<?php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With");
header("Access-Control-Max-Age: 3600");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Check content type and get data accordingly
    $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

    if (strpos($contentType, 'application/json') !== false) {
        // JSON data
        $data = json_decode(file_get_contents('php://input'), true);
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
    } else {
        // Form data or URL encoded
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
    }

    if (empty($email) || empty($password)) {
        throw new Exception('Email and password are required');
    }

    // Check if user exists
    $query = "SELECT id, password, is_verified FROM recruiters WHERE email = $1";
    $result = pg_query_params($con, $query, [$email]);

    if (!$result || pg_num_rows($result) === 0) {
        throw new Exception('Invalid email or password');
    }

    $user = pg_fetch_assoc($result);

    if (!password_verify($password, $user['password'])) {
        throw new Exception('Invalid email or password');
    }

    if (!$user['is_verified']) {
        throw new Exception('Account not verified. Please check your email.');
    }

    $response['success'] = true;
    $response['message'] = 'Login successful';
    $response['recruiter_id'] = $user['id'];

    // FIXED: Check if password is a default password (6 characters: letters and numbers only)
    // This checks if the password is exactly 6 characters and contains only letters and numbers
    $isDefaultPassword = false;
    if (strlen($password) === 6) {
        // Check if password contains only letters and numbers (no special characters)
        if (preg_match('/^[a-zA-Z0-9]+$/', $password)) {
            // Count letters and numbers
            $letterCount = preg_match_all('/[a-zA-Z]/', $password);
            $numberCount = preg_match_all('/[0-9]/', $password);

            // If it has both letters and numbers (typical of our generated passwords)
            if ($letterCount > 0 && $numberCount > 0) {
                $isDefaultPassword = true;
            }
        }
    }

    $response['requires_password_change'] = $isDefaultPassword;
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Login error: " . $e->getMessage());
}

echo json_encode($response);
