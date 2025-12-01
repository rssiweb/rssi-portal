<?php
require_once __DIR__ . '/../bootstrap.php';
include(__DIR__ . "/../util/login_util.php");

// Security: Multiple checks
function isAllowedAccess()
{
    $valid_api_key = 'YOUR_SECRET_API_KEY';
    $provided_api_key = $_GET['api_key'] ?? '';

    // Allowed origins
    $allowed_origins = [
        'http://localhost:8082',
        'https://login.rssi.in',
        'https://rssi.in',
        'https://www.rssi.in'
    ];
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    // Allowed referer domains
    $allowed_domains = [
        'localhost',
        'login.rssi.in',
        'rssi.in',
        'www.rssi.in'
    ];
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $is_allowed_referer = false;

    foreach ($allowed_domains as $domain) {
        if (strpos($referer, $domain) !== false) {
            $is_allowed_referer = true;
            break;
        }
    }

    return ($provided_api_key === $valid_api_key)
        || in_array($origin, $allowed_origins)
        || $is_allowed_referer;
}

if (!isAllowedAccess()) {
    http_response_code(403);
    echo '<div class="alert alert-danger text-center">
            <h4>Access Denied</h4>
            <p>Please use the official student result portal to access your results.</p>
            <p><a href="https://rssi.in/result-portal" class="btn btn-primary">Go to Result Portal</a></p>
          </div>';
    exit;
}

// Set initial values from GET parameters
$student_id = $_GET['student_id'] ?? '';
$exam_type = $_GET['exam_type'] ?? '';
$academic_year = $_GET['academic_year'] ?? '';

// Initialize flags
$student_exists = false;
$no_records_found = false;
$result_published = false;

// NEW FUNCTION: Check if result is published
function isResultPublished($con, $exam_type, $academic_year)
{
    $query = "SELECT publish_date FROM result_publication_dates 
              WHERE exam_type = $1 AND academic_year = $2";
    $result = pg_query_params($con, $query, [$exam_type, $academic_year]);

    if ($result && pg_num_rows($result) > 0) {
        $row = pg_fetch_assoc($result);
        $publish_date = strtotime($row['publish_date']);
        $current_date = time();

        return $current_date >= $publish_date;
    }

    // If no record exists, consider result as not published
    return false;
}

