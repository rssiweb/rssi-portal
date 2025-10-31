<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

/* ------------------------------
   FUNCTIONS
------------------------------- */

/**
 * Build common SQL filter conditions
 */
function buildFilters($filters = [])
{
    global $con;
    $conditions = [];

    // If student_id_only is checked, only apply student_id filter
    if (!empty($filters['student_id_only'])) {
        if (!empty($filters['student_id']) && $filters['student_id'] != 'all') {
            $ids = is_array($filters['student_id']) ? $filters['student_id'] : [$filters['student_id']];
            $conditions[] = "s.student_id IN ('" . implode("','", array_map(fn($c) => pg_escape_string($con, $c), $ids)) . "')";
        }
    } else {
        // Original filter logic
        if (!empty($filters['class']) && $filters['class'] != 'all') {
            $classes = is_array($filters['class']) ? $filters['class'] : [$filters['class']];
            $conditions[] = "s.class IN ('" . implode("','", array_map(fn($c) => pg_escape_string($con, $c), $classes)) . "')";
        }
        if (!empty($filters['category']) && $filters['category'] != 'all') {
            $categories = is_array($filters['category']) ? $filters['category'] : [$filters['category']];
            $conditions[] = "s.category IN ('" . implode("','", array_map(fn($c) => pg_escape_string($con, $c), $categories)) . "')";
        }
        if (!empty($filters['student_id']) && $filters['student_id'] != 'all') {
            $ids = is_array($filters['student_id']) ? $filters['student_id'] : [$filters['student_id']];
            $conditions[] = "s.student_id IN ('" . implode("','", array_map(fn($c) => pg_escape_string($con, $c), $ids)) . "')";
        }
    }

    return count($conditions) ? ' AND ' . implode(' AND ', $conditions) : '';
}

/**
 * Get total students enrolled in period and new admissions
 */
function getStudentMetrics($start_date, $end_date, $filters = [])
{
    global $con;
    $filter_clause = buildFilters($filters);

    // If student_id_only is checked, ignore date filters
    if (!empty($filters['student_id_only'])) {
        // Total students: all students matching the student_id filter
        $query_total = "
            SELECT COUNT(DISTINCT s.student_id) AS total_students
            FROM rssimyprofile_student s
            WHERE 1=1
              $filter_clause
        ";

        // New admissions: not applicable in student_id_only mode
        $new_admissions = 0;
    } else {
        // Total students: enrolled at any time in period (active or later inactive)
        $query_total = "
            SELECT COUNT(DISTINCT s.student_id) AS total_students
            FROM rssimyprofile_student s
            WHERE s.doa <= '$end_date'
              AND (s.effectivefrom IS NULL OR s.effectivefrom >= '$start_date')
              $filter_clause
        ";

        // New admissions: students whose DOA falls within period
        $query_new = "
            SELECT COUNT(*) AS new_admissions
            FROM rssimyprofile_student s
            WHERE s.doa BETWEEN '$start_date' AND '$end_date'
              $filter_clause
        ";
        $new_admissions = pg_fetch_assoc(pg_query($con, $query_new))['new_admissions'];
    }

    $total_students = pg_fetch_assoc(pg_query($con, $query_total))['total_students'];

    return [
        'total_students' => $total_students,
        'new_admissions' => $new_admissions
    ];
}

/**
 * Count active students as of a specific date
 */
function getActiveStudents($date, $filters = [])
{
    global $con;
    $filter_clause = buildFilters($filters);

    // If student_id_only is checked, ignore date filter
    if (!empty($filters['student_id_only'])) {
        $query = "
            SELECT COUNT(*) AS count
            FROM rssimyprofile_student s
            WHERE 1=1
              $filter_clause
        ";
    } else {
        // Active students: currently active or inactive after this date
        $query = "
            SELECT COUNT(*) AS count
            FROM rssimyprofile_student s
            WHERE s.doa <= '$date'
              AND (s.effectivefrom IS NULL OR s.effectivefrom > '$date')
              $filter_clause
        ";
    }

    return pg_fetch_assoc(pg_query($con, $query))['count'];
}

/**
 * Count students who left during a period
 */
