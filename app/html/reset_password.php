<?php
require_once __DIR__ . '/../bootstrap.php';
include(__DIR__ . "../../util/email.php");

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With");
header("Access-Control-Max-Age: 3600");

// Check if session is already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$response = ['success' => false, 'message' => ''];

try {
    // Check content type and get data accordingly
    $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

    if (strpos($contentType, 'application/json') !== false) {
        // JSON data
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';
        $email = $data['email'] ?? '';
        $otp = $data['otp'] ?? '';
        $newPassword = $data['new_password'] ?? '';
    } else {
        // Form data or URL encoded
        $action = $_POST['action'] ?? '';
        $email = $_POST['email'] ?? '';
        $otp = $_POST['otp'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if ($action === 'request_reset') {
        if (empty($email)) {
            throw new Exception('Email is required');
        }

        // Check if email exists
        $query = "SELECT id, full_name FROM recruiters WHERE email = $1";
        $result = pg_query_params($con, $query, [$email]);

        if (!$result) {
            $error = pg_last_error($con);
            error_log("Database error checking email: " . $error);
            throw new Exception('Database error. Please try again.');
        }

        if (pg_num_rows($result) === 0) {
            // Don't reveal if email exists for security
            $response['success'] = true;
            $response['message'] = 'If an account exists with this email, you will receive reset instructions.';
            echo json_encode($response);
            exit;
        }

        // Generate OTP
        $otp = rand(100000, 999999);

        // Store OTP in database instead of session
        $expires_at = date('Y-m-d H:i:s', time() + 300); // 5 minutes from now

        // First, delete any existing OTPs for this email (cleanup)
        $cleanupQuery = "DELETE FROM otp_verification WHERE email = $1";
        pg_query_params($con, $cleanupQuery, [$email]);

        // Insert new OTP
        $insertQuery = "INSERT INTO otp_verification (email, otp, expires_at) 
                        VALUES ($1, $2, $3)";
        $insertResult = pg_query_params($con, $insertQuery, [$email, $otp, $expires_at]);

        if (!$insertResult) {
            $error = pg_last_error($con);
            error_log("Database error inserting OTP: " . $error);
            throw new Exception('Failed to generate OTP. Please try again.');
        }

        // Send OTP email
        $user = pg_fetch_assoc($result);
        $emailData = [
            "receiver" => $user['full_name'],
            "user_email" => $email,
            "otp" => $otp,
            "timestamp" => date("d/m/Y g:i a"),
            "process" => "Password Reset for Recruiters' Portal"
        ];

        $emailResult = sendEmail("otp", $emailData, $email, false);

        // Debug logging
        error_log("Password reset OTP sent to: $email, OTP: $otp");

        $response['success'] = true;
        $response['message'] = 'OTP sent to your email';
    } elseif ($action === 'verify_and_reset') {
        if (empty($email) || empty($otp) || empty($newPassword)) {
            throw new Exception('All fields are required');
        }

        // Verify OTP from database
        $verifyQuery = "SELECT id, expires_at FROM otp_verification 
                        WHERE email = $1 AND otp = $2 
                        AND is_used = false";
        $verifyResult = pg_query_params($con, $verifyQuery, [$email, $otp]);

        if (!$verifyResult) {
            $error = pg_last_error($con);
            error_log("Database error verifying OTP: " . $error);
            throw new Exception('Database error. Please try again.');
        }

        if (pg_num_rows($verifyResult) === 0) {
            throw new Exception('Invalid or expired OTP. Please request a new OTP.');
        }

        $row = pg_fetch_assoc($verifyResult);
        $otp_id = $row['id'];

        // Check if OTP is expired
        if (strtotime($row['expires_at']) < time()) {
            // Mark as expired
            $expireQuery = "UPDATE otp_verification SET is_used = true WHERE id = $1";
            pg_query_params($con, $expireQuery, [$otp_id]);
            throw new Exception('OTP has expired. Please request a new OTP.');
        }

        // Mark OTP as used
        $updateOtpQuery = "UPDATE otp_verification SET is_used = true WHERE id = $1";
        $updateOtpResult = pg_query_params($con, $updateOtpQuery, [$otp_id]);

        if (!$updateOtpResult) {
            $error = pg_last_error($con);
            error_log("Database error updating OTP status: " . $error);
            // Continue anyway since OTP was verified
        }

        // Validate password strength
        if (strlen($newPassword) < 8) {
            throw new Exception('Password must be at least 8 characters long');
        }

        // Hash new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update password in recruiters table
        $updateQuery = "UPDATE recruiters SET password = $1 WHERE email = $2";
        $updateResult = pg_query_params($con, $updateQuery, [$hashedPassword, $email]);

        if (!$updateResult) {
            $error = pg_last_error($con);
            error_log("Database error updating password: " . $error);
            throw new Exception('Failed to reset password. Please try again.');
        }

        // Also delete the OTP record to clean up
        $deleteQuery = "DELETE FROM otp_verification WHERE id = $1";
        pg_query_params($con, $deleteQuery, [$otp_id]);

        error_log("Password reset successful for: $email");

        $response['success'] = true;
        $response['message'] = 'Password reset successfully';
    } else {
        throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Reset password error: " . $e->getMessage());
}

echo json_encode($response);
