<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

function getStudentInfoForDate($con, $studentId, $targetDate)
{
    // First try to get from history table
    $query = "SELECT category_type, class 
              FROM student_category_history 
              WHERE student_id = $1 
              AND is_valid = true
              AND (
                  (effective_from <= '$targetDate' AND (effective_until >= '$targetDate' OR effective_until IS NULL))
                  OR
                  (TO_CHAR(effective_from, 'YYYY-MM') = TO_CHAR('$targetDate'::date, 'YYYY-MM'))
              )
              ORDER BY effective_from DESC, created_at DESC
              LIMIT 1";

    $result = pg_query_params($con, $query, array($studentId));
    if ($row = pg_fetch_assoc($result)) {
        return $row; // Return historical data if found
    }

    // Fallback to original student record if no history exists
    $originalQuery = "SELECT type_of_admission as category_type, class 
                     FROM rssimyprofile_student 
                     WHERE student_id = $1";
    $originalResult = pg_query_params($con, $originalQuery, array($studentId));
    return pg_fetch_assoc($originalResult) ?? ['category_type' => null, 'class' => null];
}

// Get filter parameters from URL - match the main page parameters
$status = $_GET['status'] ?? 'Active';
$monthYear = $_GET['month_year'] ?? date('Y-m');
$category = $_GET['category'] ?? [];
$class = $_GET['class'] ?? [];
$student_ids = $_GET['student_ids'] ?? [];

// Handle category parameter - could be string or array
if (!is_array($category) && !empty($category)) {
    $category = [$category];
} elseif (empty($category)) {
    $category = [];
}

// Handle class parameter - could be string or array
if (!is_array($class) && !empty($class)) {
    $class = [$class];
} elseif (empty($class)) {
    $class = [];
}

// Handle student IDs parameter - ensure it's always an array
if (!is_array($student_ids)) {
    $student_ids = !empty($student_ids) ? [$student_ids] : [];
}

// Check if we have any filters
$hasFilters = !empty($class) || !empty($student_ids) || !empty($category);

if (!$hasFilters) {
    header('Content-Type: text/plain');
    echo "Error: Please select at least one class, category, or student to export data.";
    exit;
}

// Convert month_year to month name and number
list($year, $monthNumber) = explode('-', $monthYear);
$month = date('F', strtotime("$year-$monthNumber-01"));
$firstDayOfMonth = "$year-$monthNumber-01";
$lastDayOfMonth = date('Y-m-t', strtotime($firstDayOfMonth));

// Get student data - match the query from main page
$query = "SELECT s.student_id, s.studentname, s.category, s.class, s.doa, 
                 s.type_of_admission, s.filterstatus, s.effectivefrom, s.contact
          FROM rssimyprofile_student s
          WHERE s.filterstatus = '$status'
          AND (s.doa <= '$lastDayOfMonth' AND 
              (s.filterstatus = 'Active' OR 
               (s.filterstatus = 'Inactive' AND s.effectivefrom > '$firstDayOfMonth')))";

// Add category filter if categories are selected
if (!empty($category)) {
    $escapedCategories = array_map(function ($cat) use ($con) {
        return pg_escape_string($con, $cat);
    }, $category);
    $categoryList = implode("','", $escapedCategories);
    $query .= " AND s.category IN ('$categoryList')";
}

// Add class filter if classes are selected
if (!empty($class)) {
    $escapedClasses = array_map(function ($c) use ($con) {
        return pg_escape_string($con, $c);
    }, $class);
    $classList = implode("','", $escapedClasses);
    $query .= " AND s.class IN ('$classList')";
}

// Add student IDs filter if provided
if (!empty($student_ids)) {
    $escapedIds = array_map(function ($id) use ($con) {
        return pg_escape_string($con, $id);
    }, $student_ids);
    $idList = implode("','", $escapedIds);
    $query .= " AND s.student_id IN ('$idList')";
}

$query .= " ORDER BY s.class, s.studentname";

$result = pg_query($con, $query);
$students = pg_fetch_all($result) ?? [];

