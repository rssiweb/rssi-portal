<?php
require_once __DIR__ . '/../bootstrap.php';
include(__DIR__ . "../../util/email.php");

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

$response = ['success' => false, 'message' => ''];

try {
    $action = $_POST['action'] ?? '';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if ($action === 'request_reset') {
        $email = $_POST['email'] ?? '';
        
        if (empty($email)) {
            throw new Exception('Email is required');
        }

        // Check if email exists
        $query = "SELECT id, full_name FROM recruiters WHERE email = $1";
        $result = pg_query_params($con, $query, [$email]);

        if (!$result || pg_num_rows($result) === 0) {
            // Don't reveal if email exists for security
            $response['success'] = true;
            $response['message'] = 'If an account exists with this email, you will receive reset instructions.';
            echo json_encode($response);
            exit;
        }

        // Generate OTP and send email
        $otp = rand(100000, 999999);
        
        // Store OTP in session or database (simplified version)
        $_SESSION['reset_otp_' . $email] = $otp;
        $_SESSION['reset_otp_expiry_' . $email] = time() + 300; // 5 minutes

        // Send OTP email
        $user = pg_fetch_assoc($result);
        $emailData = [
            "user_name" => $user['full_name'],
            "user_email" => $email,
            "otp_code" => $otp,
            "timestamp" => date("d/m/Y g:i a")
        ];

        // You'll need to create a new email template for password reset
        $emailResult = sendEmail("password_reset_otp", $emailData, $email, false);

        $response['success'] = true;
        $response['message'] = 'OTP sent to your email';

    } elseif ($action === 'verify_and_reset') {
        $email = $_POST['email'] ?? '';
        $otp = $_POST['otp'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';

        if (empty($email) || empty($otp) || empty($newPassword)) {
            throw new Exception('All fields are required');
        }

        // Verify OTP
        if (!isset($_SESSION['reset_otp_' . $email]) || 
            $_SESSION['reset_otp_' . $email] != $otp ||
            !isset($_SESSION['reset_otp_expiry_' . $email]) ||
            $_SESSION['reset_otp_expiry_' . $email] < time()) {
            throw new Exception('Invalid or expired OTP');
        }

        if (strlen($newPassword) < 8) {
            throw new Exception('Password must be at least 8 characters long');
        }

        // Hash new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update password
        $updateQuery = "UPDATE recruiters SET password = $1 WHERE email = $2";
        $updateResult = pg_query_params($con, $updateQuery, [$hashedPassword, $email]);

        if (!$updateResult) {
            throw new Exception('Failed to reset password');
        }

        // Clear OTP session
        unset($_SESSION['reset_otp_' . $email]);
        unset($_SESSION['reset_otp_expiry_' . $email]);

        $response['success'] = true;
        $response['message'] = 'Password reset successfully';

    } else {
        throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>