// Check if required parameters are provided
if (isset($_GET['student_id']) && isset($_GET['exam_type']) && isset($_GET['academic_year'])) {

    // Check if result is published
    $result_published = isResultPublished($con, $exam_type, $academic_year);

    if (!$result_published) {
        // Result not published yet
        echo '<div class="alert alert-info text-center">
                <i class="glyphicon glyphicon-time"></i>
                <h4>Result Not Published Yet</h4>
                <p>The result for ' . htmlspecialchars($exam_type) . ' exam of academic year ' . htmlspecialchars($academic_year) . ' has not been published yet.</p>
                <p>Please check back later or contact your school administration.</p>
              </div>';
        exit;
    }

    // Add this function to your result file if not already present
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

    // NEW: CHECK FEE DUE STATUS BEFORE DISPLAYING RESULT - USING YOUR EXACT CALCULATION LOGIC
    function getStudentFeeDueStatus($con, $student_id, $academic_year)
    {
        // First, check if student is active or inactive
        $status_query = "SELECT filterstatus, doa, class, type_of_admission FROM rssimyprofile_student 
                     WHERE student_id = '$student_id'";
        $status_result = pg_query($con, $status_query);

        if (!$status_result || pg_num_rows($status_result) === 0) {
            return 0; // Student not found
        }

        $student = pg_fetch_assoc($status_result);
        $is_inactive = ($student['filterstatus'] === 'Inactive');

        // For inactive students: use last attended month instead of current month
        if ($is_inactive) {
            // Get the last attendance date
            $last_attendance_query = "SELECT MAX(punch_in) as last_attended_date 
                                  FROM attendance 
                                  WHERE user_id = '$student_id'";
            $last_attendance_result = pg_query($con, $last_attendance_query);
            $last_attendance = pg_fetch_assoc($last_attendance_result);

            $lastAttendanceDate = $last_attendance['last_attended_date'] ?? $student['doa'];

            // If no attendance record, use admission date
            if (!$lastAttendanceDate) {
                $lastAttendanceDate = $student['doa'];
            }

            // If last attendance date is the 1st of the month, shift to previous month's last day
            if (date('d', strtotime($lastAttendanceDate)) == '01') {
                $lastAttendanceDate = date('Y-m-t', strtotime($lastAttendanceDate . ' -1 month'));
            }

            // Extract month and year from last attendance
            $current_month = date('m', strtotime($lastAttendanceDate));
            $current_year = date('Y', strtotime($lastAttendanceDate));
        } else {
            // For active students: use current month (original logic)
            $current_month = date('m');
            $current_year = date('Y');
        }

        $monthYear = $current_year . '-' . $current_month;
        list($year, $monthNumber) = explode('-', $monthYear);
        $month = date('F', strtotime("$year-$monthNumber-01"));

        $firstDayOfMonth = "$year-$monthNumber-01";
        $lastDayOfMonth = date('Y-m-t', strtotime($firstDayOfMonth));

        // Get student basic info
        $student_query = "SELECT s.student_id, s.studentname, s.class, s.doa, s.type_of_admission 
                      FROM rssimyprofile_student s 
                      WHERE s.student_id = '$student_id'";
        $student_result = pg_query($con, $student_query);

        if (!$student_result || pg_num_rows($student_result) === 0) {
            return 0; // Student not found, no due
        }

        $student = pg_fetch_assoc($student_result);

        // Get student info for the relevant month using your function
        $currentInfo = getStudentInfoForDate($con, $student_id, $firstDayOfMonth);
        $studentType = $currentInfo['category_type'];
        $currentClass = $currentInfo['class'] ?? $student['class'];

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

        // 3. Get current month's STUDENT-SPECIFIC fees
        $studentSpecificQuery = "SELECT fc.id, fc.category_name, ssf.amount, fc.fee_type
        FROM student_specific_fees ssf
        JOIN fee_categories fc ON ssf.category_id = fc.id
        WHERE ssf.student_id = '$student_id'
        AND '$firstDayOfMonth' BETWEEN ssf.effective_from AND COALESCE(ssf.effective_until, '9999-12-31')";

        $studentSpecificResult = pg_query($con, $studentSpecificQuery);
        $studentSpecificItems = pg_fetch_all($studentSpecificResult) ?? [];

        // 4. Calculate total student-specific fees
        $studentSpecificTotal = 0;
        foreach ($studentSpecificItems as $fee) {
            $studentSpecificTotal += $fee['amount'];
        }

        // 5. Combine both fee types
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
         WHERE student_id = '$student_id'
         AND month = '$month'
         AND academic_year = '$year'";

        $paymentsResult = pg_query($con, $paymentsQuery);
        $paymentData = pg_fetch_assoc($paymentsResult);
        $paidAmount = (float)($paymentData['paid_amount'] ?? 0);
        $corePaidAmount = (float)($paymentData['core_paid_amount'] ?? 0);

        // 7. Get current concessions
        $concessionQuery = "SELECT COALESCE(SUM(concession_amount), 0) as concession_amount
               FROM student_concessions
               WHERE student_id = '$student_id'
               AND '$firstDayOfMonth' BETWEEN effective_from AND COALESCE(effective_until, '9999-12-31')";
        $concessionResult = pg_query($con, $concessionQuery);
        $concessionAmount = (float)(pg_fetch_assoc($concessionResult)['concession_amount'] ?? 0);

        // 8. Calculate carry forward (previous months' unpaid dues) - YOUR EXACT LOGIC
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
                $historicalInfo = getStudentInfoForDate($con, $student_id, $loopMonthDate);
                $loopStudentType = $historicalInfo['category_type'];
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
               WHERE p.student_id = '$student_id'
               AND p.month = '$loopMonthName'
               AND p.academic_year = '$year'
               AND fc.category_name IN ('Admission Fee', 'Monthly Fee')";

                $loopPaymentsResult = pg_query($con, $loopPaymentsQuery);
                $loopPaidAmount = (float)(pg_fetch_assoc($loopPaymentsResult)['paid_amount'] ?? 0);

                $loopConcessionQuery = "SELECT COALESCE(SUM(concession_amount), 0) as concession_amount
                  FROM student_concessions
                  WHERE student_id = '$student_id'
                  AND '$year-$loopMonthNum-01' BETWEEN effective_from AND COALESCE(effective_until, '9999-12-31')";
                $loopConcessionResult = pg_query($con, $loopConcessionQuery);
                $loopConcessionAmount = (float)(pg_fetch_assoc($loopConcessionResult)['concession_amount'] ?? 0);

                $loopNetFee = $CombLoopTotalFee - $loopConcessionAmount;
                $loopDueAmount = $loopNetFee - $loopPaidAmount;

                $carryForward += $loopDueAmount;
            }
        }

        // 9. Calculate current month's net fee and due amount - YOUR EXACT FORMULA
        $netFee = ($totalCurrentMonthFees) - $concessionAmount;
        $dueAmount = ($netFee - $corePaidAmount) + $carryForward;

        return $dueAmount;
    }

    // Check if student has fee due
    $due_amount = getStudentFeeDueStatus($con, $student_id, $academic_year);
    $has_fee_due = $due_amount > 0;

    if ($has_fee_due) {
        // Fee due found - hold result and show contact message
        echo '<div class="alert alert-warning text-center">
        <i class="glyphicon glyphicon-exclamation-sign"></i>
        <h4>Fee Due Detected</h4>
        <p>We cannot display your result as there is an outstanding fee amount of <strong>₹' . number_format($due_amount, 2) . '</strong>.</p>
        <p>Please contact the Centre In-charge to clear your dues and access your result.</p>
      </div>';
        exit;
    }

    // Extract the start year from the academic year
    list($start_year, $end_year) = explode('-', $academic_year);
    $start_year = intval($start_year);

    // Determine the start and end dates based on the exam type and academic year
    $today_date = date('Y-m-d');
    if ($exam_type == 'First Term') {
        $start_date = "$start_year-04-01";
        $end_date = "$start_year-07-31";
    } elseif ($exam_type == 'Half Yearly') {
        $start_date = "$start_year-08-01";
        $end_date = "$start_year-11-30";
    } else {  // annual
        $start_date = "$start_year-12-01";
        $end_date = ($start_year + 1) . "-03-31";
    }
    $end_date = min($end_date, $today_date);

    // Fetch student details
    $student_query = "SELECT * FROM rssimyprofile_student WHERE student_id = $1";
    $student_result = pg_query_params($con, $student_query, [$student_id]);

    // Check if student exists
    if ($student_result && pg_num_rows($student_result) > 0) {
        $student_details = pg_fetch_assoc($student_result);
        $student_exists = true;

        // Fetch exam details and marks
        $marks_query = "
        SELECT 
            exam_marks_data.exam_id, 
            ROUND(exam_marks_data.viva_marks) AS viva_marks, 
            ROUND(exam_marks_data.written_marks) AS written_marks, 
            exams.subject, 
            exams.full_marks_written, 
            exams.full_marks_viva, 
            exams.exam_date_written, 
            exams.exam_date_viva, 
            student.doa,
            CASE
                WHEN attendance_written.attendance_status IS NULL 
                    AND student.doa <= exams.exam_date_written 
                    AND NOT EXISTS (
                        SELECT 1 
                        FROM reexamination 
                        WHERE reexamination.student_id = exam_marks_data.student_id 
                        AND reexamination.date = exams.exam_date_written
                    ) THEN 'A'
                ELSE attendance_written.attendance_status
            END AS written_attendance_status,
            CASE
                WHEN attendance_viva.attendance_status IS NULL 
                    AND student.doa <= exams.exam_date_viva 
                    AND NOT EXISTS (
                        SELECT 1 
                        FROM reexamination 
                        WHERE reexamination.student_id = exam_marks_data.student_id 
                        AND reexamination.date = exams.exam_date_viva
                    ) THEN 'A'
                ELSE attendance_viva.attendance_status
            END AS viva_attendance_status
        FROM exam_marks_data
        JOIN exams ON exam_marks_data.exam_id = exams.exam_id
        LEFT JOIN (
            SELECT user_id, date, 'P' AS attendance_status
            FROM attendance
            GROUP BY user_id, date
        ) AS attendance_written
        ON exam_marks_data.student_id = attendance_written.user_id 
        AND exams.exam_date_written = attendance_written.date
        LEFT JOIN (
            SELECT user_id, date, 'P' AS attendance_status
            FROM attendance
            GROUP BY user_id, date
        ) AS attendance_viva
        ON exam_marks_data.student_id = attendance_viva.user_id 
        AND exams.exam_date_viva = attendance_viva.date
        JOIN rssimyprofile_student student ON exam_marks_data.student_id = student.student_id
        WHERE exam_marks_data.student_id = $1 
        AND exams.exam_type = $2 
        AND exams.academic_year = $3";
        $marks_result = pg_query_params($con, $marks_query, [$student_id, $exam_type, $academic_year]);

        // Process and arrange marks data according to subject sequence
        if ($marks_result && pg_num_rows($marks_result) > 0) {
            $marks_data = [];
            while ($row = pg_fetch_assoc($marks_result)) {
                $marks_data[] = $row;
            }

            // Define the subject sequence
            $subject_sequence = ['Hindi', 'English', 'Mathematics', 'GK', 'Hamara Parivesh', 'Computer', 'Sulekh+Imla', 'Art & Craft'];
            $ordered_marks_data = [];
            $total_full_marks = 0;
            $total_obtained_marks = 0;

            // Arrange marks data according to subject sequence
            foreach ($subject_sequence as $subject) {
                foreach ($marks_data as $key => $data) {
                    if ($data['subject'] === $subject) {
                        $ordered_marks_data[] = $data;
                        $total_full_marks += $data['full_marks_written'] + $data['full_marks_viva'];
                        $total_obtained_marks += $data['written_marks'] + $data['viva_marks'];
                        unset($marks_data[$key]);
                    }
                }
            }

            // Add any remaining subjects that were not in the defined sequence
            foreach ($marks_data as $data) {
                $ordered_marks_data[] = $data;
                $total_full_marks += $data['full_marks_written'] + $data['full_marks_viva'];
                $total_obtained_marks += $data['written_marks'] + $data['viva_marks'];
            }
        } else {
            $no_records_found = true;
        }
    }
}

