<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Clear all 2FA session variables
unset($_SESSION['email_verification_code']);
unset($_SESSION['email_code_created_at']);
unset($_SESSION['email_verification_started']);
unset($_SESSION['email_verified']);
unset($_SESSION['auth_verification_started']);

header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'Session reset']);
?>