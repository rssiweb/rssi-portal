<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    exit('Access denied');
}

// Get filters from GET parameters
$filters = [
    'start_date' => $_GET['start_date'] ?? date('Y-m-01'),
    'end_date' => $_GET['end_date'] ?? date('Y-m-d'),
    'class' => $_GET['class'] ?? 'all',
    'category' => $_GET['category'] ?? 'all',
    'student_id' => $_GET['student_id'] ?? 'all',
    'student_id_only' => $_GET['student_id_only'] ?? false
];

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
 * Get student info for a specific date (category type and class)
 */
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
    return pg_fetch_assoc($originalResult) ?? ['category_type' => 'Regular', 'class' => null];
}

// Fetch student data
$students = getStudentsData($filters['start_date'], $filters['end_date'], $filters);

// Process each student to calculate fees based on last attendance month
$processedStudents = [];
if (!empty($students)) {
    foreach ($students as $student) {
        $studentId = $student['student_id'];

        // Get the last attendance date from your query result
        $lastAttendanceDate = $student['last_attended_date'];

        // If no attendance record, use admission date or current date as fallback
        if (!$lastAttendanceDate || $lastAttendanceDate == 'N/A') {
            $lastAttendanceDate = $student['doa'] ?: date('Y-m-d');
        }

        // If last attendance date is the 1st of the month, shift it to the previous month's last day
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
        $studentSpecificTotal = 0;
        $studentSpecificQuery = "SELECT fc.category_name, ssf.amount 
                            FROM student_specific_fees ssf
                            JOIN fee_categories fc ON ssf.category_id = fc.id
                            WHERE ssf.student_id = '{$student['student_id']}'
                            AND '$firstDayOfMonth' BETWEEN ssf.effective_from AND COALESCE(ssf.effective_until, '9999-12-31')";
        $studentSpecificResult = pg_query($con, $studentSpecificQuery);
        $studentSpecificItems = pg_fetch_all($studentSpecificResult) ?? [];

        foreach ($studentSpecificItems as $fee) {
            $studentSpecificTotal += $fee['amount'];
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

        // 4. Combine both fee types (student-specific fees are ADDED to standard fees)
        $totalCurrentMonthFees = $currentMonthFees + $studentSpecificTotal;

        // 5. Get payments for the last attendance month
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

        // 6. Get concessions for the last attendance month
        $concessionQuery = "SELECT COALESCE(SUM(concession_amount), 0) as concession_amount
                       FROM student_concessions
                       WHERE student_id = '$studentId'
                       AND '$firstDayOfMonth' BETWEEN effective_from AND COALESCE(effective_until, '9999-12-31')";
        $concessionResult = pg_query($con, $concessionQuery);
        $concessionAmount = (float)(pg_fetch_assoc($concessionResult)['concession_amount'] ?? 0);

        // 7. Calculate carry forward (previous months' unpaid dues)
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

        // 8. Calculate current month's net fee and due amount
        $netFee = ($totalCurrentMonthFees) - $concessionAmount;
        $dueAmount = ($netFee - $corePaidAmount) + $carryForward;

        // Prepare student data for export
        $processedStudents[] = [
            'student_id' => $student['student_id'],
            'studentname' => $student['studentname'],
            'class' => $student['class'],
            'doa' => date('d-M-Y', strtotime($student['doa'])),
            'category' => $student['category'],
            'filterstatus' => $student['filterstatus'],
            'last_attended_month' => "$month $year",
            'due_amount' => $dueAmount,
            'last_attended_date' => $student['last_attended_date'],
            'effectivefrom' => $student['effectivefrom'],
            'total_duration_days' => $student['total_duration_days'],
            'remarks' => $student['remarks'] ?? ''
        ];
    }
}

// Output CSV headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=student_attrition_export_' . date('Y-m-d') . '.csv');

// Optional: add BOM for Excel to recognize UTF-8
echo "\xEF\xBB\xBF";

// Create output stream
$output = fopen('php://output', 'w');

// Add CSV headers
fputcsv($output, [
    'Student ID',
    'Name',
    'Class',
    'Category',
    'DOA',
    'Status',
    'Fees Due Up To',
    'Due Amount',
    'Last Attendance',
    'Marked Inactive',
    'Class Attended',
    'Remarks'
]);

// Add data rows
foreach ($processedStudents as $student) {
    // Format class attended duration
    $classAttended = 'N/A';
    if (!empty($student['total_duration_days'])) {
        $days = (int)$student['total_duration_days'];
        if ($days < 30) {
            $classAttended = $days . ' days';
        } elseif ($days < 365) {
            $months = floor($days / 30);
            $remaining_days = $days % 30;
            $classAttended = $months . ' month' . ($months > 1 ? 's' : '');
            if ($remaining_days > 0) {
                $classAttended .= ' ' . $remaining_days . ' day' . ($remaining_days > 1 ? 's' : '');
            }
        } else {
            $years = floor($days / 365);
            $remaining_days = $days % 365;
            $months = floor($remaining_days / 30);
            $extra_days = $remaining_days % 30;

            $classAttended = $years . ' year' . ($years > 1 ? 's' : '');
            if ($months > 0) {
                $classAttended .= ' ' . $months . ' month' . ($months > 1 ? 's' : '');
            }
            if ($extra_days > 0) {
                $classAttended .= ' ' . $extra_days . ' day' . ($extra_days > 1 ? 's' : '');
            }
        }
    }

    fputcsv($output, [
        $student['student_id'],
        $student['studentname'],
        $student['class'],
        $student['category'],
        !empty($student['doa']) ? date("d/m/Y", strtotime($student['doa'])) : 'N/A',
        $student['filterstatus'],
        $student['last_attended_month'],
        '₹' . number_format($student['due_amount'], 2),
        !empty($student['last_attended_date']) ? date("d/m/Y", strtotime($student['last_attended_date'])) : 'N/A',
        !empty($student['effectivefrom']) ? date("d/m/Y", strtotime($student['effectivefrom'])) : 'N/A',
        $classAttended,
        $student['remarks']
    ]);
}

fclose($output);
exit();