// Get fee categories
$categories = pg_fetch_all(pg_query(
    $con,
    "SELECT id, category_name, fee_type 
     FROM fee_categories 
     WHERE is_active = TRUE
     AND category_type='structured'
     ORDER BY id"
)) ?? [];

// Process each student to calculate fees - same logic as main page
$processedStudents = [];
foreach ($students as $student) {
    $studentId = $student['student_id'];

    // Get student info for current month
    $currentInfo = getStudentInfoForDate($con, $studentId, $firstDayOfMonth);
    $studentType = $currentInfo['category_type'];
    $currentClass = $currentInfo['class'] ?? $student['class']; // Fallback to original class if null

    // Get student-specific fees with details
    $studentSpecificDetails = [];
    $studentSpecificQuery = "SELECT fc.category_name, ssf.amount 
                            FROM student_specific_fees ssf
                            JOIN fee_categories fc ON ssf.category_id = fc.id
                            WHERE ssf.student_id = '{$student['student_id']}'
                            AND '$firstDayOfMonth' BETWEEN ssf.effective_from AND COALESCE(ssf.effective_until, '9999-12-31')";
    $studentSpecificResult = pg_query($con, $studentSpecificQuery);
    $studentSpecificItems = pg_fetch_all($studentSpecificResult) ?? [];

    $studentSpecificTotal = 0;
    foreach ($studentSpecificItems as $fee) {
        $studentSpecificTotal += $fee['amount'];
        $studentSpecificDetails[] = [
            'category' => $fee['category_name'],
            'amount' => $fee['amount']
        ];
    }

    // 1. Get current month's base fees
    $feeQuery = "SELECT fc.id, fc.category_name, fs.amount, fc.fee_type
                FROM fee_structure fs
                JOIN fee_categories fc ON fs.category_id = fc.id
                WHERE fs.class = '$currentClass'
                AND fs.student_type = '$studentType'
                AND '$firstDayOfMonth' BETWEEN fs.effective_from AND COALESCE(fs.effective_until, '9999-12-31')";

    $feeResult = pg_query($con, $feeQuery);
    $feeItems = pg_fetch_all($feeResult) ?? [];

    // 2. Calculate current month's fees with Admission Fee logic
    $feeDetails = [
        'Admission Fee' => 0,
        'Monthly Fee' => 0,
        'Miscellaneous' => 0
    ];

    foreach ($feeItems as $fee) {
        if ($fee['category_name'] == 'Admission Fee') {
            $admissionDate = strtotime($student['doa']);
            $admissionMonth = date('m', $admissionDate);
            if ($monthNumber == '04' || ($monthNumber == $admissionMonth && $year == date('Y', $admissionDate))) {
                $feeDetails['Admission Fee'] = $fee['amount'];
            }
        } elseif ($fee['category_name'] == 'Monthly Fee') {
            $feeDetails['Monthly Fee'] = $fee['amount'];
        } else {
            $feeDetails['Miscellaneous'] += $fee['amount'];
        }
    }
    $currentMonthFees = array_sum($feeDetails);

    // 3. Get current month's STUDENT-SPECIFIC fees (additional fees for this student)
    $studentSpecificQuery = "SELECT fc.id, fc.category_name, ssf.amount, fc.fee_type
                FROM student_specific_fees ssf
                JOIN fee_categories fc ON ssf.category_id = fc.id
                WHERE ssf.student_id = '$studentId'
                AND '$firstDayOfMonth' BETWEEN ssf.effective_from AND COALESCE(ssf.effective_until, '9999-12-31')";

    $studentSpecificResult = pg_query($con, $studentSpecificQuery);
    $studentSpecificItems = pg_fetch_all($studentSpecificResult) ?? [];

    // 4. Calculate total student-specific fees (simple sum, no category logic)
    $studentSpecificTotal = 0;
    foreach ($studentSpecificItems as $fee) {
        $studentSpecificTotal += $fee['amount'];
    }

    // 5. Combine both fee types (student-specific fees are ADDED to standard fees)
    $totalCurrentMonthFees = $currentMonthFees + $studentSpecificTotal;

    // 6. Get current month's payments
    $paymentsQuery = "SELECT 
                    COALESCE(SUM(amount), 0) as paid_amount,
                    COALESCE(SUM(CASE 
                        WHEN category_id IN (
                            SELECT id FROM fee_categories 
                            WHERE category_name IN ('Admission Fee', 'Monthly Fee')
                        ) THEN amount 
                        ELSE 0 
                    END), 0) as core_paid_amount
                 FROM fee_payments
                 WHERE student_id = '$studentId'
                 AND month = '$month'
                 AND academic_year = '$year'";

    $paymentsResult = pg_query($con, $paymentsQuery);
    $paymentData = pg_fetch_assoc($paymentsResult);
    $paidAmount = (float)($paymentData['paid_amount'] ?? 0);
    $corePaidAmount = (float)($paymentData['core_paid_amount'] ?? 0);

    // 7. Get current concessions
    $concessionQuery = "SELECT COALESCE(SUM(concession_amount), 0) as concession_amount
                       FROM student_concessions
                       WHERE student_id = '$studentId'
                       AND '$firstDayOfMonth' BETWEEN effective_from AND COALESCE(effective_until, '9999-12-31')";
    $concessionResult = pg_query($con, $concessionQuery);
    $concessionAmount = (float)(pg_fetch_assoc($concessionResult)['concession_amount'] ?? 0);

    // 8. Calculate carry forward (previous months' unpaid dues)
    $carryForward = 0;
    if ($monthNumber != '04') { // No carry forward in April (start of academic year)
        // Get all months from April to previous month of current year
        $startMonth = 4; // April
        $endMonth = $monthNumber - 1;

        // Get student's date of admission
        $doa = $student['doa'];
        $doaMonth = date('m', strtotime($doa));
        $doaYear = date('Y', strtotime($doa));

        for ($m = $startMonth; $m <= $endMonth; $m++) {
            $loopMonthNum = str_pad($m, 2, '0', STR_PAD_LEFT);
            $loopMonthName = date('F', mktime(0, 0, 0, $m, 1));
            $loopMonthDate = "$year-$loopMonthNum-01";

            // Skip months before student's admission
            if ($year == $doaYear && $m < $doaMonth) {
                continue;
            }

            // Get historical info for this month
            $historicalInfo = getStudentInfoForDate($con, $studentId, $loopMonthDate);
            $loopStudentType = $historicalInfo['category_type'];
            $loopClass = $historicalInfo['class'] ?? $student['class']; // Fallback to original class if null

            // Get month's fees
            $loopFeeQuery = "SELECT COALESCE(SUM(fs.amount), 0) as total_fee
               FROM fee_structure fs
               JOIN fee_categories fc ON fs.category_id = fc.id
               WHERE fs.class = '$loopClass'
               AND fs.student_type = '$loopStudentType'
               AND '$year-$loopMonthNum-01' BETWEEN fs.effective_from AND COALESCE(fs.effective_until, '9999-12-31')
               AND (
                   fc.category_name != 'Admission Fee'
                   OR (
                       fc.category_name = 'Admission Fee'
                       AND (
                           '$loopMonthNum' = '04'
                           OR (
                               EXTRACT(MONTH FROM TO_DATE('{$student['doa']}', 'YYYY-MM-DD')) = '$loopMonthNum'
                               AND EXTRACT(YEAR FROM TO_DATE('{$student['doa']}', 'YYYY-MM-DD')) = '$year'
                           )
                       )
                   )
               )";
            $loopFeeResult = pg_query($con, $loopFeeQuery);
            $loopTotalFee = (float)(pg_fetch_assoc($loopFeeResult)['total_fee'] ?? 0);

            $loopStudentSpecificQuery = "SELECT COALESCE(SUM(ssf.amount), 0) as total_fee
                          FROM student_specific_fees ssf
                          JOIN fee_categories fc ON ssf.category_id = fc.id
                          WHERE ssf.student_id = '{$student['student_id']}'
                          AND '$year-$loopMonthNum-01' BETWEEN ssf.effective_from AND COALESCE(ssf.effective_until, '9999-12-31')";
            $loopStudentSpecificResult = pg_query($con, $loopStudentSpecificQuery);
            $loopStudentSpecificFee = (float)(pg_fetch_assoc($loopStudentSpecificResult)['total_fee'] ?? 0);

            $CombLoopTotalFee = $loopTotalFee + $loopStudentSpecificFee;

            $loopPaymentsQuery = "SELECT COALESCE(SUM(p.amount), 0) as paid_amount
                   FROM fee_payments p
                   JOIN fee_categories fc ON p.category_id = fc.id
                   WHERE p.student_id = '$studentId'
                   AND p.month = '$loopMonthName'
                   AND p.academic_year = '$year'
                   AND fc.category_name IN ('Admission Fee', 'Monthly Fee')";

            $loopPaymentsResult = pg_query($con, $loopPaymentsQuery);
            $loopPaidAmount = (float)(pg_fetch_assoc($loopPaymentsResult)['paid_amount'] ?? 0);

            $loopConcessionQuery = "SELECT COALESCE(SUM(concession_amount), 0) as concession_amount
                      FROM student_concessions
                      WHERE student_id = '$studentId'
                      AND '$year-$loopMonthNum-01' BETWEEN effective_from AND COALESCE(effective_until, '9999-12-31')";
            $loopConcessionResult = pg_query($con, $loopConcessionQuery);
            $loopConcessionAmount = (float)(pg_fetch_assoc($loopConcessionResult)['concession_amount'] ?? 0);

            $loopNetFee = $CombLoopTotalFee - $loopConcessionAmount;
            $loopDueAmount = $loopNetFee - $loopPaidAmount;

            $carryForward += $loopDueAmount;
        }
    }

    // 9. Calculate current month's net fee and due amount
    $netFee = ($totalCurrentMonthFees) - $concessionAmount;
    $dueAmount = ($netFee - $corePaidAmount) + $carryForward;
    $totalAmount = $totalCurrentMonthFees + $carryForward;

    // Prepare student data for display
    $processedStudents[] = [
        'student_id' => $student['student_id'],
        'studentname' => $student['studentname'],
        'class' => $student['class'],
        'category' => $student['category'],
        'contact' => $student['contact'],
        'doa' => date('d-M-Y', strtotime($student['doa'])),
        'student_type' => $currentClass . '/' . $studentType,
        'admission_fee' => $feeDetails['Admission Fee'],
        'monthly_fee' => $feeDetails['Monthly Fee'],
        'miscellaneous' => $feeDetails['Miscellaneous'],
        'student_specific_fees' => $studentSpecificTotal,
        'student_specific_details' => $studentSpecificDetails,
        'total_fee' => $currentMonthFees,
        'concession_amount' => $concessionAmount,
        'carry_forward' => $carryForward,
        'net_fee' => $totalAmount,
        'paid_amount' => $paidAmount,
        'core_paid_amount' => $corePaidAmount,
        'due_amount' => $dueAmount
    ];
}

