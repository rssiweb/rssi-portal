<?php
require_once __DIR__ . '/../bootstrap.php';
header('Content-Type: application/json');

header("Access-Control-Allow-Origin: http://localhost:8081");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With");
header("Access-Control-Max-Age: 3600");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);
$code = $data['code'] ?? '';

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Code is required']);
    exit;
}

// Start session to get session ID
// session_start();
$sessionId = session_id();

// Verify the code against your database
// This assumes you have a table storing the generated codes
$query = "SELECT * FROM cash_verification_codes 
          WHERE code = $1 AND created_at > NOW() - INTERVAL '1 hour' 
          AND is_verified = false";
$result = pg_query_params($con, $query, [$code]);

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

if (pg_num_rows($result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired code']);
    exit;
}

// Mark code as verified
$updateQuery = "UPDATE cash_verification_codes 
               SET is_verified = true, verified_at = NOW(), verified_session_id = $1
               WHERE code = $2";
$updateResult = pg_query_params($con, $updateQuery, [$sessionId, $code]);

if (!$updateResult) {
    echo json_encode(['success' => false, 'message' => 'Failed to update verification status']);
    exit;
}

// Update your admission record if needed
// $admissionUpdate = pg_query_params($con, 
//     "UPDATE admissions SET cash_verified = true WHERE session_id = $1",
//     [$sessionId]);

echo json_encode([
    'success' => true,
    'message' => 'Code verified successfully'
]);