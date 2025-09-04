<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("../../util/email.php");

header('Content-Type: application/json');

if (!isLoggedIn("aid")) {
    echo json_encode(['success' => false, 'message' => 'You are not logged in.']);
    exit;
}

$user = $_SESSION['aid'];

// Generate new email code
$email_code = rand(100000, 999999);

// Hash the code before storing
$hashed_code = password_hash($email_code, PASSWORD_DEFAULT);

// Store in database
$update = "UPDATE rssimyaccount_members SET twofa_email_code=$1, twofa_email_code_created_at=NOW() WHERE email=$2";
$stmt = pg_prepare($con, "update_email_code", $update);
$result = pg_execute($con, "update_email_code", [$hashed_code, $user]);

if ($result) {
    // Store in session
    $_SESSION['email_verification_code'] = $email_code;
    $_SESSION['email_code_created_at'] = time();

    // Get user email
    $query = "SELECT email FROM rssimyaccount_members WHERE email=$1";
    $stmt = pg_prepare($con, "get_user_email", $query);
    $res = pg_execute($con, "get_user_email", [$user]);
    $row = pg_fetch_assoc($res);

    // Send email with the code
    sendEmail("2fa_verification", [
        "verification_code" => $email_code,
        "user_email" => $row['email']
    ], [$row['email']], false);

    echo json_encode(['success' => true, 'message' => 'Verification code sent successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to generate new verification code.']);
}