// Get summary data
$summary = [
    'total_students' => count($processedStudents),
    'total_fee' => array_sum(array_column($processedStudents, 'total_fee')),
    'total_concession' => array_sum(array_column($processedStudents, 'concession_amount')),
    'total_net_fee' => array_sum(array_column($processedStudents, 'net_fee')),
    'total_paid' => array_sum(array_column($processedStudents, 'core_paid_amount')),
    'total_due' => array_sum(array_column($processedStudents, 'due_amount')),
    'total_carry_forward' => array_sum(array_column($processedStudents, 'carry_forward'))
];

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="Monthly_Fee_Report_' . $month . '_' . $year . '.xls"');

// Start HTML output for Excel
echo '<html>';
echo '<head><meta charset="UTF-8"></head>';
echo '<body>';

// Report title and filters
echo '<h2>Monthly Fee Collection Report</h2>';
echo '<strong>Month:</strong> ' . $month . ' ' . $year . '<br>';
echo '<strong>Status:</strong> ' . $status . '<br>';
if (!empty($category)) {
    echo '<strong>Category:</strong> ' . implode(', ', $category) . '<br>';
}
if (!empty($class)) {
    echo '<strong>Class:</strong> ' . implode(', ', $class) . '<br>';
}
if (!empty($student_ids)) {
    echo '<strong>Student IDs:</strong> ' . implode(', ', $student_ids) . '<br>';
}
echo '<strong>Generated On:</strong> ' . date('d-M-Y H:i:s') . '<br>';
echo '<strong>Generated By:</strong> ' . $fullname . ' (' . $associatenumber . ')<br>';

