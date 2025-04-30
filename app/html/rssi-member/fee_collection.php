<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}
validation();

function getStudentTypeForDate($con, $studentId, $targetDate)
{
    // Convert target date to first day of month
    $targetMonthStart = date('Y-m-01', strtotime($targetDate));

    // First try to get from history table for the month
    $query = "SELECT category_type 
              FROM student_category_history 
              WHERE student_id = $1 
              AND (
                  (effective_from <= '$targetMonthStart' AND (effective_until >= '$targetMonthStart' OR effective_until IS NULL))
                  OR
                  (TO_CHAR(effective_from, 'YYYY-MM') = TO_CHAR('$targetMonthStart'::date, 'YYYY-MM'))
              )
              ORDER BY effective_from DESC 
              LIMIT 1";

    $result = pg_query_params($con, $query, array($studentId));
    if ($row = pg_fetch_assoc($result)) {
        return $row['category_type'];
    }

    // Directly fetch the type_of_admission from the database
    $originalTypeQuery = "SELECT type_of_admission FROM rssimyprofile_student WHERE student_id = $1";
    $originalTypeResult = pg_query_params($con, $originalTypeQuery, array($studentId));
    if ($originalType = pg_fetch_assoc($originalTypeResult)) {
        return $originalType['type_of_admission'];
    }

    // If no record is found, handle the case (optional)
    return null; // Or handle it differently, e.g., return 'Unknown'
}

// Get filter parameters
$status = $_GET['status'] ?? 'Active';
$month = $_GET['month'] ?? date('F');
$year = $_GET['year'] ?? date('Y');
$class = $_GET['class'] ?? [];
$search_term = $_GET['search_term'] ?? '';

// Handle class parameter - could be string or array
if (!is_array($class) && !empty($class)) {
    $class = [$class];
} elseif (empty($class)) {
    $class = [];
}

// Convert month name to number and get date range
$monthNumber = date('m', strtotime("$month 1, $year"));
$firstDayOfMonth = "$year-$monthNumber-01";
$lastDayOfMonth = date('Y-m-t', strtotime($firstDayOfMonth));

// After getting filter parameters, add this check:
$hasFilters = !empty($class) || !empty($search_term);

// Then modify the student data query section:
if ($hasFilters) {
    // Get student data
    $query = "SELECT s.student_id, s.studentname, s.category, s.class, s.doa, 
                     s.type_of_admission, s.filterstatus, s.effectivefrom
              FROM rssimyprofile_student s
              WHERE s.filterstatus = '$status'
              AND (s.doa <= '$lastDayOfMonth' AND 
                  (s.filterstatus = 'Active' OR 
                   (s.filterstatus = 'Inactive' AND s.effectivefrom > '$firstDayOfMonth')))";

    // Add class filter if classes are selected
    if (!empty($class)) {
        $escapedClasses = array_map(function ($c) use ($con) {
            return pg_escape_string($con, $c);
        }, $class);
        $classList = implode("','", $escapedClasses);
        $query .= " AND s.class IN ('$classList')";
    }

    // Add search term filter if provided
    if (!empty($search_term)) {
        $escaped_search = pg_escape_string($con, $search_term);
        $query .= " AND (s.student_id = '$escaped_search' OR s.studentname ILIKE '%$escaped_search%')";
    }

    $query .= " ORDER BY s.class, s.studentname";

    $result = pg_query($con, $query);
    $students = pg_fetch_all($result) ?? [];
} else {
    $students = []; // Empty array if no filters
}

// Get fee categories
$categories = pg_fetch_all(pg_query(
    $con,
    "SELECT id, category_name, fee_type 
     FROM fee_categories 
     WHERE is_active = TRUE 
     ORDER BY id"
)) ?? [];


