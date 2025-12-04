<?php
// check_user.php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

$response = ['success' => false, 'message' => '', 'user_exists' => false, 'has_password' => false];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $email = $_POST['email'] ?? '';

    if (empty($email)) {
        throw new Exception('Email is required');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Check if user exists and has password
    $query = "SELECT id, password FROM recruiters WHERE email = $1";
    $result = pg_query_params($con, $query, [$email]);

    if (!$result) {
        $error = pg_last_error($con);
        error_log("Database error checking user: " . $error);
        throw new Exception('Database error. Please try again.');
    }

    if (pg_num_rows($result) > 0) {
        $user = pg_fetch_assoc($result);
        $response['user_exists'] = true;
        $response['has_password'] = !empty($user['password']);
        $response['recruiter_id'] = $user['id'];
    }

    $response['success'] = true;
    $response['message'] = 'User check completed';

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Check user error: " . $e->getMessage());
}

echo json_encode($response);
?>