// Summary section
echo '<h3>Summary</h3>';
echo '<table border="1">';
echo '<tr>';
echo '<th>Total Students</th>';
echo '<th>Total Fee</th>';
echo '<th>Total Concession</th>';
echo '<th>Total Net Fee</th>';
echo '<th>Total Paid</th>';
echo '<th>Total Due</th>';
echo '<th>Total Carry Forward</th>';
echo '</tr>';
echo '<tr>';
echo '<td>' . $summary['total_students'] . '</td>';
echo '<td>₹' . number_format($summary['total_fee'], 2) . '</td>';
echo '<td>₹' . number_format($summary['total_concession'], 2) . '</td>';
echo '<td>₹' . number_format(($summary['total_net_fee'] - $summary['total_concession']), 2) . '</td>';
echo '<td>₹' . number_format($summary['total_paid'], 2) . '</td>';
echo '<td>₹' . number_format($summary['total_due'], 2) . '</td>';
echo '<td>₹' . number_format($summary['total_carry_forward'], 2) . '</td>';
echo '</tr>';
echo '</table>';

// Detailed data section
echo '<h3>Student Fee Details</h3>';
echo '<table border="1">';
echo '<tr>';
echo '<th>Student ID</th>';
echo '<th>Name</th>';
echo '<th>Class</th>';
echo '<th>Category</th>';
echo '<th>Contact</th>';
echo '<th>DOA</th>';
echo '<th>Type</th>';
echo '<th>Admission Fee</th>';
echo '<th>Monthly Fee</th>';
echo '<th>Miscellaneous</th>';
echo '<th>Concession</th>';
echo '<th>Carry Forward</th>';
echo '<th>Net Fee</th>';
echo '<th>Paid</th>';
echo '<th>Due</th>';
echo '<th>Other Charges Paid</th>';
echo '</tr>';

