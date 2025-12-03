<?php
require_once __DIR__ . '/../bootstrap.php';
include(__DIR__ . "/../util/email.php");

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
error_log("send_otp.php accessed at: " . date('Y-m-d H:i:s'));

$response = ['success' => false, 'message' => ''];

try {
    // Check content type and get data accordingly
    $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
    
    if (strpos($contentType, 'application/json') !== false) {
        // JSON data
        $data = json_decode(file_get_contents('php://input'), true);
        $email = $data['email'] ?? '';
    } else {
        // Form data or URL encoded
        $email = $_POST['email'] ?? '';
    }
    
    error_log("Received email: $email");
    
    if (empty($email)) {
        throw new Exception('Email is required');
    }
    
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    // Check if user exists
    $checkQuery = "SELECT id, full_name FROM recruiters WHERE email = $1";
    $checkResult = pg_query_params($con, $checkQuery, [$email]);
    
    $userName = 'User';
    $userExists = false;
    
    if ($checkResult && pg_num_rows($checkResult) > 0) {
        $userExists = true;
        $userData = pg_fetch_assoc($checkResult);
        $userName = $userData['full_name'] ?? 'User';
        error_log("Existing user found: " . $userName);
    } else {
        error_log("New user: $email");
    }
    
    // Generate 6-digit OTP
    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));
    
    error_log("Generated OTP: $otp, Expires: $expires_at");
    
    // Check if OTP already exists for this email
    $query = "SELECT id FROM otp_verification WHERE email = $1 AND expires_at > NOW() AND is_used = false";
    $result = pg_query_params($con, $query, [$email]);
    
    if ($result && pg_num_rows($result) > 0) {
        // Update existing OTP
        $query = "UPDATE otp_verification SET otp = $1, expires_at = $2, created_at = NOW() WHERE email = $3";
        $result = pg_query_params($con, $query, [$otp, $expires_at, $email]);
        error_log("Updated existing OTP");
    } else {
        // Insert new OTP
        $query = "INSERT INTO otp_verification (email, otp, expires_at) VALUES ($1, $2, $3)";
        $result = pg_query_params($con, $query, [$email, $otp, $expires_at]);
        error_log("Inserted new OTP");
    }
    
    if (!$result) {
        $error = pg_last_error($con);
        error_log("Database error: " . $error);
        throw new Exception('Failed to generate OTP');
    }
    
    // Prepare email content
    $emailData = [
        "user_name" => $userName,
        "user_email" => $email,
        "otp_code" => $otp,
        "expiry_time" => "5 minutes",
        "timestamp" => date("d/m/Y g:i a"),
        "purpose" => "Job Post Verification",
        "user_type" => $userExists ? "Existing User" : "New User"
    ];
    
    // Send OTP email
    try {
        $emailResult = sendEmail("otp_verification_job", $emailData, $email, false);
        error_log("Email sent successfully to: $email");
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        // Don't throw exception for email failure, just log it
    }
    
    // Log for debugging (remove in production)
    error_log("OTP for $email: $otp (valid until $expires_at)");
    
    $response['success'] = true;
    $response['message'] = 'OTP sent to your email';
    $response['user_exists'] = $userExists;
    $response['debug'] = ['email_received' => $email]; // For debugging
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Exception in send_otp.php: " . $e->getMessage());
}

error_log("Final response: " . json_encode($response));
echo json_encode($response);
?>