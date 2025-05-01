<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

// Set default response headers
header('Content-Type: application/json');

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Function to build redirect URL with proper parameter handling
function buildRedirectUrl($params)
{
    $queryParams = [];

    foreach ($params as $key => $value) {
        if (is_array($value)) {
            foreach ($value as $item) {
                $queryParams[] = urlencode($key . '[]') . '=' . urlencode($item);
            }
        } elseif ($value !== '' && $value !== null) {
            $queryParams[] = urlencode($key) . '=' . urlencode($value);
        }
    }

    return 'fee_collection.php?' . implode('&', $queryParams);
}

// Get and validate all parameters
$redirectParams = [
    'status' => $_REQUEST['status'] ?? 'Active',
    'month' => $_REQUEST['month'] ?? date('F'),
    'year' => $_REQUEST['year'] ?? date('Y'),
    'search_term' => $_REQUEST['search_term'] ?? ''
];

// Handle class parameter (can be array or string)
if (isset($_REQUEST['class']) && is_array($_REQUEST['class'])) {
    $redirectParams['class'] = $_REQUEST['class'];
} elseif (isset($_REQUEST['class']) && !empty($_REQUEST['class'])) {
    $redirectParams['class'] = [$_REQUEST['class']];
} else {
    $redirectParams['class'] = [];
}

// Input validation
$requiredFields = ['student_id', 'reason', 'effective_from', 'category_ids', 'concession_amounts'];
foreach ($requiredFields as $field) {
    if (empty($_POST[$field])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => "Missing required field: $field",
            'redirect' => buildRedirectUrl($redirectParams)
        ]);
        exit;
    }
}

// Extract and sanitize inputs
$studentId = pg_escape_string($con, $_POST['student_id']);
$reason = pg_escape_string($con, $_POST['reason']);
$effectiveFrom = $_POST['effective_from'];
$effectiveUntil = !empty($_POST['effective_until']) ? $_POST['effective_until'] : null;
$categoryIds = $_POST['category_ids'];
$concessionAmounts = $_POST['concession_amounts'];

// Validate date formats
if (!DateTime::createFromFormat('Y-m-d', $effectiveFrom)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid effective_from date format',
        'redirect' => buildRedirectUrl($redirectParams)
    ]);
    exit;
}

if ($effectiveUntil && !DateTime::createFromFormat('Y-m-d', $effectiveUntil)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid effective_until date format',
        'redirect' => buildRedirectUrl($redirectParams)
    ]);
    exit;
}

// Start transaction
if (!pg_query($con, "BEGIN")) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to start transaction',
        'redirect' => buildRedirectUrl($redirectParams)
    ]);
    exit;
}

try {
    $insertedCount = 0;
    $insertQuery = "INSERT INTO student_concessions 
                   (student_id, category_id, concession_amount, reason, effective_from, effective_until, created_by)
                   VALUES ($1, $2, $3, $4, $5, $6, $7)";

    // Prepare statement
    $stmt = pg_prepare($con, "insert_concession", $insertQuery);
    if (!$stmt) {
        throw new Exception("Failed to prepare statement");
    }

    // Process each category concession
    foreach ($categoryIds as $index => $categoryId) {
        $amount = (float)($concessionAmounts[$index] ?? 0);
        if ($amount <= 0) continue;

        $params = [
            $studentId,
            $categoryId,
            $amount,
            $reason,
            $effectiveFrom,
            $effectiveUntil,
            $associatenumber
        ];

        $result = pg_execute($con, "insert_concession", $params);
        if (!$result) {
            throw new Exception("Failed to insert concession for category $categoryId");
        }
        $insertedCount++;
    }

    if ($insertedCount === 0) {
        throw new Exception("No valid concession amounts provided");
    }

    if (!pg_query($con, "COMMIT")) {
        throw new Exception("Failed to commit transaction");
    }

    // Store success message
    $_SESSION['success_message'] = "Concession added successfully";

    // Return success response with redirect URL
    echo json_encode([
        'success' => true,
        'message' => 'Concession processed successfully',
        'redirect' => buildRedirectUrl($redirectParams)
    ]);
} catch (Exception $e) {
    pg_query($con, "ROLLBACK");
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error adding concession: ' . $e->getMessage(),
        'redirect' => buildRedirectUrl($redirectParams)
    ]);
}