function getStudentsLeftDuringPeriod($start_date, $end_date, $filters = [])
{
    global $con;
    $filter_clause = buildFilters($filters);

    // If student_id_only is checked, ignore date filters
    if (!empty($filters['student_id_only'])) {
        $query = "
            SELECT COUNT(*) AS count
            FROM rssimyprofile_student s
            WHERE s.filterstatus = 'Inactive'
              $filter_clause
        ";
    } else {
        $query = "
            SELECT COUNT(*) AS count
            FROM rssimyprofile_student s
            WHERE s.filterstatus = 'Inactive'
              AND s.effectivefrom BETWEEN '$start_date' AND '$end_date'
              $filter_clause
        ";
    }

    return pg_fetch_assoc(pg_query($con, $query))['count'];
}

/**
 * Get detailed student data within period
 */
function getStudentsData($start_date, $end_date, $filters = [])
{
    global $con;
    $filter_clause = buildFilters($filters);

    // If student_id_only is checked, ignore date filters
    if (!empty($filters['student_id_only'])) {
        $query = "
            SELECT 
                s.student_id,
                s.studentname,
                s.category,
                s.class,
                s.filterstatus,
                s.doa,
                s.effectivefrom,
                s.gender,
                s.remarks,
                MAX(a.punch_in) AS last_attended_date,
                (MAX(a.punch_in)::date - s.doa::date) AS total_duration_days
            FROM rssimyprofile_student s
            LEFT JOIN attendance a ON a.user_id = s.student_id
            WHERE s.filterstatus = 'Inactive'
              $filter_clause
            GROUP BY s.student_id, s.studentname, s.category, s.class, s.filterstatus, s.doa, s.effectivefrom, s.gender
            ORDER BY s.effectivefrom DESC
        ";
    } else {
        $query = "
            SELECT 
                s.student_id,
                s.studentname,
                s.category,
                s.class,
                s.filterstatus,
                s.doa,
                s.effectivefrom,
                s.gender,
                s.remarks,
                MAX(a.punch_in) AS last_attended_date,
                (MAX(a.punch_in)::date - s.doa::date) AS total_duration_days
            FROM rssimyprofile_student s
            LEFT JOIN attendance a ON a.user_id = s.student_id
            WHERE s.filterstatus = 'Inactive' AND s.effectivefrom BETWEEN '$start_date' AND '$end_date'
              $filter_clause
            GROUP BY s.student_id, s.studentname, s.category, s.class, s.filterstatus, s.doa, s.effectivefrom, s.gender
            ORDER BY s.effectivefrom DESC
        ";
    }

    $result = pg_query($con, $query);
    $students = [];
    while ($row = pg_fetch_assoc($result)) {
        $students[] = $row;
    }
    return $students;
}

/**
 * Calculate attrition and retention metrics
 */
function calculateAttritionMetrics($students, $students_at_start, $students_left, $students_today, $total_students)
{
    $metrics = [
        'students_today' => $students_today,
        'students_at_start' => $students_at_start,
        'students_left' => $students_left,
        'male_inactive' => 0,
        'female_inactive' => 0,
        'binary_inactive' => 0,
        'attrition_rate' => 0,
        'retention_rate' => 0
    ];

    foreach ($students as $student) {
        if ($student['filterstatus'] === 'Inactive') {
            if ($student['gender'] === 'Male') $metrics['male_inactive']++;
            elseif ($student['gender'] === 'Female') $metrics['female_inactive']++;
            elseif ($student['gender'] === 'Binary') $metrics['binary_inactive']++;
        }
    }

    if ($total_students > 0) {
        $metrics['attrition_rate'] = round(($students_left / $total_students) * 100, 2);
        $metrics['retention_rate'] = round(($students_today / $total_students) * 100, 2);
    }

    return $metrics;
}

/* ------------------------------
   GET FILTERS + DATA
------------------------------- */

$filters = [
    'start_date' => $_GET['start_date'] ?? date('Y-m-01'),
    'end_date' => $_GET['end_date'] ?? date('Y-m-d'),
    'class' => $_GET['class'] ?? 'all',
    'category' => $_GET['category'] ?? 'all',
    'student_id' => $_GET['student_id'] ?? 'all',
    'student_id_only' => $_GET['student_id_only'] ?? false
];

// Don't set start_date and end_date to null - instead modify the query functions
// to handle the student_id_only case appropriately

// Get selected values from filters if set, otherwise empty array
$selectedClasses = !empty($filters['class']) ? (array)$filters['class'] : [];
$selectedCategories = !empty($filters['category']) ? (array)$filters['category'] : [];
$selectedStudentIds = !empty($filters['student_id']) ? (array)$filters['student_id'] : [];

