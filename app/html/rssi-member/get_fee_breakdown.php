<?php
require_once __DIR__ . "/../../bootstrap.php";

$studentId = $_GET['student_id'] ?? '';
$month = $_GET['month'] ?? date('F');
$year = $_GET['year'] ?? date('Y');

// Convert month name to number and get date range
$monthNumber = date('m', strtotime("$month 1, $year"));
$firstDayOfMonth = "$year-$monthNumber-01";

// Get student info
$studentQuery = "SELECT class, type_of_admission, doa FROM rssimyprofile_student WHERE student_id = $1";
$studentResult = pg_query_params($con, $studentQuery, [$studentId]);
$student = pg_fetch_assoc($studentResult);

if (!$student) {
    http_response_code(404);
    die(json_encode(['error' => 'Student not found']));
}

// Determine if admission fee should be shown
$admissionDate = strtotime($student['doa']);
$admissionMonth = date('m', $admissionDate);
$admissionYear = date('Y', $admissionDate);
$showAdmissionFee = ($monthNumber == '04') || ($monthNumber == $admissionMonth && $year == $admissionYear);

// Determine student type
$studentType = in_array($student['type_of_admission'], ['New Admission', 'Transfer Admission']) ? 'New' : 'Existing';

// Get applicable fees
$feeQuery = "SELECT fc.id, fc.category_name, 
                    COALESCE(fs.amount, 0) as amount, 
                    fc.fee_type
             FROM fee_categories fc
             LEFT JOIN fee_structure fs ON (
                 fs.category_id = fc.id
                 AND fs.class = $1
                 AND fs.student_type = $2
                 AND $3 BETWEEN fs.effective_from AND COALESCE(fs.effective_until, '9999-12-31')
             )
             WHERE fc.is_active = TRUE
             AND fc.is_listed = TRUE
             ORDER BY fc.id";
$feeResult = pg_query_params($con, $feeQuery, [$student['class'], $studentType, $firstDayOfMonth]);
$feeItems = pg_fetch_all($feeResult) ?? [];

// Get previous payments for this month
$paymentQuery = "SELECT category_id, SUM(amount) as paid_amount 
                 FROM fee_payments 
                 WHERE student_id = $1 
                 AND month = $2 
                 AND academic_year = $3
                 GROUP BY category_id";
$paymentResult = pg_query_params($con, $paymentQuery, [$studentId, $month, $year]);
$payments = pg_fetch_all($paymentResult) ?? [];

// Prepare response
$response = [
    'categories' => [],
    'show_admission_fee' => $showAdmissionFee
];

foreach ($feeItems as $fee) {
    // Apply logic for Admission Fee
    if ($fee['category_name'] == 'Admission Fee' && !$showAdmissionFee) {
        $fee['amount'] = 0;
    }

    // Get paid amount
    $paidAmount = 0;
    foreach ($payments as $payment) {
        if ($payment['category_id'] == $fee['id']) {
            $paidAmount = $payment['paid_amount'];
            break;
        }
    }

    // Get concession amount
    $concessionQuery = "SELECT COALESCE(SUM(concession_amount), 0) as concession_amount
                        FROM student_concessions
                        WHERE student_id = $1
                        AND category_id = $2
                        AND $3 BETWEEN effective_from AND COALESCE(effective_until, '9999-12-31')";
    $concessionResult = pg_query_params($con, $concessionQuery, [$studentId, $fee['id'], $firstDayOfMonth]);
    $concession = pg_fetch_assoc($concessionResult);
    $concessionAmount = $concession['concession_amount'] ?? 0;

    $netAmount = $fee['amount'] - $concessionAmount;
    $dueAmount = max($netAmount - $paidAmount, 0);

    // Include category in response
    $response['categories'][] = [
        'id' => $fee['id'],
        'name' => $fee['category_name'],
        'amount' => (float)$fee['amount'],
        'concession' => (float)$concessionAmount,
        'paid' => (float)$paidAmount,
        'due' => (float)$dueAmount,
        'fee_type' => $fee['fee_type'],
        'can_pay' => true
    ];
}

header('Content-Type: application/json');
echo json_encode($response);
