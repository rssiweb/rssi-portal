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

// Get and validate required parameters from both POST and GET
$redirectParams = [
    'status' => $_REQUEST['status'] ?? '',
    'month' => $_REQUEST['month'] ?? date('F'),
    'year' => $_REQUEST['year'] ?? date('Y'),
    'class' => $_REQUEST['class'] ?? ''
];

// Input validation
$requiredFields = ['student_id', 'reason', 'effective_from', 'category_ids', 'concession_amounts'];
foreach ($requiredFields as $field) {
    if (empty($_POST[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
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
    echo json_encode(['success' => false, 'message' => 'Invalid effective_from date format']);
    exit;
}

if ($effectiveUntil && !DateTime::createFromFormat('Y-m-d', $effectiveUntil)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid effective_until date format']);
    exit;
}

// Start transaction
if (!pg_query($con, "BEGIN")) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to start transaction']);
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
    
    // Build redirect URL with all parameters
    $redirectUrl = 'fee_collection.php?' . http_build_query(array_filter([
        'status' => $redirectParams['status'],
        'month' => $redirectParams['month'],
        'year' => $redirectParams['year'],
        'class' => $redirectParams['class']
    ]));
    
    // Return success response with redirect URL
    echo json_encode([
        'success' => true,
        'message' => 'Concession processed successfully',
        'redirect' => $redirectUrl
    ]);
    
} catch (Exception $e) {
    pg_query($con, "ROLLBACK");
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error adding concession: ' . $e->getMessage()
    ]);
}