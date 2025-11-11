<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("../../util/drive.php");
// Set default response headers
header('Content-Type: application/json');

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Function to clean and unique array parameters
function cleanArrayParam($param)
{
    if (is_array($param)) {
        // Remove empty values and get unique values
        return array_unique(array_filter($param, function ($value) {
            return $value !== '' && $value !== null;
        }));
    }
    return $param;
}

// Get and validate all required parameters - with proper array handling
$redirectParams = [
    'status' => $_REQUEST['status'] ?? 'Active',
    'month_year' => $_REQUEST['month_year'] ?? date('Y-m'), // e.g., 2025-02
    'search_term' => $_REQUEST['search_term'] ?? ''
];

// Handle category parameter (can be array or string)
if (isset($_REQUEST['category'])) {
    $redirectParams['category'] = is_array($_REQUEST['category'])
        ? cleanArrayParam($_REQUEST['category'])
        : [$_REQUEST['category']];
} else {
    $redirectParams['category'] = [];
}

// Handle class parameter (can be array or string)
if (isset($_REQUEST['class'])) {
    $redirectParams['class'] = is_array($_REQUEST['class'])
        ? cleanArrayParam($_REQUEST['class'])
        : [$_REQUEST['class']];
} else {
    $redirectParams['class'] = [];
}

// Handle student_ids parameter - ensure unique values
if (isset($_REQUEST['student_ids'])) {
    $redirectParams['student_ids'] = is_array($_REQUEST['student_ids'])
        ? cleanArrayParam($_REQUEST['student_ids'])
        : [$_REQUEST['student_ids']];
} else {
    $redirectParams['student_ids'] = [];
}

// Improved function to build redirect URL with proper parameter handling
function buildRedirectUrl($params)
{
    $queryParts = [];

    foreach ($params as $key => $value) {
        if (is_array($value)) {
            // Handle array parameters (like student_ids[])
            foreach (array_unique($value) as $item) {
                if ($item !== '' && $item !== null) {
                    $queryParts[] = urlencode($key . '[]') . '=' . urlencode($item);
                }
            }
        } elseif ($value !== '' && $value !== null) {
            $queryParts[] = urlencode($key) . '=' . urlencode($value);
        }
    }

    return 'fee_collection.php?' . implode('&', $queryParts);
}

// Input validation
$requiredFields = ['student_id', 'concession_category', 'reason', 'effective_from', 'category_ids', 'concession_amounts'];
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
$concession_category = pg_escape_string($con, $_POST['concession_category']);
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

// Add this after your input validation section and before the transaction start

// Handle file upload
$doclink = null;
if (isset($_FILES['supporting_document']) && $_FILES['supporting_document']['error'] === UPLOAD_ERR_OK) {
    $uploadedFile = $_FILES['supporting_document'];

    // Generate unique filename and upload to Google Drive
    $filename = "concession_" . $studentId . "_" . time();
    $parent = '1oG6jwPuVpjbRPof6gHZvVwmjw3iYscPj'; // Use your concession documents folder ID
    $doclink = uploadeToDrive($uploadedFile, $parent, $filename);
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
    // Replace your current insert query with this:
    $insertQuery = "INSERT INTO student_concessions 
               (student_id, category_id, concession_amount, reason, effective_from, effective_until, created_by, concession_category, supporting_document)
               VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)";

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
            $associatenumber,
            $concession_category,
            $doclink  // Add this parameter - will be null if no file uploaded
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