// Process each student to calculate fees
$processedStudents = [];
if ($hasFilters) {
    foreach ($students as $student) {
        $studentId = $student['student_id'];

        // Get student type for the current month being processed
        $studentType = getStudentTypeForDate($con, $studentId, $firstDayOfMonth);

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
                WHERE fs.class = '{$student['class']}'
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
                            WHERE category_name IN ('Admission Fee', 'Monthly Fee', 'Miscellaneous', 'Exam Fee')
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

            for ($m = $startMonth; $m <= $endMonth; $m++) {
                $loopMonthNum = str_pad($m, 2, '0', STR_PAD_LEFT);
                $loopMonthName = date('F', mktime(0, 0, 0, $m, 1));
                $loopMonthDate = "$year-$loopMonthNum-01";

                // Get student type for this historical month
                $loopStudentType = getStudentTypeForDate($con, $studentId, $loopMonthDate);

                // Get month's fees
                $loopFeeQuery = "SELECT COALESCE(SUM(fs.amount), 0) as total_fee
                           FROM fee_structure fs
                           JOIN fee_categories fc ON fs.category_id = fc.id
                           WHERE fs.class = '{$student['class']}'
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

                // Get month's STUDENT-SPECIFIC fees
                $loopStudentSpecificQuery = "SELECT COALESCE(SUM(ssf.amount), 0) as total_fee
                                      FROM student_specific_fees ssf
                                      JOIN fee_categories fc ON ssf.category_id = fc.id
                                      WHERE ssf.student_id = '{$student['student_id']}'
                                      AND '$year-$loopMonthNum-01' BETWEEN ssf.effective_from AND COALESCE(ssf.effective_until, '9999-12-31')";
                $loopStudentSpecificResult = pg_query($con, $loopStudentSpecificQuery);
                $loopStudentSpecificFee = (float)(pg_fetch_assoc($loopStudentSpecificResult)['total_fee'] ?? 0);

                // Combine both fee types
                $CombLoopTotalFee = $loopTotalFee + $loopStudentSpecificFee;

                // Get month's payments for core categories (Admission, Monthly, Miscellaneous)
                $loopPaymentsQuery = "SELECT COALESCE(SUM(p.amount), 0) as paid_amount
                               FROM fee_payments p
                               JOIN fee_categories fc ON p.category_id = fc.id
                               WHERE p.student_id = '$studentId'
                               AND p.month = '$loopMonthName'
                               AND p.academic_year = '$year'
                               AND fc.category_name IN ('Admission Fee', 'Monthly Fee', 'Miscellaneous', 'Exam Fee')";

                $loopPaymentsResult = pg_query($con, $loopPaymentsQuery);
                $loopPaidAmount = (float)(pg_fetch_assoc($loopPaymentsResult)['paid_amount'] ?? 0);

                // Get month's concessions
                $loopConcessionQuery = "SELECT COALESCE(SUM(concession_amount), 0) as concession_amount
                                  FROM student_concessions
                                  WHERE student_id = '$studentId'
                                  AND '$year-$loopMonthNum-01' BETWEEN effective_from AND COALESCE(effective_until, '9999-12-31')";
                $loopConcessionResult = pg_query($con, $loopConcessionQuery);
                $loopConcessionAmount = (float)(pg_fetch_assoc($loopConcessionResult)['concession_amount'] ?? 0);

                // Calculate month's due
                $loopNetFee = $CombLoopTotalFee - $loopConcessionAmount;
                $loopDueAmount = $loopNetFee - $loopPaidAmount;

                // Add to carry forward if positive
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
            'doa' => date('d-M-Y', strtotime($student['doa'])),
            'student_type' => $studentType,
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

// Get summary data
// Update the summary section to:
$summary = [
    'total_students' => $hasFilters ? count($processedStudents) : 0,
    'total_fee' => $hasFilters ? array_sum(array_column($processedStudents, 'total_fee')) : 0,
    'total_concession' => $hasFilters ? array_sum(array_column($processedStudents, 'concession_amount')) : 0,
    'total_net_fee' => $hasFilters ? array_sum(array_column($processedStudents, 'net_fee')) : 0,
    'total_paid' => $hasFilters ? array_sum(array_column($processedStudents, 'core_paid_amount')) : 0,
    'total_due' => $hasFilters ? array_sum(array_column($processedStudents, 'due_amount')) : 0,
    'total_carry_forward' => $hasFilters ? array_sum(array_column($processedStudents, 'carry_forward')) : 0
];

// Get classes for filter
$classQuery = "SELECT DISTINCT class FROM rssimyprofile_student ORDER BY class";
$classResult = pg_query($con, $classQuery);
$classes = pg_fetch_all($classResult) ?? [];

// Get collectors
$collectorsQuery = "SELECT associatenumber, fullname FROM rssimyaccount_members WHERE filterstatus='Active' ORDER BY fullname";
$collectorsResult = pg_query($con, $collectorsQuery);
$collectors = pg_fetch_all($collectorsResult) ?? [];
?>
<?php
// Check fee sheet lock status - treat missing records as LOCKED (default)
$lockQuery = "SELECT is_locked FROM fee_collection_lock WHERE month = $1 AND year = $2";
$lockResult = pg_query_params($con, $lockQuery, [$month, $year]);

// Default to LOCKED (true) if no record exists
$isLocked = true; // Changed from false to true for default state
if ($lockStatus = pg_fetch_assoc($lockResult)) {
    $isLocked = ($lockStatus['is_locked'] === 't');
    // This will now only set to false if there's an explicit record with is_locked = false
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=AW-11316670180"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'AW-11316670180');
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Fee Collection System</title>
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .due-positive {
            color: #dc3545;
            font-weight: bold;
        }

        .due-zero {
            color: #28a745;
            font-weight: bold;
        }

        .summary-card {
            border-left: 5px solid;
            margin-bottom: 20px;
        }

        .summary-card.total {
            border-color: #007bff;
        }

        .summary-card.fee {
            border-color: #6f42c1;
        }

        .summary-card.concession {
            border-color: #fd7e14;
        }

        .summary-card.net {
            border-color: #28a745;
        }

        .summary-card.paid {
            border-color: #17a2b8;
        }

        .summary-card.due {
            border-color: #dc3545;
        }

        .fee-category {
            white-space: nowrap;
        }

        .table th {
            position: sticky;
            top: 0;
            background: white;
        }


        .prebanner {
            display: none;
        }

        .back-to-top {
            position: fixed;
            visibility: hidden;
            opacity: 0;
            right: 15px;
            bottom: 15px;
            z-index: 99999;
            background: #4154f1;
            width: 40px;
            height: 40px;
            border-radius: 4px;
            transition: all 0.4s;
        }

        .back-to-top i {
            font-size: 24px;
            color: #fff;
            line-height: 0;
        }

        .back-to-top:hover {
            background: #6776f4;
            color: #fff;
        }

        .back-to-top.active {
            visibility: visible;
            opacity: 1;
        }

        .fee-notice {
            background-color: #f8f9fa;
            border-left: 4px solid #d9d9d9 !important;
            padding: 12px;
            font-size: 0.95rem;
        }

        #fee-collection-card .card-title {
            padding: 0;
            /* or correct padding value */
            color: var(--bs-card-title-color);
        }
    </style>
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
    <!------ Include the above in your HEAD tag ---------->
    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>

    <!-- Glow Cookies v3.0.1 -->
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            //facebookPixel: '',
            policyLink: 'https://www.rssi.in/disclaimer'
        });
    </script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Settlement Management</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Fee Portal</a></li>
                    <li class="breadcrumb-item active">Settlement Management</li>
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
                            <div class="container-fluid mt-4">
                                <div class="card">
                                    <div class="card-header bg-primary text-white" style="display: flex; justify-content: space-between; align-items: center;" id="fee-collection-card">
                                        <h3 class="card-title"><i class="fas fa-money-bill-wave"></i> Monthly Fee Collection - <?= $month ?> <?= $year ?></h3>
                                    </div>
                                    <div class="card-body">
                                        <!-- Updated Filters Form -->
                                        <form method="get" class="row g-3 mb-4 mt-4">
                                            <div class="col-md-2">
                                                <select name="status" class="form-select">
                                                    <option value="Active" <?= $status == 'Active' ? 'selected' : '' ?>>Active Students</option>
                                                    <option value="Inactive" <?= $status == 'Inactive' ? 'selected' : '' ?>>Inactive Students</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <select name="month" class="form-select">
                                                    <?php foreach (['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'] as $m): ?>
                                                        <option value="<?= $m ?>" <?= $month == $m ? 'selected' : '' ?>><?= $m ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <select name="year" class="form-select">
                                                    <?php for ($y = date('Y') - 1; $y <= date('Y') + 1; $y++): ?>
                                                        <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <select name="class[]" class="form-select" multiple="multiple" id="classSelect">
                                                    <?php foreach ($classes as $classItem): ?>
                                                        <option value="<?= $classItem['class'] ?>" <?= in_array($classItem['class'], $class) ? 'selected' : '' ?>>
                                                            <?= $classItem['class'] ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="text" name="search_term" class="form-control" placeholder="Search by Student ID or Name" value="<?= htmlspecialchars($search_term) ?>">
                                            </div>
                                            <div class="col-md-1">
                                                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter"></i> Filter</button>
                                            </div>
                                            <div class="col-md-1">
                                                <button type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#concessionModal">
                                                    <i class="fas fa-percentage"></i> Concession
                                                </button>
                                            </div>
                                        </form>

                                        <!-- Summary Cards -->
                                        <div class="row mb-4">
                                            <div class="col-md-2">
                                                <div class="card summary-card total">
                                                    <div class="card-body">
                                                        <h6 class="card-title">Total Students</h6>
                                                        <p class="card-text h4"><?= $summary['total_students'] ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="card summary-card fee">
                                                    <div class="card-body">
                                                        <h6 class="card-title">Total Fee</h6>
                                                        <p class="card-text h4">₹<?= number_format($summary['total_fee'], 2) ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="card summary-card concession">
                                                    <div class="card-body">
                                                        <h6 class="card-title">Total Concession</h6>
                                                        <p class="card-text h4">₹<?= number_format($summary['total_concession'], 2) ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="card summary-card net">
                                                    <div class="card-body">
                                                        <h6 class="card-title">Net Fee</h6>
                                                        <p class="card-text h4">₹<?= number_format(($summary['total_net_fee'] - $summary['total_concession']), 2) ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="card summary-card paid">
                                                    <div class="card-body">
                                                        <h6 class="card-title">Total Paid</h6>
                                                        <p class="card-text h4">₹<?= number_format($summary['total_paid'], 2) ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="card summary-card due">
                                                    <div class="card-body">
                                                        <h6 class="card-title">Total Due</h6>
                                                        <p class="card-text h4">₹<?= number_format($summary['total_due'], 2) ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-success w-100" id="exportReport">
                                                <i class="fas fa-file-excel"></i> Export Report
                                            </button>
                                        </div>
                                        <?php if ($isLocked): ?>
                                            <div class="alert alert-warning mt-3">
                                                <i class="fas fa-lock"></i> Fee collection is currently locked for <?= $month ?> <?= $year ?>.
                                                Please contact administration to unlock.
                                            </div>
                                        <?php endif; ?>
                                        <!-- Replace the table section with this: -->
                                        <?php if (!$hasFilters && empty($_GET)): ?>
                                            <div class="alert alert-info mt-3">
                                                <i class="fas fa-info-circle"></i> Please select at least one class or enter a student ID to view fee data.
                                            </div>
                                        <?php elseif (empty($processedStudents)): ?>
                                            <div class="alert alert-warning mt-3">
                                                <i class="fas fa-exclamation-triangle"></i> No students found matching your criteria.
                                            </div>
                                        <?php else: ?>
                                            <!-- Student List -->
                                            <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                                                <table class="table table-striped table-hover table-bordered" id="table-id">
                                                    <thead>
                                                        <tr>
                                                            <th>Student ID</th>
                                                            <th>Name</th>
                                                            <th>Class</th>
                                                            <th>Category</th>
                                                            <th>DOA</th>
                                                            <th>Type</th>
                                                            <?php foreach ($categories as $category): ?>
                                                                <?php if (in_array($category['category_name'], ['Admission Fee', 'Monthly Fee', 'Miscellaneous'])): ?>
                                                                    <th class="fee-category"><?= $category['category_name'] ?></th>
                                                                <?php endif; ?>
                                                            <?php endforeach; ?>
                                                            <th>Concession</th>
                                                            <th>Carry Forward</th>
                                                            <th>Net Fee</th>
                                                            <th>Paid</th>
                                                            <th>Due</th>
                                                            <th>Other Charges Paid</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($processedStudents as $student): ?>
                                                            <tr>
                                                                <td><?= $student['student_id'] ?></td>
                                                                <td><?= htmlspecialchars($student['studentname']) ?></td>
                                                                <td><?= $student['class'] ?></td>
                                                                <td><?= $student['category'] ?></td>
                                                                <td><?= $student['doa'] ?></td>
                                                                <td><?= $student['student_type'] ?></td>
                                                                <td class="text-end">
                                                                    <?= $student['admission_fee'] > 0 ? '₹' . number_format($student['admission_fee'], 2) : '-' ?>
                                                                </td>
                                                                <td class="text-end">₹<?= number_format($student['monthly_fee'], 2) ?></td>
                                                                <td class="text-end">
                                                                    <?php
                                                                    $standardMisc = $student['miscellaneous'] ?? 0;
                                                                    $studentSpecific = $student['student_specific_fees'] ?? 0;
                                                                    $totalMisc = $standardMisc + $studentSpecific;

                                                                    if ($totalMisc > 0) {
                                                                        echo '₹' . number_format($totalMisc, 2);

                                                                        // Build tooltip content as array
                                                                        $tooltipLines = [];

                                                                        // Add standard misc if exists
                                                                        if ($standardMisc > 0) {
                                                                            $tooltipLines[] = 'Standard: ₹' . number_format($standardMisc, 2);
                                                                        }

                                                                        // Add student-specific details if exists
                                                                        if ($studentSpecific > 0 && !empty($student['student_specific_details'])) {
                                                                            $tooltipLines[] = 'Student-specific:';
                                                                            foreach ($student['student_specific_details'] as $detail) {
                                                                                $tooltipLines[] = '• ' . htmlspecialchars($detail['category']) . ': ₹' .
                                                                                    number_format($detail['amount'], 2);
                                                                            }
                                                                        }

                                                                        // Show tooltip if we have content
                                                                        if (!empty($tooltipLines)) {
                                                                            // Join with newlines (will be converted to <br> by Bootstrap)
                                                                            $tooltipContent = htmlspecialchars(implode("\n", $tooltipLines));
                                                                            echo ' <span class="text-muted small" data-bs-toggle="tooltip" data-html="true" 
                                                                            title="' . str_replace("\n", "&#10;", $tooltipContent) . '">
                                                                            <i class="fas fa-info-circle"></i></span>';
                                                                        }
                                                                    } else {
                                                                        echo '-';
                                                                    }
                                                                    ?>
                                                                </td>
                                                                <td class="text-end">
                                                                    <?= $student['concession_amount'] > 0 ? '₹' . number_format($student['concession_amount'], 2) : '-' ?>
                                                                </td>
                                                                <td class="text-end">
                                                                    <?= '₹' . number_format($student['carry_forward'], 2) ?>
                                                                </td>
                                                                <td class="text-end">₹<?= number_format(($student['net_fee'] - $student['concession_amount']), 2) ?></td>
                                                                <td class="text-end">₹<?= number_format($student['core_paid_amount'], 2) ?></td>
                                                                <td class="text-end <?= $student['due_amount'] > 0 ? 'text-danger fw-bold' : 'text-success fw-bold' ?>">
                                                                    ₹<?= number_format(abs($student['due_amount']), 2) ?>
                                                                    <?= $student['due_amount'] < 0 ? ' (Cr)' : '' ?>
                                                                </td>
                                                                <td class="text-end">₹<?= number_format(($student['paid_amount'] - $student['core_paid_amount']), 2) ?></td>
                                                                <td>
                                                                    <button class="btn btn-sm btn-primary collect-fee"
                                                                        data-student-id="<?= $student['student_id'] ?>"
                                                                        data-student-name="<?= htmlspecialchars($student['studentname']) ?>"
                                                                        data-student-class="<?= htmlspecialchars($student['class']) ?>"
                                                                        data-net-fee="<?= $student['net_fee'] ?>"
                                                                        data-due-amount="<?= $student['due_amount'] ?>"
                                                                        <?= $isLocked ? 'disabled title="Fee collection is locked for this month"' : '' ?>>
                                                                        <i class="fas fa-hand-holding-usd"></i> Collect
                                                                    </button>

                                                                    <button class="btn btn-sm btn-info view-history"
                                                                        data-student-id="<?= $student['student_id'] ?>">
                                                                        <i class="fas fa-history"></i> History
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endif; ?>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

    <script>
        $(document).ready(function() {
            // Check if resultArr is empty
            <?php if (!empty($categories)) : ?>
                // Initialize DataTables only if resultArr is not empty
                $('#table-id').DataTable({
                    paging: false,
                    "order": [] // Disable initial sorting
                    // other options...
                });
            <?php endif; ?>
        });
    </script>

    <!-- Payment History Modal -->
    <div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="historyModalLabel">Payment History</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="historyContent"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add this modal right after the payment modal in your HTML -->
    <!-- Concession Modal -->
    <div class="modal fade" id="concessionModal" tabindex="-1" aria-labelledby="concessionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title" id="concessionModalLabel">Add Concession</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="concessionForm">
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="concessionStudentId" class="form-label">Student ID</label>
                                <select class="form-select" id="concessionStudentId" name="student_id" required>
                                    <option value="">Select Student</option>
                                    <?php foreach ($processedStudents as $student): ?>
                                        <option value="<?= $student['student_id'] ?>">
                                            <?= $student['student_id'] ?> - <?= $student['studentname'] ?> (<?= $student['class'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="concessionReason" class="form-label">Reason</label>
                                <textarea class="form-control" id="concessionReason" name="reason" rows="3" required></textarea>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="concessionFrom" class="form-label">Effective From</label>
                                <input type="date" class="form-control" id="concessionFrom" name="effective_from"
                                    value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="concessionUntil" class="form-label">Effective Until (optional)</label>
                                <input type="date" class="form-control" id="concessionUntil" name="effective_until">
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Fee Category</th>
                                        <th>Concession Amount</th>
                                    </tr>
                                </thead>
                                <tbody id="concessionCategories">
                                    <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td><?= $category['category_name'] ?>
                                                <input type="hidden" name="category_ids[]" value="<?= $category['id'] ?>">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control concession-amount"
                                                    name="concession_amounts[]"
                                                    min="0" step="0.01" value="0.00">
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-warning">Save Concession</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            $('#concessionForm').on('submit', function(e) {
                e.preventDefault();

                var form = $(this);
                // Include all parameters in the form data
                var formData = form.serialize() +
                    '&status=' + encodeURIComponent(new URLSearchParams(window.location.search).get('status') || '') +
                    '&month=' + encodeURIComponent(new URLSearchParams(window.location.search).get('month') || '') +
                    '&year=' + encodeURIComponent(new URLSearchParams(window.location.search).get('year') || '') +
                    '&class=' + encodeURIComponent(new URLSearchParams(window.location.search).get('class') || '');

                var submitBtn = form.find('button[type="submit"]');
                var originalText = submitBtn.html();

                // Show loading state
                submitBtn.prop('disabled', true).html(`
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            Processing...
        `);

                // AJAX request
                $.ajax({
                    url: 'process_concession.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Redirect with all parameters
                            window.location.href = response.redirect;
                        } else {
                            alert(response.message);
                            submitBtn.prop('disabled', false).html(originalText);
                        }
                    },
                    error: function(xhr) {
                        alert('Request failed: ' + (xhr.responseJSON?.message || xhr.statusText || 'Unknown error'));
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });
        });
    </script>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="paymentModalLabel">Collect Fee</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" id="paymentForm">
                    <input type="hidden" name="submit_payment" value="1">
                    <input type="hidden" id="paymentStudentId" name="student_id">
                    <input type="hidden" id="paymentMonth" name="month" value="<?= $month ?>">
                    <input type="hidden" id="paymentYear" name="year" value="<?= $year ?>">

                    <div class="modal-body">
                        <!-- Student Info -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Student Name:</label>
                                <div class="form-control-plaintext fw-bold" id="paymentStudentName"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Class:</label>
                                <div class="form-control-plaintext fw-bold" id="paymentStudentClass"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Due Amount:</label>
                                <div class="form-control-plaintext fw-bold" id="paymentDueAmount"></div>
                            </div>
                        </div>

                        <!-- Fee Breakdown -->
                        <div class="fee-table-container d-none">
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <h5>Fee Collection</h5>
                                    <!-- Fee Categories Notice -->
                                    <div class="fee-notice mb-4 p-3 bg-light border-start border-4 border-primary">
                                        <p class="mb-0 text-muted">
                                            Dues can be collected under Admission, Monthly, Exam, or Miscellaneous fees.
                                        </p>
                                    </div>
                                    <div id="feeActions" class="d-flex justify-content-end mb-3"></div>
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Category</th>
                                                <th>Pay Now</th>
                                            </tr>
                                        </thead>
                                        <tbody id="feeBreakdown">
                                            <!-- Populated by JavaScript -->
                                        </tbody>
                                        <tfoot>
                                            <tr class="table-primary">
                                                <th>Total Due</th>
                                                <th id="totalDueAmount">₹0.00</th>
                                            </tr>
                                            <tr class="table-primary">
                                                <th>Total Pay Now</th>
                                                <th id="totalPayNow">₹0.00</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Details -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="paymentType" class="form-label">Payment Type:</label>
                                <select class="form-select" id="paymentType" name="payment_type" required>
                                    <option value="cash">Cash</option>
                                    <option value="online">Online</option>
                                </select>
                            </div>
                            <div class="col-md-4" id="transactionIdContainer">
                                <label for="transactionId" class="form-label">Reference No:</label>
                                <input type="text" class="form-control" id="transactionId" name="transaction_id">
                            </div>
                            <div class="col-md-4">
                                <label for="paymentDate" class="form-label">Payment Date:</label>
                                <input type="date" class="form-control" id="paymentDate" name="payment_date" value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="collectorId" class="form-label">Collector:</label>
                                <select class="form-select" id="collectorId" name="collected_by" required <?= ($role !== 'Admin') ? 'disabled' : '' ?>>
                                    <?php if ($role === 'Admin'): ?>
                                        <?php foreach ($collectors as $collector): ?>
                                            <option value="<?= $collector['associatenumber'] ?>" <?= $collector['associatenumber'] == $associatenumber ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($collector['fullname']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="<?= $associatenumber ?>" selected>
                                            <?= htmlspecialchars($fullname) ?>
                                        </option>
                                    <?php endif; ?>
                                </select>
                                <?php if ($role !== 'Admin'): ?>
                                    <input type="hidden" name="collected_by" value="<?= $associatenumber ?>">
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label for="paymentNotes" class="form-label">Notes:</label>
                                <textarea class="form-control" id="paymentNotes" name="notes" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Submit Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            let allCategories = []; // Store all categories for reference
            let currentDueAmount = 0; // Store the due amount from the button

            // Collect fee button handler
            $(".collect-fee").click(function() {
                const studentId = $(this).data("student-id");
                const studentName = $(this).data("student-name");
                const studentClass = $(this).data("student-class");
                currentDueAmount = parseFloat($(this).data("due-amount")) || 0;

                // Set basic info
                $("#paymentStudentId").val(studentId);
                $("#paymentStudentName").text(studentName);
                $("#paymentStudentClass").text(studentClass);

                // Set due amount with proper formatting and color
                const dueAmountElement = $("#paymentDueAmount");
                const formattedDueAmount = "₹" + Math.abs(currentDueAmount).toFixed(2) + (currentDueAmount < 0 ? " (Cr)" : "");
                dueAmountElement.text(formattedDueAmount);
                dueAmountElement.removeClass("text-danger text-success fw-bold")
                    .addClass(currentDueAmount > 0 ? "text-danger fw-bold" : "text-success fw-bold");

                // Hide table initially
                $(".fee-table-container").addClass("d-none");

                // Show loading state
                const loadingHtml = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading fee details...</p>
                </div>
            `;
                $(".fee-table-container").before(loadingHtml);

                // Fetch fee breakdown via AJAX
                $.ajax({
                    url: "get_fee_breakdown.php",
                    method: "GET",
                    data: {
                        student_id: studentId,
                        month: "<?= $month ?>",
                        year: "<?= $year ?>"
                    },
                    dataType: "json",
                    success: function(response) {
                        // Remove loading indicator
                        $(".fee-table-container").prev().remove();

                        const breakdown = $("#feeBreakdown");
                        breakdown.empty();

                        let totalPayNow = 0;

                        // Populate categories
                        allCategories = response.categories;

                        response.categories.forEach((category) => {
                            const row = `
                            <tr class="fee-category-row" data-category-id="${category.id}">
                                <td>${category.name}</td>
                                <td>
                                    <div class="input-group">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" class="form-control pay-now text-end" 
                                               data-category-id="${category.id}" 
                                               name="payment_amounts[${category.id}]"
                                               value="0.00"
                                               min="0"
                                               step="0.01">
                                    </div>
                                </td>
                            </tr>
                        `;
                            breakdown.append(row);
                        });

                        // Show table now that data is loaded
                        $(".fee-table-container").removeClass("d-none");

                        // Update Total Due Amount with color coding
                        $("#totalDueAmount")
                            .html(formattedDueAmount)
                            .removeClass("text-danger text-success fw-bold")
                            .addClass(currentDueAmount > 0 ? "text-danger fw-bold" : "text-success fw-bold");

                        // Update Total Pay Now dynamically
                        $(".pay-now").on("input", function() {
                            totalPayNow = $(".pay-now")
                                .toArray()
                                .reduce((total, input) => {
                                    const value = parseFloat($(input).val()) || 0;
                                    return total + value;
                                }, 0);
                            $("#totalPayNow").text("₹" + totalPayNow.toFixed(2));
                        });
                    },
                    error: function() {
                        $(".fee-table-container").prev().remove();
                        $(".fee-table-container").before(`
                        <div class="text-center text-danger py-4">
                            Error loading fee details. Please try again.
                        </div>
                    `);
                    }
                });

                const paymentModal = new bootstrap.Modal(document.getElementById("paymentModal"));
                paymentModal.show();
            });

            // Toggle transaction ID field based on payment type
            $("#paymentType").change(function() {
                if ($(this).val() === "online") {
                    $("#transactionIdContainer").show();
                    $("#transactionId").prop("required", true);
                } else {
                    $("#transactionIdContainer").hide();
                    $("#transactionId").prop("required", false);
                }
            }).trigger("change");
        });
    </script>

    <script>
        $(document).ready(function() {
            // ... existing code ...

            // Payment form submission handler
            $('#paymentForm').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                let formData = form.serialize();

                // Get current URL parameters
                const urlParams = new URLSearchParams(window.location.search);
                const currentParams = {
                    status: urlParams.get('status') || '',
                    month: urlParams.get('month') || '<?= $month ?>',
                    year: urlParams.get('year') || '<?= $year ?>',
                    class: urlParams.get('class') || ''
                };

                // Add parameters to form data
                formData += `&status=${encodeURIComponent(currentParams.status)}`;
                formData += `&month=${encodeURIComponent(currentParams.month)}`;
                formData += `&year=${encodeURIComponent(currentParams.year)}`;
                formData += `&class=${encodeURIComponent(currentParams.class)}`;

                const submitBtn = form.find('button[type="submit"]');
                const originalText = submitBtn.html();

                // Show loading state
                submitBtn.prop('disabled', true).html(`
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            Processing...
        `);

                // AJAX request
                $.ajax({
                    url: 'process_payment.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Redirect with all parameters
                            window.location.href = response.redirect;
                        } else {
                            alert(response.message);
                            submitBtn.prop('disabled', false).html(originalText);
                        }
                    },
                    error: function(xhr) {
                        alert('Request failed: ' + (xhr.responseJSON?.message || xhr.statusText || 'Unknown error'));
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });

            // ... rest of your existing JavaScript ...
        });
    </script>
    <script>
        // Payment History Script
        $(document).ready(function() {
            // View history button handler
            $(".view-history").click(function() {
                // Get current month/year from URL
                const urlParams = new URLSearchParams(window.location.search);
                const month = urlParams.get('month') || '';
                const year = urlParams.get('year') || '';

                const studentId = $(this).data("student-id");
                const studentName = $(this).data("student-name");

                // Set student info in modal
                $("#historyStudentName").text(studentName);

                // Show loading state
                $("#historyContent").html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>');

                const historyModal = new bootstrap.Modal(document.getElementById("historyModal"));
                historyModal.show();

                $.ajax({
                    url: "get_payment_history.php",
                    method: "GET",
                    data: {
                        student_id: studentId,
                        month: month,
                        year: year
                    },
                    success: function(data) {
                        $("#historyContent").html(data);
                    },
                    error: function(xhr, status, error) {
                        $("#historyContent").html('<div class="alert alert-danger">Error loading payment history: ' + error + '</div>');
                    }
                });
            });
        });
    </script>

    <script>
        // Export button handler
        // Update the export button handler in fee_collection.php
        $("#exportReport").click(function(e) {
            e.preventDefault();

            // Get all current filter values
            const status = $("select[name='status']").val();
            const month = $("select[name='month']").val();
            const year = $("select[name='year']").val();
            const classFilter = $("#classSelect").val() || [];
            const studentId = $("input[name='student_id']").val();

            // Build export URL with all current filters
            let exportUrl = `export_monthly_fees.php?status=${encodeURIComponent(status)}&month=${encodeURIComponent(month)}&year=${encodeURIComponent(year)}`;

            // Add class filters if any are selected
            if (classFilter.length > 0) {
                classFilter.forEach(c => {
                    exportUrl += `&class[]=${encodeURIComponent(c)}`;
                });
            }

            // Add student ID if provided
            if (studentId) {
                exportUrl += `&student_id=${encodeURIComponent(studentId)}`;
            }

            // Open in new tab to trigger download
            window.open(exportUrl, '_blank');
        });
    </script>
    <!-- Initialize the multi-select plugin -->
    <script>
        $(document).ready(function() {
            $('#classSelect').select2({
                placeholder: "Select class(es)",
                allowClear: true
            });

            // Prevent form submission if no filters are selected
            $('form').on('submit', function(e) {
                const classSelected = $('#classSelect').val() && $('#classSelect').val().length > 0;
                const studentIdEntered = $('input[name="search_term"]').val().trim() !== '';

                if (!classSelected && !studentIdEntered) {
                    e.preventDefault();
                    alert('Please select at least one class or enter a student ID to view data.');
                    return false;
                }
                return true;
            });
        });
    </script>
</body>

</html>