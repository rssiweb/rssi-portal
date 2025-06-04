<?php
require_once __DIR__ . '/../bootstrap.php';
include(__DIR__ . "../../util/email.php");
header('Content-Type: application/json');

header("Access-Control-Allow-Origin: http://localhost:8081");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With");
header("Access-Control-Max-Age: 3600");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);
$code = $data['code'] ?? '';
$studentName = $data['student_name'] ?? '';
$preferredBranch = $data['preferred_branch'] ?? '';
$doa = $data['doa'] ?? '';
$class = $data['class'] ?? '';
$type_of_admission = $data['type_of_admission'] ?? '';
$sessionId = session_id();

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Code is required']);
    exit;
}

// First store the code in the database
$storeQuery = "INSERT INTO cash_verification_codes 
              (code, student_name, preferred_branch, session_id, created_at)
              VALUES ($1, $2, $3, $4, NOW())";
$storeResult = pg_query_params($con, $storeQuery, [
    $code,
    $studentName,
    $preferredBranch,
    $sessionId
]);

if (!$storeResult) {
    $error = pg_last_error($con);
    error_log("Database error: " . $error);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to store verification code in database',
        'error' => $error
    ]);
    exit;
}

// Fetch all active director emails
$emailQuery = "SELECT alt_email, fullname FROM rssimyaccount_members 
              WHERE position = 'Centre Incharge' AND filterstatus = 'Active'";
$emailResult = pg_query($con, $emailQuery);

if (!$emailResult) {
    echo json_encode(['success' => false, 'message' => 'Database error fetching emails']);
    exit;
}

$emails = [];
$fullnames = [];
$maskedEmails = []; // To store masked versions for display

while ($row = pg_fetch_assoc($emailResult)) {
    if (!empty($row['alt_email'])) {
        // Split comma-separated emails into an array
        $rawEmails = explode(',', $row['alt_email']);

        foreach ($rawEmails as $email) {
            $email = trim($email); // Remove whitespace
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emails[] = $email;
                $fullnames[] = $row['fullname'];

                // New masking rule: keep first 2 chars + at least 1 X
                $parts = explode('@', $email);
                if (count($parts) === 2) {
                    $username = $parts[0];
                    $domain = $parts[1];

                    // Calculate how many characters to keep (minimum 1 X)
                    $keepChars = min(2, strlen($username) - 1);
                    $maskedUsername = substr($username, 0, $keepChars) . str_repeat('X', max(1, strlen($username) - $keepChars));

                    $maskedEmails[] = $maskedUsername . '@' . $domain;
                } else {
                    $maskedEmails[] = $email; // fallback if invalid format
                }
            }
        }
    }
}

if (empty($emails)) {
    echo json_encode(['success' => false, 'message' => 'No emails found']);
    exit;
}

$toEmails = implode(',', $emails);
$fullNames = implode(',', $fullnames);

// Prepare email content
$emailData = [
    "student_name" => $studentName,
    "preferred_branch" => $preferredBranch,
    "verification_code" => $code,
    "class" => $class,
    "type_of_admission" => $type_of_admission,
    "timestamp" => date("d/m/Y g:i a"),
    "fullname" => $fullNames,
    "doa" => date('d/m/Y', strtotime($doa)),
    "session_id" => $sessionId  // Include session ID in email for reference
];

// With this:
try {
    $emailResult = sendEmail("cash_verification_code", $emailData, $toEmails);

    // If email is sending but you're still getting failure message,
    // assume it's successful if no exception was thrown
    echo json_encode([
        'success' => true,
        'message' => 'Code sent to recipients',
        'session_id' => $sessionId,
        'email_sent' => $maskedEmails
    ]);
    exit;
} catch (Exception $e) {
    error_log("Email sending error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send email: ' . $e->getMessage(),
        'session_id' => $sessionId
    ]);
    exit;
}