// SQL query to fetch class and category from exam_marks_data based on filter criteria
$class_category_query = "
    SELECT emd.class, emd.category 
    FROM exam_marks_data emd
    INNER JOIN exams e ON emd.exam_id = e.exam_id
    WHERE emd.student_id = $1 
      AND e.exam_type = $2 
      AND e.academic_year = $3
    LIMIT 1;";

// Execute query
$class_category_result = pg_query_params($con, $class_category_query, [$student_id, $exam_type, $academic_year]);
$class_category_data = pg_fetch_assoc($class_category_result);

// Calculate percentage and grade
if (isset($total_obtained_marks) && isset($total_full_marks) && $total_full_marks > 0) {
    $percentage = ($total_obtained_marks / $total_full_marks) * 100;
    $formattedPercentage = number_format($percentage, 2);

    // Determine latest exam date (written & viva)
    $written_date = null;
    $viva_date = null;

    foreach ($ordered_marks_data as $row) {
        if (!empty($row['exam_date_written']) && $row['exam_date_written'] !== '0000-00-00') {
            $written_date = $row['exam_date_written'];
        }
        if (!empty($row['exam_date_viva']) && $row['exam_date_viva'] !== '0000-00-00') {
            $viva_date = $row['exam_date_viva'];
        }
    }

    $latest_exam_date = max($written_date, $viva_date);

    // Fetch applicable grade rule based on latest exam date
    $rule_query = "
        SELECT rule_id
        FROM grade_rules
        WHERE valid_from <= $1
        AND (valid_to IS NULL OR valid_to >= $1)
        ORDER BY valid_from DESC
        LIMIT 1
    ";
    $rule_result = pg_query_params($con, $rule_query, [$latest_exam_date]);

    if ($rule_result && pg_num_rows($rule_result) > 0) {
        $rule_row = pg_fetch_assoc($rule_result);
        $rule_id = $rule_row['rule_id'];
    } else {
        $rule_id = null;
    }

    // Fetch grade & description from grade_rule_details
    if ($rule_id !== null) {
        $grade_query = "
            SELECT grade, description
            FROM grade_rule_details
            WHERE rule_id = $1
            AND $2 BETWEEN min_percentage AND max_percentage
            LIMIT 1
        ";
        $grade_result = pg_query_params($con, $grade_query, [$rule_id, $percentage]);

        if ($grade_result && pg_num_rows($grade_result) > 0) {
            $grade_row = pg_fetch_assoc($grade_result);
            $grade = $grade_row['grade'];
            $gradeDescription = $grade_row['description'];
        } else {
            $grade = "N/A";
            $gradeDescription = "Grade not defined";
        }
    } else {
        $grade = "N/A";
        $gradeDescription = "No grading rule applied";
    }

    // Fetch fail cutoff directly from DB
    $fail_query = "
        SELECT max_percentage
        FROM grade_rule_details
        WHERE rule_id = $1
        AND LOWER(description) = 'fail'
    ";

    $fail_result = pg_query_params($con, $fail_query, [$rule_id]);

    $failMax = 0;

    if ($fail_result) {
        while ($row = pg_fetch_assoc($fail_result)) {
            if ($row['max_percentage'] > $failMax) {
                $failMax = $row['max_percentage'];
            }
        }
    }

    // Determine Pass / Fail
    $passOrFail = ($percentage > $failMax) ? 'Pass' : 'Fail';
}