foreach ($processedStudents as $student) {
    echo '<tr>';
    echo '<td>' . $student['student_id'] . '</td>';
    echo '<td>' . htmlspecialchars($student['studentname']) . '</td>';
    echo '<td>' . $student['class'] . '</td>';
    echo '<td>' . $student['category'] . '</td>';
    echo '<td>' . $student['contact'] . '</td>';
    echo '<td>' . $student['doa'] . '</td>';
    echo '<td>' . $student['student_type'] . '</td>';
    echo '<td>' . ($student['admission_fee'] > 0 ? '₹' . number_format($student['admission_fee'], 2) : '-') . '</td>';
    echo '<td>₹' . number_format($student['monthly_fee'], 2) . '</td>';

    // Calculate total miscellaneous (standard + student-specific)
    $standardMisc = $student['miscellaneous'] ?? 0;
    $studentSpecific = $student['student_specific_fees'] ?? 0;
    $totalMisc = $standardMisc + $studentSpecific;
    echo '<td>' . ($totalMisc > 0 ? '₹' . number_format($totalMisc, 2) : '-') . '</td>';

    echo '<td>' . ($student['concession_amount'] > 0 ? '₹' . number_format($student['concession_amount'], 2) : '-') . '</td>';
    echo '<td>₹' . number_format($student['carry_forward'], 2) . '</td>';
    echo '<td>₹' . number_format(($student['net_fee'] - $student['concession_amount']), 2) . '</td>';
    echo '<td>₹' . number_format($student['core_paid_amount'], 2) . '</td>';

    // Format due amount with credit indicator
    $dueAmount = $student['due_amount'];
    $formattedDue = '₹' . number_format(abs($dueAmount), 2);
    if ($dueAmount < 0) {
        $formattedDue .= ' (Cr)';
    }
    echo '<td>' . $formattedDue . '</td>';

    echo '<td>₹' . number_format(($student['paid_amount'] - $student['core_paid_amount']), 2) . '</td>';
    echo '</tr>';
}

echo '</table>';
echo '</body></html>';
exit;
