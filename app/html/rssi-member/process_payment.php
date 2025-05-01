<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

// Set JSON header for all responses
header('Content-Type: application/json');

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get and validate all required parameters
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

// Function to build redirect URL with proper parameter handling
function buildRedirectUrl($params) {
    $queryParams = [];
    
    foreach ($params as $key => $value) {
        if (is_array($value)) {
            foreach ($value as $item) {
                $queryParams[] = urlencode($key.'[]') . '=' . urlencode($item);
            }
        } elseif ($value !== '' && $value !== null) {
            $queryParams[] = urlencode($key) . '=' . urlencode($value);
        }
    }
    
    return 'fee_collection.php?' . implode('&', $queryParams);
}

// Validate required fields
$requiredFields = ['student_id', 'month', 'year', 'payment_type', 'collected_by'];
foreach ($requiredFields as $field) {
    if (empty($_POST[$field])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => "Required field '$field' is missing",
            'redirect' => 'fee_collection.php?' . http_build_query($redirectParams)
        ]);
        exit;
    }
}

// Sanitize inputs
$studentId = pg_escape_string($con, $_POST['student_id']);
$month = pg_escape_string($con, $_POST['month']);
$year = pg_escape_string($con, $_POST['year']);
$paymentType = pg_escape_string($con, $_POST['payment_type']);
$transactionId = !empty($_POST['transaction_id']) ? pg_escape_string($con, $_POST['transaction_id']) : null;
$paymentDate = !empty($_POST['payment_date']) ? $_POST['payment_date'] : date('Y-m-d');
$collectedBy = pg_escape_string($con, $_POST['collected_by']);
$notes = !empty($_POST['notes']) ? pg_escape_string($con, $_POST['notes']) : null;
$paymentAmounts = $_POST['payment_amounts'] ?? [];

// Validate payment date
if (!DateTime::createFromFormat('Y-m-d', $paymentDate)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid payment date format (YYYY-MM-DD required)',
        'redirect' => 'fee_collection.php?' . http_build_query($redirectParams)
    ]);
    exit;
}

// Start transaction
if (!pg_query($con, "BEGIN")) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to start database transaction',
        'redirect' => 'fee_collection.php?' . http_build_query($redirectParams)
    ]);
    exit;
}

try {
    // Get monthly fee category ID
    $monthlyFeeQuery = "SELECT id FROM fee_categories WHERE category_name = 'Monthly Fee'";
    $monthlyFeeResult = pg_query($con, $monthlyFeeQuery);
    $monthlyFeeCategoryId = pg_fetch_assoc($monthlyFeeResult)['id'] ?? null;

    if (!$monthlyFeeCategoryId) {
        throw new Exception("Monthly Fee category not found");
    }

    // Get previous carry forward amounts
    $carryForwardQuery = "SELECT COALESCE(SUM(carry_forward), 0) as total_carry_forward
                         FROM fee_payments 
                         WHERE student_id = $1
                         AND category_id = $2
                         AND (academic_year < $3 OR (academic_year = $3 AND month != $4))";
    $carryForwardResult = pg_query_params($con, $carryForwardQuery, 
        [$studentId, $monthlyFeeCategoryId, $year, $month]);
    $carryForward = (float)(pg_fetch_assoc($carryForwardResult)['total_carry_forward'] ?? 0);

    // Get current monthly fee amount
    $monthlyFeeQuery = "SELECT fs.amount 
                       FROM fee_structure fs
                       JOIN rssimyprofile_student s ON fs.class = s.class
                       WHERE fs.category_id = $1
                       AND s.student_id = $2
                       AND $3 BETWEEN fs.effective_from AND COALESCE(fs.effective_until, '9999-12-31')";
    $monthlyFeeResult = pg_query_params($con, $monthlyFeeQuery, 
        [$monthlyFeeCategoryId, $studentId, "$year-$month-01"]);
    $monthlyFeeAmount = (float)(pg_fetch_assoc($monthlyFeeResult)['amount'] ?? 0);

    // Calculate adjusted monthly fee with carry forward
    $adjustedMonthlyFee = $monthlyFeeAmount + $carryForward;

    // Prepare payment insert statement
    $insertQuery = "INSERT INTO fee_payments 
                   (student_id, academic_year, month, category_id, amount, 
                    due_amount, carry_forward, payment_type, transaction_id, 
                    collected_by, collection_date, notes)
                   VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12)";
    $stmt = pg_prepare($con, "insert_payment", $insertQuery);
    if (!$stmt) {
        throw new Exception("Failed to prepare payment insert statement");
    }

    // Process each category payment
    $processedPayments = 0;
    foreach ($paymentAmounts as $categoryId => $amount) {
        $amount = (float)$amount;
        if ($amount <= 0) continue;
        
        $dueAfterPayment = 0;
        $currentCarryForward = 0;

        // Handle monthly fee specially
        if ($categoryId == $monthlyFeeCategoryId) {
            $dueAfterPayment = max($adjustedMonthlyFee - $amount, 0);
            $currentCarryForward = $adjustedMonthlyFee - $amount;
        } 
        // Handle previous dues
        elseif ($categoryId === 'previous_due') {
            $dueAfterPayment = max($carryForward - $amount, 0);
            $currentCarryForward = -$amount;
        }
        // Regular categories
        else {
            $categoryQuery = "SELECT amount FROM fee_structure fs
                            JOIN fee_categories fc ON fs.category_id = fc.id
                            WHERE fc.id = $1 AND fs.class = 
                            (SELECT class FROM rssimyprofile_student WHERE student_id = $2)";
            $categoryResult = pg_query_params($con, $categoryQuery, [$categoryId, $studentId]);
            $categoryAmount = (float)(pg_fetch_assoc($categoryResult)['amount'] ?? 0);
            $dueAfterPayment = max($categoryAmount - $amount, 0);
        }
        
        $params = [
            $studentId,
            $year,
            $month,
            ($categoryId === 'previous_due') ? null : $categoryId,
            $amount,
            $dueAfterPayment,
            ($categoryId == $monthlyFeeCategoryId || $categoryId === 'previous_due') ? $currentCarryForward : 0,
            $paymentType,
            $transactionId,
            $collectedBy,
            $paymentDate,
            $notes
        ];
        
        $result = pg_execute($con, "insert_payment", $params);
        if (!$result) {
            throw new Exception("Failed to record payment for category $categoryId: " . pg_last_error($con));
        }
        $processedPayments++;
    }

    if ($processedPayments === 0) {
        throw new Exception("No valid payment amounts provided");
    }

    if (!pg_query($con, "COMMIT")) {
        throw new Exception("Failed to commit transaction");
    }

    // Store success message in session
    $_SESSION['success_message'] = "Payment recorded successfully";
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Payment processed successfully',
        'redirect' => buildRedirectUrl($redirectParams)
    ]);
    
} catch (Exception $e) {
    pg_query($con, "ROLLBACK");
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error processing payment: ' . $e->getMessage(),
        'redirect' => buildRedirectUrl($redirectParams)
    ]);
}