// Output the result HTML
?>
<?php if ($student_exists && !$no_records_found && isset($marks_result)): ?>
    <div class="result-card">
        <!-- Student Information -->
        <div class="panel panel-info">
            <div class="panel-heading">
                <h4 class="panel-title">Student Information</h4>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-3"><strong>Student ID:</strong> <?php echo htmlspecialchars($student_details['student_id']); ?></div>
                    <div class="col-md-3"><strong>Student Name:</strong> <?php echo htmlspecialchars($student_details['studentname']); ?></div>
                    <div class="col-md-3"><strong>Class:</strong> <?php echo htmlspecialchars($class_category_data['category'] . '/' . $class_category_data['class']); ?></div>
                    <div class="col-md-3"><strong>Exam:</strong> <?php echo htmlspecialchars($exam_type); ?> <?php echo htmlspecialchars($academic_year); ?></div>
                </div>
            </div>
        </div>

        <!-- Marks Table -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">Subject-wise Marks</h4>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr class="bg-primary text-white">
                                <th>Subject</th>
                                <th>Full Marks</th>
                                <th>Written Marks</th>
                                <th>Viva Marks</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $grandFullMarks = 0;
                            $grandWrittenMarks = 0;
                            $grandVivaMarks = 0;
                            $grandTotalMarks = 0;

                            foreach ($ordered_marks_data as $row):
                                $written_marks = ($row['written_attendance_status'] == 'A') ? 'A' : $row['written_marks'];
                                $viva_marks = ($row['viva_attendance_status'] == 'A') ? 'A' : $row['viva_marks'];

                                // Calculate total marks
                                if ($row['written_attendance_status'] == 'A' && $row['viva_attendance_status'] != 'A') {
                                    $total_marks = $row['viva_marks'];
                                } elseif ($row['viva_attendance_status'] == 'A' && $row['written_attendance_status'] != 'A') {
                                    $total_marks = $row['written_marks'];
                                } elseif ($row['written_attendance_status'] != 'A' && $row['viva_attendance_status'] != 'A') {
                                    $total_marks = $row['written_marks'] + $row['viva_marks'];
                                } else {
                                    $total_marks = 0;
                                }

                                $subjectFullMarks = $row['full_marks_written'] + $row['full_marks_viva'];

                                $grandFullMarks += $subjectFullMarks;
                                if ($written_marks !== 'A') $grandWrittenMarks += $written_marks;
                                if ($viva_marks !== 'A') $grandVivaMarks += $viva_marks;
                                $grandTotalMarks += $total_marks;
                            ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['subject']); ?></strong></td>
                                    <td><?php echo $subjectFullMarks; ?></td>
                                    <td><?php echo $written_marks; ?></td>
                                    <td><?php echo $viva_marks; ?></td>
                                    <td><?php echo $total_marks; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="bg-warning">
                                <td><strong>Grand Total</strong></td>
                                <td><strong><?php echo $grandFullMarks; ?></strong></td>
                                <td><strong><?php echo $grandWrittenMarks; ?></strong></td>
                                <td><strong><?php echo $grandVivaMarks; ?></strong></td>
                                <td><strong><?php echo $grandTotalMarks; ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Result Summary -->
        <div class="panel panel-success">
            <div class="panel-heading">
                <h4 class="panel-title">Result Summary</h4>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-3">
                        <strong>Marks Obtained:</strong><br>
                        <?php echo $total_obtained_marks; ?>/<?php echo $total_full_marks; ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Percentage:</strong><br>
                        <?php echo $formattedPercentage; ?>%
                    </div>
                    <div class="col-md-3">
                        <strong>Grade:</strong><br>
                        <?php echo $grade . ' - ' . $gradeDescription; ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Result:</strong><br>
                        <span class="label label-<?php echo $passOrFail == 'Pass' ? 'success' : 'danger'; ?>">
                            <?php echo $passOrFail; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-warning text-center">
        <i class="glyphicon glyphicon-warning-sign"></i>
        <h4>No Result Found</h4>
        <p>Please check your Student ID, Exam Type, and Academic Year</p>
    </div>
<?php endif; ?>