// Fetch metrics
$students_at_start = getActiveStudents($filters['start_date'], $filters);
$students_today = getActiveStudents($filters['end_date'], $filters);
$students_left = getStudentsLeftDuringPeriod($filters['start_date'], $filters['end_date'], $filters);
$student_metrics = getStudentMetrics($filters['start_date'], $filters['end_date'], $filters);
$total_students = $student_metrics['total_students'];
$new_admissions = $student_metrics['new_admissions'];
$students = getStudentsData($filters['start_date'], $filters['end_date'], $filters);

// Calculate attrition and retention
$metrics = calculateAttritionMetrics($students, $students_at_start, $students_left, $students_today, $total_students);
?>

<?php
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

// Process each student to calculate fees based on last attendance month
$processedStudents = [];
// Check if we have students data
if (!empty($students)) {
    foreach ($students as $student) {
        $studentId = $student['student_id'];

        // Get the last attendance date from your query result
        $lastAttendanceDate = $student['last_attended_date'];

        // If no attendance record, use admission date or current date as fallback
        if (!$lastAttendanceDate || $lastAttendanceDate == 'N/A') {
            $lastAttendanceDate = $student['doa'] ?: date('Y-m-d');
        }

        // If last attendance date is the 1st of the month, shift it to the previous month’s last day
        if (date('d', strtotime($lastAttendanceDate)) == '01') {
            $lastAttendanceDate = date('Y-m-t', strtotime($lastAttendanceDate . ' -1 month'));
        }

        // Extract month and year from last attendance
        $month = date('F', strtotime($lastAttendanceDate));
        $year = date('Y', strtotime($lastAttendanceDate));
        $monthNumber = date('m', strtotime($lastAttendanceDate));
        $firstDayOfMonth = "$year-$monthNumber-01";
        $lastDayOfMonth = date('Y-m-t', strtotime($firstDayOfMonth));

        // Get student type for the last attendance month
        $currentInfo = getStudentInfoForDate($con, $studentId, $firstDayOfMonth);
        $studentType = $currentInfo['category_type'] ?? 'Regular';
        $currentClass = $currentInfo['class'] ?? $student['class'];

        // 1. Get student-specific fees with details
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

        // 2. Get current month's base fees
        $feeQuery = "SELECT fc.id, fc.category_name, fs.amount, fc.fee_type
                FROM fee_structure fs
                JOIN fee_categories fc ON fs.category_id = fc.id
                WHERE fs.class = '$currentClass'
                AND fs.student_type = '$studentType'
                AND '$firstDayOfMonth' BETWEEN fs.effective_from AND COALESCE(fs.effective_until, '9999-12-31')";

        $feeResult = pg_query($con, $feeQuery);
        $feeItems = pg_fetch_all($feeResult) ?? [];

        // 3. Calculate current month's fees with Admission Fee logic
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

        // 4. Calculate total student-specific fees (simple sum, no category logic)
        $studentSpecificTotal = 0;
        foreach ($studentSpecificItems as $fee) {
            $studentSpecificTotal += $fee['amount'];
        }

        // 5. Combine both fee types (student-specific fees are ADDED to standard fees)
        $totalCurrentMonthFees = $currentMonthFees + $studentSpecificTotal;

        // 6. Get payments for the last attendance month
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

        // 7. Get concessions for the last attendance month
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

                // Get student type for this historical month
                $historicalInfo = getStudentInfoForDate($con, $studentId, $loopMonthDate);
                $loopStudentType = $historicalInfo['category_type'] ?? 'Regular';
                $loopClass = $historicalInfo['class'] ?? $student['class'];

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
        $netFee = $totalCurrentMonthFees + $concessionAmount; // Concession reduces the fee
        $dueAmount = ($netFee - $corePaidAmount) + $carryForward;
        $totalAmount = $totalCurrentMonthFees + $carryForward;

        // Prepare student data for display
        $processedStudents[] = [
            'student_id' => $student['student_id'],
            'studentname' => $student['studentname'],
            'class' => $student['class'],
            'doa' => date('d-M-Y', strtotime($student['doa'])),
            'student_type' => $currentClass . '/' . $studentType,
            'last_attended_month' => "$month $year",
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
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Attrition Dashboard</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #4e73df;
            --success: #1cc88a;
            --info: #36b9cc;
            --warning: #f6c23e;
            --danger: #e74a3b;
            --secondary: #858796;
            --light: #f8f9fc;
            --dark: #5a5c69;
        }

        .dashboard-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            transition: transform 0.3s;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
        }

        .metric-card {
            text-align: center;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .metric-card i {
            position: absolute;
            right: 20px;
            bottom: 10px;
            font-size: 4rem;
            opacity: 0.2;
            transform: rotate(-15deg);
        }

        .metric-value {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .metric-label {
            font-size: 0.9rem;
            text-transform: uppercase;
            opacity: 0.8;
        }

        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }

        .table th {
            border-top: none;
            font-weight: 600;
            color: var(--dark);
        }

        .chart-container {
            position: relative;
            height: 250px;
        }

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2e59d9;
        }

        .filter-btn {
            min-width: 120px;
        }
    </style>
    <style>
        .metric-card.combined-card {
            padding: 20px;
            border-radius: 12px;
            color: white;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            position: relative;
            overflow: hidden;
            height: 100%;
        }

        .metric-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        /* Horizontal divider */
        .divider {
            width: 50px;
            height: 2px;
            background: rgba(255, 255, 255, 0.3);
            margin: 20px auto;
            border-radius: 1px;
        }

        .metric-header h3 {
            font-size: 16px;
            font-weight: 600;
            margin: 0;
        }

        .carousel-controls {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .carousel-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .carousel-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .carousel-btn i {
            font-size: 12px;
        }

        .chart-indicator {
            font-size: 12px;
            opacity: 0.8;
            min-width: 30px;
            text-align: center;
        }

        .charts-carousel {
            position: relative;
            height: 180px;
            overflow: hidden;
        }

        .chart-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 0.3s ease, transform 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            /* This prevents interaction when hidden */
            transform: translateX(100%);
        }

        .chart-slide.active {
            opacity: 1;
            pointer-events: auto;
            /* This enables interaction when visible */
            transform: translateX(0);
            z-index: 2;
        }

        .chart-slide.prev {
            transform: translateX(-100%);
        }

        .chart-slide.next {
            transform: translateX(100%);
        }

        .mini-chart-container {
            height: 140px;
            width: 100%;
            position: relative;
        }

        .chart-title {
            font-size: 14px;
            margin-bottom: 10px;
            text-align: center;
            opacity: 0.9;
        }

        .metric-footer {
            margin-top: 15px;
            text-align: center;
            opacity: 0.9;
        }

        .metric-footer i {
            font-size: 5rem;
            opacity: 0.3;
            transform: rotate(-5deg);
        }

        /* Hover effects for cards */
        .metric-card.combined-card {
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .metric-card.combined-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
            border-color: rgba(255, 255, 255, 0.2);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .carousel-controls {
                gap: 5px;
            }

            .carousel-btn {
                width: 24px;
                height: 24px;
            }

            .charts-carousel {
                height: 160px;
            }

            .mini-chart-container {
                height: 120px;
            }
        }
    </style>
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Student Attrition</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Work</a></li>
                    <li class="breadcrumb-item active">Student Attrition</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">

                <!-- Reports -->
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <br>
                            <div class="container-fluid py-4">
                                <!-- Filters Section -->
                                <div class="filter-section">
                                    <form method="GET" id="filterForm">
                                        <div class="row">
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label fw-bold">Date Range</label>
                                                <input type="date" class="form-control" name="start_date" id="start_date"
                                                    value="<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); ?>">
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label fw-bold">To</label>
                                                <input type="date" class="form-control" name="end_date" id="end_date"
                                                    value="<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'); ?>">
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label fw-bold">Class</label>
                                                <select class="form-select" id="classes" name="class[]" multiple>
                                                    <?php foreach ($selectedClasses as $cls): ?>
                                                        <?php if ($cls != 'all'): ?>
                                                            <option value="<?= htmlspecialchars($cls) ?>" selected><?= htmlspecialchars($cls) ?></option>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="col-md-2 mb-3">
                                                <label class="form-label fw-bold">Category</label>
                                                <select class="form-select" id="categories" name="category[]" multiple>
                                                    <?php foreach ($selectedCategories as $cat): ?>
                                                        <?php if ($cat != 'all'): ?>
                                                            <option value="<?= htmlspecialchars($cat) ?>" selected><?= htmlspecialchars($cat) ?></option>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="col-md-2 mb-3">
                                                <label class="form-label fw-bold">Student ID</label>
                                                <select class="form-select" id="student_ids" name="student_id[]" multiple>
                                                    <?php foreach ($selectedStudentIds as $stu): ?>
                                                        <?php if ($stu != 'all'): ?>
                                                            <option value="<?= htmlspecialchars($stu) ?>" selected><?= htmlspecialchars($stu) ?></option>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="form-text">
                                                    Select inactive students only.
                                                </div>
                                            </div>
                                        </div>

                                        <!-- New checkbox for student ID only search -->
                                        <div class="row mt-2">
                                            <div class="col-md-12">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="student_id_only" name="student_id_only"
                                                        <?php echo !empty($_GET['student_id_only']) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="student_id_only">
                                                        Search by Student ID only (ignore all other filters)
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row mt-3">
                                            <div class="col-12">
                                                <button type="submit" class="btn btn-primary filter-btn">
                                                    <i class="fas fa-filter me-1"></i> Apply Filters
                                                </button>
                                                <button type="button" class="btn btn-secondary filter-btn" onclick="resetFilters()">
                                                    <i class="fas fa-sync-alt me-1"></i> Reset
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <!-- Combined Metrics Cards -->
                                <div class="row">
                                    <!-- Combined Enrollment Card -->
                                    <div class="col-xl-3 col-md-6">
                                        <div class="metric-card combined-card" style="background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);">
                                            <div class="metric-header">
                                                <h3>Enrollment Summary</h3>
                                            </div>
                                            <div class="combined-metrics">
                                                <div class="combined-metric">
                                                    <div class="metric-value"><?php echo $total_students; ?></div>
                                                    <div class="metric-label">Total Enrolled</div>
                                                </div>
                                                <div class="divider"></div>
                                                <div class="combined-metric">
                                                    <div class="metric-value"><?php echo $new_admissions; ?></div>
                                                    <div class="metric-label">New Admissions</div>
                                                </div>
                                            </div>
                                            <div class="metric-footer">
                                                <i class="fas fa-user-plus"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Combined Active Students Card -->
                                    <div class="col-xl-3 col-md-6">
                                        <div class="metric-card combined-card" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                                            <div class="metric-header">
                                                <h3>Active Students</h3>
                                            </div>
                                            <div class="combined-metrics">
                                                <div class="combined-metric">
                                                    <div class="metric-value"><?php echo $metrics['students_at_start']; ?></div>
                                                    <div class="metric-label">At Period Start</div>
                                                </div>
                                                <div class="divider"></div>
                                                <div class="combined-metric">
                                                    <div class="metric-value"><?php echo $metrics['students_today']; ?></div>
                                                    <div class="metric-label">At Period End</div>
                                                </div>
                                            </div>
                                            <div class="metric-footer">
                                                <i class="fas fa-chart-line"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Combined Attrition Card -->
                                    <div class="col-xl-3 col-md-6">
                                        <div class="metric-card combined-card" style="background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);">
                                            <div class="metric-header">
                                                <h3>Attrition Analysis</h3>
                                            </div>
                                            <div class="combined-metrics">
                                                <div class="combined-metric">
                                                    <div class="metric-value"><?php echo $metrics['students_left']; ?></div>
                                                    <div class="metric-label">Students Left</div>
                                                </div>
                                                <div class="divider"></div>
                                                <div class="combined-metric">
                                                    <div class="metric-value"><?php echo $metrics['attrition_rate']; ?>%</div>
                                                    <div class="metric-label">Attrition Rate</div>
                                                </div>
                                            </div>
                                            <div class="metric-footer">
                                                <i class="fas fa-user-times"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Combined Charts Card -->
                                    <div class="col-xl-3 col-md-6">
                                        <div class="metric-card combined-card chart-combo-box" style="background: linear-gradient(135deg, #6f42c1 0%, #20c997 100%);">
                                            <div class="metric-header">
                                                <h3>Analytics Overview</h3>
                                                <div class="carousel-controls">
                                                    <button class="carousel-btn prev-btn" onclick="prevChart()">
                                                        <i class="fas fa-chevron-left"></i>
                                                    </button>
                                                    <span class="chart-indicator">1/2</span>
                                                    <button class="carousel-btn next-btn" onclick="nextChart()">
                                                        <i class="fas fa-chevron-right"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="charts-carousel">
                                                <div class="chart-slide active">
                                                    <div class="chart-title">Gender Distribution</div>
                                                    <div class="mini-chart-container">
                                                        <canvas id="genderChart"></canvas>
                                                    </div>
                                                </div>
                                                <div class="chart-slide">
                                                    <div class="chart-title">Attrition Overview</div>
                                                    <div class="mini-chart-container">
                                                        <canvas id="attritionChart"></canvas>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="metric-footer">
                                                <i class="fas fa-chart-pie"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Students Table -->
                                <div class="dashboard-card mt-4">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h4 class="mb-0"><i class="fas fa-list me-2"></i>Student Details</h4>
                                        <!-- <span class="badge bg-primary"><?php echo count($students); ?> records found</span> -->
                                        <!-- Update your export button -->
                                        <a href="export_student_attrition.php?<?php echo http_build_query($_GET); ?>" class="btn btn-success btn-sm">
                                            <i class="fas fa-file-export me-1"></i> Export CSV
                                        </a>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="table-id">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Student ID</th>
                                                    <th>Name</th>
                                                    <th>Class</th>
                                                    <th>Category</th>
                                                    <th>DOA</th>
                                                    <th>Status</th>
                                                    <th>Fees Due Up To</th>
                                                    <th>Due Amount</th>
                                                    <th>Last Attendance</th>
                                                    <th>Marked Inactive</th>
                                                    <th>Class Attended</th>
                                                    <th>Remarks</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (count($students) > 0): ?>
                                                    <?php foreach ($students as $index => $student): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                                            <td><?php echo htmlspecialchars($student['studentname']); ?></td>
                                                            <td><?php echo htmlspecialchars($student['class']); ?></td>
                                                            <td><?php echo htmlspecialchars($student['category']); ?></td>
                                                            <td>
                                                                <?php echo !empty($student['doa']) ? date("d/m/Y", strtotime($student['doa'])) : 'N/A'; ?>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($student['filterstatus']); ?></td>
                                                            <td>
                                                                <?php
                                                                // Display the month of last attendance for which due is calculated
                                                                if (!empty($processedStudents[$index]['last_attended_month'])) {
                                                                    echo $processedStudents[$index]['last_attended_month'];
                                                                } else {
                                                                    $lastAttendanceDate = $student['last_attended_date'];
                                                                    if ($lastAttendanceDate && $lastAttendanceDate != 'N/A') {
                                                                        echo date("F Y", strtotime($lastAttendanceDate));
                                                                    } else {
                                                                        echo 'N/A';
                                                                    }
                                                                }
                                                                ?>
                                                            </td>
                                                            <td>
                                                                <?php
                                                                // Display the due amount for the last attended month
                                                                echo isset($processedStudents[$index]['due_amount']) ? '₹' . number_format($processedStudents[$index]['due_amount'], 2) : 'N/A';
                                                                ?>
                                                            </td>
                                                            <td>
                                                                <?php echo !empty($student['last_attended_date']) ? date("d/m/Y", strtotime($student['last_attended_date'])) : 'N/A'; ?>
                                                            </td>
                                                            <td>
                                                                <?php echo !empty($student['effectivefrom']) ? date("d/m/Y", strtotime($student['effectivefrom'])) : 'N/A'; ?>
                                                            </td>
                                                            <td>
                                                                <?php
                                                                if (!empty($student['total_duration_days'])) {
                                                                    $days = (int)$student['total_duration_days'];

                                                                    if ($days < 30) {
                                                                        echo $days . ' days';
                                                                    } elseif ($days < 365) {
                                                                        $months = floor($days / 30);
                                                                        $remaining_days = $days % 30;
                                                                        echo $months . ' month' . ($months > 1 ? 's' : '');
                                                                        if ($remaining_days > 0) {
                                                                            echo ' ' . $remaining_days . ' day' . ($remaining_days > 1 ? 's' : '');
                                                                        }
                                                                    } else {
                                                                        $years = floor($days / 365);
                                                                        $remaining_days = $days % 365;
                                                                        $months = floor($remaining_days / 30);
                                                                        $extra_days = $remaining_days % 30;

                                                                        echo $years . ' year' . ($years > 1 ? 's' : '');
                                                                        if ($months > 0) {
                                                                            echo ' ' . $months . ' month' . ($months > 1 ? 's' : '');
                                                                        }
                                                                        if ($extra_days > 0) {
                                                                            echo ' ' . $extra_days . ' day' . ($extra_days > 1 ? 's' : '');
                                                                        }
                                                                    }
                                                                } else {
                                                                    echo 'N/A';
                                                                }
                                                                ?>
                                                            </td>
                                                            <td>
                                                                <?php if (!empty($student['remarks'])): ?>
                                                                    <!-- Link to open modal -->
                                                                    <a href="#" class="view-remarks-link" data-bs-toggle="modal" data-bs-target="#remarksModal<?= $student['student_id'] ?>">
                                                                        View
                                                                    </a>
                                                                <?php else: ?>
                                                                    N/A
                                                                <?php endif; ?>
                                                            </td>

                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="12" class="text-center py-4">
                                                            <i class="fas fa-exclamation-circle fa-2x mb-3 text-muted"></i>
                                                            <p class="text-muted">No students found with the selected filters.</p>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- End Reports -->
            </div>
        </section>

    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
    <script>
        $(document).ready(function() {
            // Check if resultArr is empty
            <?php if (!empty($students)) : ?>
                // Initialize DataTables only if resultArr is not empty
                $('#table-id').DataTable({
                    // paging: false,
                    "order": [] // Disable initial sorting
                    // other options...
                });
            <?php endif; ?>
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Include Student IDs
            $('#student_ids').select2({
                ajax: {
                    url: 'fetch_students.php?isInactive=true',
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        q: params.term
                    }),
                    processResults: data => ({
                        results: data.results
                    }),
                    cache: true
                },
                placeholder: 'Search by name or ID',
                width: '100%',
                minimumInputLength: 1,
                multiple: true
            });

            // Categories
            $('#categories').select2({
                ajax: {
                    url: 'fetch_category.php',
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        q: params.term
                    }),
                    processResults: data => ({
                        results: data.results
                    }),
                    cache: true
                },
                placeholder: 'Search by category',
                width: '100%',
                minimumInputLength: 1,
                multiple: true
            });

            // Classes
            $('#classes').select2({
                ajax: {
                    url: 'fetch_class.php',
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        q: params.term
                    }),
                    processResults: data => ({
                        results: data.results
                    }),
                    cache: true
                },
                placeholder: 'Search by class',
                width: '100%',
                minimumInputLength: 1,
                multiple: true
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            // Initialize charts only if we have data
            <?php if ($metrics['students_at_start'] > 0): ?>
                // Gender Chart
                const genderCtx = document.getElementById('genderChart');
                if (genderCtx) {
                    new Chart(genderCtx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Male', 'Female', 'Binary'],
                            datasets: [{
                                data: [
                                    <?php echo $metrics['male_inactive']; ?>,
                                    <?php echo $metrics['female_inactive']; ?>,
                                    <?php echo $metrics['binary_inactive']; ?>
                                ],
                                backgroundColor: ['#36A2EB', '#FF6384', '#9966FF'],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        color: '#fff', // <-- Change legend text color here
                                        // font: {
                                        //     size: 14, // optional: change font size
                                        //     weight: 'bold' // optional: bold labels
                                        // }
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const label = context.label || '';
                                            const value = context.raw || 0;
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = Math.round((value / total) * 100);
                                            return `${label}: ${value} (${percentage}%)`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                }

                // Attrition Overview Chart
                const attritionCtx = document.getElementById('attritionChart');
                if (attritionCtx) {
                    new Chart(attritionCtx, {
                        type: 'pie',
                        data: {
                            labels: ['Students Retained', 'Students Left'],
                            datasets: [{
                                data: [
                                    <?php echo ($total_students - $metrics['students_left']); ?>, // Retained
                                    <?php echo $metrics['students_left']; ?> // Left
                                ],
                                backgroundColor: ['#4BC0C0', '#FF9F40'],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        color: '#fff', // <-- Change legend text color here
                                        // font: {
                                        //     size: 14, // optional: change font size
                                        //     weight: 'bold' // optional: bold labels
                                        // }
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const label = context.label || '';
                                            const value = context.raw || 0;
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = Math.round((value / total) * 100);
                                            return `${label}: ${value} (${percentage}%)`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            <?php endif; ?>
        });

        function resetFilters() {
            // Reset form values
            document.querySelector('input[name="start_date"]').value = '<?php echo date('Y-m-01'); ?>';
            document.querySelector('input[name="end_date"]').value = '<?php echo date('Y-m-d'); ?>';

            // Clear Select2 dropdowns
            $('.class-select').val(null).trigger('change');
            $('.category-select').val(null).trigger('change');
            $('.student-select').val(null).trigger('change');

            // Submit the form
            document.getElementById('filterForm').submit();
        }
    </script>
    <script>
        let currentChartIndex = 0;
        const totalCharts = 2;

        function updateChartIndicator() {
            document.querySelector('.chart-indicator').textContent = `${currentChartIndex + 1}/${totalCharts}`;
        }

        function showChart(index) {
            // Hide all charts by setting height to 0
            document.querySelectorAll('.chart-slide').forEach(slide => {
                slide.classList.remove('active');
            });

            // Show selected chart
            document.querySelectorAll('.chart-slide')[index].classList.add('active');

            currentChartIndex = index;
            updateChartIndicator();

            // Reinitialize charts to fix hover issues
            if (typeof initializeCharts === 'function') {
                initializeCharts();
            }
        }

        function nextChart() {
            let nextIndex = (currentChartIndex + 1) % totalCharts;
            showChart(nextIndex);
        }

        function prevChart() {
            let prevIndex = (currentChartIndex - 1 + totalCharts) % totalCharts;
            showChart(prevIndex);
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            updateChartIndicator();
        });
    </script>
    <script>
        // Function to handle the student_id_only checkbox
        document.addEventListener('DOMContentLoaded', function() {
            const studentIdOnlyCheckbox = document.getElementById('student_id_only');
            const studentIdSelect = document.getElementById('student_ids');
            const filterForm = document.getElementById('filterForm');

            // Function to toggle form elements based on checkbox state
            function toggleFormElements() {
                const isChecked = studentIdOnlyCheckbox.checked;
                const elementsToDisable = [
                    'start_date', 'end_date', 'classes', 'categories'
                ];

                elementsToDisable.forEach(id => {
                    const element = document.getElementById(id);
                    if (element) {
                        element.disabled = isChecked;
                    }
                });

                // Make student ID required when checkbox is checked
                if (isChecked) {
                    studentIdSelect.setAttribute('required', 'required');
                } else {
                    studentIdSelect.removeAttribute('required');
                }
            }

            // Initial state
            toggleFormElements();

            // Add event listener for checkbox change
            studentIdOnlyCheckbox.addEventListener('change', toggleFormElements);

            // Modify form submission to handle student_id_only
            filterForm.addEventListener('submit', function(e) {
                if (studentIdOnlyCheckbox.checked) {
                    // Clear other filter values if student_id_only is checked
                    document.getElementById('start_date').value = '';
                    document.getElementById('end_date').value = '';

                    // Clear class and category selections
                    const classSelect = document.getElementById('classes');
                    const categorySelect = document.getElementById('categories');

                    Array.from(classSelect.options).forEach(option => {
                        option.selected = false;
                    });

                    Array.from(categorySelect.options).forEach(option => {
                        option.selected = false;
                    });

                    // Validate that at least one student ID is selected
                    const selectedStudentIds = Array.from(studentIdSelect.selectedOptions);
                    if (selectedStudentIds.length === 0) {
                        e.preventDefault(); // Prevent form submission
                        alert('Please select at least one Student ID when using "Search by Student ID only"');
                        studentIdSelect.focus();
                    }
                }
            });
        });

        // Reset filters function (update if needed)
        function resetFilters() {
            // Your existing reset logic
            window.location.href = window.location.pathname;
        }
    </script>
    <!-- Place this at the bottom of your page, before </body> -->
    <div id="modals-container">
        <?php if (!empty($students)): ?>
            <?php foreach ($students as $student): ?>
                <?php if (!empty($student['remarks'])): ?>
                    <!-- Modal -->
                    <div class="modal fade" id="remarksModal<?= $student['student_id'] ?>" tabindex="-1" aria-labelledby="remarksModalLabel<?= $student['student_id'] ?>" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="remarksModalLabel<?= $student['student_id'] ?>">Remarks for <?= htmlspecialchars($student['studentname']) ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <?= nl2br(htmlspecialchars($student['remarks'])) ?>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>

</html>