<?php
require_once __DIR__ . "/../../bootstrap.php";
require __DIR__ . '/../vendor/autoload.php';
include("../../util/login_util.php");
include("../../util/email.php");

use OTPHP\TOTP;

if (!isLoggedIn("aid")) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user = $_SESSION['aid']; // logged in user email or ID

// Fetch user details from DB
$query = "SELECT twofa_secret, twofa_enabled, email, fullname FROM rssimyaccount_members WHERE email=$1";
$stmt = pg_prepare($con, "get_user_2fa", $query);
$res = pg_execute($con, "get_user_2fa", [$user]);
$row = pg_fetch_assoc($res);

// If 2FA is already enabled, don't proceed
if ($row['twofa_enabled'] === 't') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Two-factor authentication is already enabled on your account']);
    exit;
}

// Check if we need to generate a new email code (not generated in the last 2 minutes)
$needsNewEmailCode = true;
if (isset($_SESSION['email_verification_started']) && time() - $_SESSION['email_verification_started'] < 120) {
    $needsNewEmailCode = false;
}

// Generate email verification code if needed
if ($needsNewEmailCode) {
    // Clear any previous verification states
    unset($_SESSION['email_verified']);
    unset($_SESSION['auth_verification_started']);

    // Generate 6-digit code
    $email_code = rand(100000, 999999);

    // Store in session
    $_SESSION['email_verification_code'] = $email_code;
    $_SESSION['email_code_created_at'] = time();
    $_SESSION['email_verification_started'] = time();

    // Store in database
    $update = "UPDATE rssimyaccount_members 
               SET twofa_email_code = $1, twofa_email_code_created_at = NOW() 
               WHERE email = $2";
    $stmt = pg_prepare($con, "update_email_code", $update);
    $result = pg_execute($con, "update_email_code", [$email_code, $user]);

    if ($result) {
        // Prepare data for email template
        $user_name = $row['fullname'];
        $email_data = [
            "verification_code" => $email_code,
            "user_email" => $row['email'],
            "user_name" => $user_name,
        ];

        try {
            // Send email with the code using your template system
            sendEmail("2fa_verification", $email_data, [$row['email']], false);

            // If we reach here, the email was sent successfully
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Verification code sent to your email address'
            ]);
            exit;
        } catch (Exception $e) {
            // Clean up session if email failed
            unset($_SESSION['email_verification_code']);
            unset($_SESSION['email_code_created_at']);
            unset($_SESSION['email_verification_started']);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Failed to send verification email. Please try again.'
            ]);
            exit;
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Failed to generate verification code. Please try again.'
        ]);
        exit;
    }
} else {
    // Email code was recently generated, no need to send again
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Verification code already sent. Please check your email.'
    ]);
    exit;
}
