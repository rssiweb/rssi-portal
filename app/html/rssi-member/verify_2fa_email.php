<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

header('Content-Type: application/json');

if (!isLoggedIn("aid")) {
    echo json_encode(['success' => false, 'message' => 'You are not logged in.']);
    exit;
}

// Get JSON data from fetch
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['email_otp'])) {
    echo json_encode(['success' => false, 'message' => 'Email OTP is required.']);
    exit;
}

$emailOtpEntered = trim($data['email_otp']);
$user = $_SESSION['aid'];

// Fetch email code from DB
$query = "SELECT twofa_email_code, twofa_email_code_created_at FROM rssimyaccount_members WHERE email=$1";
$stmt = pg_prepare($con, "get_email_code", $query);
$res = pg_execute($con, "get_email_code", [$user]);
$row = pg_fetch_assoc($res);

if (!$row || empty($row['twofa_email_code'])) {
    echo json_encode(['success' => false, 'message' => 'Email verification code not found.']);
    exit;
}

// Check if code is expired (10 minutes)
$codeCreated = strtotime($row['twofa_email_code_created_at']);
if (time() - $codeCreated > 600) {
    echo json_encode(['success' => false, 'message' => 'Email verification code has expired.']);
    exit;
}

// Verify email OTP
if (password_verify($emailOtpEntered, $row['twofa_email_code'])) {
    // Mark email as verified in session
    $_SESSION['email_verified'] = true;
    $_SESSION['email_verification_started'] = time();

    echo json_encode(['success' => true, 'message' => 'Email verification successful!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid email verification code.']);
}
