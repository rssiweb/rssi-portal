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

// Debug logging
error_log("verify_otp.php accessed at: " . date('Y-m-d H:i:s'));
error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);

$response = ['success' => false, 'message' => '', 'user_exists' => false];

try {
    // Check content type and get data accordingly
    $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

    if (strpos($contentType, 'application/json') !== false) {
        // JSON data
        $data = json_decode(file_get_contents('php://input'), true);
        $email = $data['email'] ?? '';
        $otp = $data['otp'] ?? '';
    } else {
        // Form data or URL encoded
        $email = $_POST['email'] ?? '';
        $otp = $_POST['otp'] ?? '';
    }

    error_log("Received - Email: $email, OTP: $otp");

    if (empty($email)) {
        throw new Exception('Email is required');
    }

    if (empty($otp)) {
        throw new Exception('OTP is required');
    }

    // Sanitize inputs
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    $otp = htmlspecialchars($otp, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $otp = preg_replace('/[^0-9]/', '', $otp); // Keep only digits

    // Verify OTP
    $query = "SELECT id, created_at, expires_at FROM otp_verification 
              WHERE email = $1 
              AND otp = $2 
              AND is_used = false";

    error_log("Query: $query with params: $email, $otp");

    $result = pg_query_params($con, $query, [$email, $otp]);

    if (!$result) {
        $error = pg_last_error($con);
        error_log("Database error: " . $error);
        throw new Exception('Database error: ' . $error);
    }

    if (pg_num_rows($result) > 0) {
        $row = pg_fetch_assoc($result);
        $otp_id = $row['id'];
        $expires_at = $row['expires_at'];
        $created_at = $row['created_at'];

        error_log("OTP found - ID: $otp_id, Created: $created_at, Expires: $expires_at");

        // Check if OTP is expired
        $current_time = date('Y-m-d H:i:s');
        if (strtotime($current_time) > strtotime($expires_at)) {
            error_log("OTP expired - Current: $current_time, Expires: $expires_at");

            // Delete expired OTP
            $deleteExpiredQuery = "DELETE FROM otp_verification WHERE id = $1";
            $deleteExpiredResult = pg_query_params($con, $deleteExpiredQuery, [$otp_id]);

            if ($deleteExpiredResult) {
                error_log("Expired OTP deleted successfully");
            }

            throw new Exception('OTP has expired');
        }

        // DELETE THE OTP AFTER SUCCESSFUL VERIFICATION (instead of marking as used)
        $deleteQuery = "DELETE FROM otp_verification WHERE id = $1";
        $deleteResult = pg_query_params($con, $deleteQuery, [$otp_id]);

        if (!$deleteResult) {
            $error = pg_last_error($con);
            error_log("Failed to delete OTP: " . $error);
            // Continue anyway since OTP was verified
        } else {
            error_log("OTP deleted successfully after verification");
        }

        // Check if user exists and is verified
        $userQuery = "SELECT id, full_name, company_name FROM recruiters WHERE email = $1 AND is_verified = true";
        $userResult = pg_query_params($con, $userQuery, [$email]);

        if (!$userResult) {
            $error = pg_last_error($con);
            error_log("Database error checking user: " . $error);
            throw new Exception('Database error checking user');
        }

        $userExists = pg_num_rows($userResult) > 0;

        // If user exists, get their details
        if ($userExists) {
            $userData = pg_fetch_assoc($userResult);
            $response['user_details'] = [
                'full_name' => $userData['full_name'],
                'company_name' => $userData['company_name']
            ];
            error_log("User exists: " . print_r($userData, true));
        } else {
            error_log("User does not exist or not verified for email: $email");
        }

        $response['success'] = true;
        $response['message'] = 'OTP verified successfully';
        $response['user_exists'] = $userExists;
    } else {
        // Check if OTP exists but is already used (or deleted)
        $usedQuery = "SELECT id FROM otp_verification WHERE email = $1 AND otp = $2";
        $usedResult = pg_query_params($con, $usedQuery, [$email, $otp]);

        if ($usedResult && pg_num_rows($usedResult) > 0) {
            error_log("OTP already used/deleted for email: $email");
            throw new Exception('This OTP has already been used or expired');
        } else {
            error_log("No OTP found for email: $email, OTP: $otp");
            throw new Exception('Invalid or expired OTP');
        }
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Exception in verify_otp.php: " . $e->getMessage());
}

error_log("Final response: " . json_encode($response));
echo json_encode($response);
