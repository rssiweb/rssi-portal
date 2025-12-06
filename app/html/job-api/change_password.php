<?php
require_once __DIR__ . "/../../bootstrap.php";

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

    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'] ?? '';
    $currentPassword = $data['current_password'] ?? '';
    $newPassword = $data['new_password'] ?? '';

    if (empty($email) || empty($currentPassword) || empty($newPassword)) {
        throw new Exception('All fields are required');
    }

    if (strlen($newPassword) < 8) {
        throw new Exception('New password must be at least 8 characters long');
    }

    // Verify current password
    $query = "SELECT id, password FROM recruiters WHERE email = $1";
    $result = pg_query_params($con, $query, [$email]);

    if (!$result || pg_num_rows($result) === 0) {
        throw new Exception('User not found');
    }

    $user = pg_fetch_assoc($result);

    if (!password_verify($currentPassword, $user['password'])) {
        throw new Exception('Current password is incorrect');
    }

    // Hash new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update password
    $updateQuery = "UPDATE recruiters SET password = $1, password_updated_by= $2, password_updated_on=$3 WHERE email = $4";
    $updateResult = pg_query_params($con, $updateQuery, [$hashedPassword, $email, date("Y-m-d H:i:s"), $email]);

    if (!$updateResult) {
        throw new Exception('Failed to update password');
    }

    $response['success'] = true;
    $response['message'] = 'Password changed successfully';
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
