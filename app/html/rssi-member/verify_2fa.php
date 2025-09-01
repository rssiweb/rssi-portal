<?php
require_once __DIR__ . "/../../bootstrap.php";
require __DIR__ . '/../vendor/autoload.php';
include("../../util/login_util.php");

use OTPHP\TOTP;

header('Content-Type: application/json');

if (!isLoggedIn("aid")) {
    echo json_encode(['success' => false, 'message' => 'You are not logged in.']);
    exit;
}

// Get JSON data from fetch
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['otp'])) {
    echo json_encode(['success' => false, 'message' => 'OTP is required.']);
    exit;
}

$otpEntered = trim($data['otp']);
$user = $_SESSION['aid'];

// Fetch user secret from DB
$query = "SELECT twofa_secret FROM rssimyaccount_members WHERE email=$1";
$stmt = pg_prepare($con, "get_user_secret", $query);
$res = pg_execute($con, "get_user_secret", [$user]);
$row = pg_fetch_assoc($res);

if (!$row || empty($row['twofa_secret'])) {
    echo json_encode(['success' => false, 'message' => '2FA secret not found.']);
    exit;
}

$secret = $row['twofa_secret'];
$totp = TOTP::create($secret);

// Verify OTP
if ($totp->verify($otpEntered)) {
    // Enable 2FA for this user
    $update = "UPDATE rssimyaccount_members SET twofa_enabled = TRUE WHERE email=$1";
    $stmt2 = pg_prepare($con, "enable_2fa", $update);
    pg_execute($con, "enable_2fa", [$user]);

    echo json_encode(['success' => true, 'message' => 'OTP verified successfully! 2FA enabled.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid OTP. Please try again.']);
}
