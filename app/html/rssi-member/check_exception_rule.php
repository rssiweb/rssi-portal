<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("schedule_helper.php");

header('Content-Type: application/json');
// Never serve a cached answer: every call must hit live data
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (!isLoggedIn("aid")) {
    http_response_code(401);
    echo json_encode(['valid' => false, 'code' => 'unauthorized', 'message' => 'Session expired. Please log in again.']);
    exit;
}

validation(); // sets $associatenumber, as on your other pages

// Always validates for the logged-in user (an associate number is never taken from the request)
$result = validateExceptionRequest(
    $con,
    $associatenumber,
    $_GET['exception_type'] ?? '',
    $_GET['sub_type'] ?? '',
    $_GET['start'] ?? null,
    $_GET['end'] ?? null
);

echo json_encode($result);
