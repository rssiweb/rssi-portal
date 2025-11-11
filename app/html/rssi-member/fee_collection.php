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

// Get filter parameters
$status = $_GET['status'] ?? 'Active';
$monthYear = $_GET['month_year'] ?? date('Y-m'); // e.g., 2025-02
$category = $_GET['category'] ?? [];
$class = $_GET['class'] ?? [];
$studentIds = $_GET['student_ids'] ?? [];

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

// Handle student IDs parameter
if (!is_array($studentIds)) {
    $studentIds = !empty($studentIds) ? [$studentIds] : [];
}

list($year, $monthNumber) = explode('-', $monthYear);
$month = date('F', strtotime("$year-$monthNumber-01"));

$firstDayOfMonth = "$year-$monthNumber-01";
$lastDayOfMonth = date('Y-m-t', strtotime($firstDayOfMonth));

// Check if filters are applied
$hasFilters = !empty($class) || !empty($studentIds) || !empty($category);

if ($hasFilters) {
    // Get student data
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
    if (!empty($studentIds)) {
        $escapedIds = array_map(function ($id) use ($con) {
            return pg_escape_string($con, $id);
        }, $studentIds);
        $idList = implode("','", $escapedIds);
        $query .= " AND s.student_id IN ('$idList')";
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
     AND category_type='structured'
     ORDER BY id"
)) ?? [];


// Process each student to calculate fees
$processedStudents = [];
if ($hasFilters) {
    foreach ($students as $student) {
        $studentId = $student['student_id'];

        // Get student type for the current month being processed
        // Get student info for current month
        $currentInfo = getStudentInfoForDate($con, $studentId, $firstDayOfMonth);
        $studentType = $currentInfo['category_type'];
        $currentClass = $currentInfo['class'] ?? $student['class']; // Fallback to original class if null
        // echo "Student ID: $studentId, Student Type: $studentType, Class: $currentClass<br>";

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

                // Get student type for this historical month
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

                // Rest of your existing code...
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
            <h1>Fee Collection</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Fee Portal</a></li>
                    <li class="breadcrumb-item active">Fee Collection</li>
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
                                            <div class="col-md-1">
                                                <select name="status" class="form-select">
                                                    <option value="Active" <?= $status == 'Active' ? 'selected' : '' ?>>Active</option>
                                                    <option value="Inactive" <?= $status == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="month"
                                                    name="month_year"
                                                    class="form-control"
                                                    value="<?= date('Y-m', strtotime("$month 1, $year")) ?>"
                                                    min="<?= date('Y-m', strtotime('-1 year')) ?>"
                                                    max="<?= date('Y-m', strtotime('+1 year')) ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <select name="category[]" class="form-select select2-categories" multiple="multiple" id="categorySelect">
                                                    <?php
                                                    // Pre-select any existing categories
                                                    if (!empty($category)) {
                                                        foreach ($category as $selectedCategory) {
                                                            echo '<option value="' . htmlspecialchars($selectedCategory) . '" selected>' . htmlspecialchars($selectedCategory) . '</option>';
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <select name="class[]" class="form-select select2-classes" multiple="multiple" id="classSelect">
                                                    <?php
                                                    // Pre-select any existing classes
                                                    if (!empty($class)) {
                                                        foreach ($class as $selectedClass) {
                                                            $classInfo = pg_fetch_assoc(pg_query_params(
                                                                $con,
                                                                "SELECT class_name, value FROM school_classes WHERE value = $1",
                                                                array($selectedClass)
                                                            ));
                                                            if ($classInfo) {
                                                                echo '<option value="' . $classInfo['value'] . '" selected>' . $classInfo['class_name'] . ' (' . $classInfo['value'] . ')</option>';
                                                            }
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <select name="student_ids[]" id="student-select" class="form-control select2" multiple="multiple">
                                                    <?php
                                                    // Always output selected options, not just when $search_term exists
                                                    $studentIds = is_array($_GET['student_ids'] ?? []) ? $_GET['student_ids'] : [];
                                                    foreach ($studentIds as $id) {
                                                        $student = pg_fetch_assoc(pg_query_params(
                                                            $con,
                                                            "SELECT student_id, studentname FROM rssimyprofile_student WHERE student_id = $1",
                                                            array($id)
                                                        ));
                                                        if ($student) {
                                                            echo '<option value="' . $student['student_id'] . '" selected>' .
                                                                htmlspecialchars($student['studentname']) . ' - ' .
                                                                htmlspecialchars($student['student_id']) . '</option>';
                                                        }
                                                    }
                                                    ?>
                                                </select>
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
                                                <i class="fas fa-info-circle"></i> Please select at least one filter to view fee data.
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
                                                            <th>Contact</th>
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
                                                                <td>
                                                                    <a href="tel:<?= $student['contact'] ?>">
                                                                        <?= $student['contact'] ?>
                                                                    </a>
                                                                </td>
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
                                                                    <div class="dropdown">
                                                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                                                            <i class="fas fa-ellipsis-v"></i>
                                                                        </button>
                                                                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                                            <li>
                                                                                <button class="dropdown-item collect-fee"
                                                                                    data-student-id="<?= $student['student_id'] ?>"
                                                                                    data-student-name="<?= htmlspecialchars($student['studentname']) ?>"
                                                                                    data-student-class="<?= htmlspecialchars($student['class']) ?>"
                                                                                    data-net-fee="<?= $student['net_fee'] ?>"
                                                                                    data-due-amount="<?= $student['due_amount'] ?>"
                                                                                    <?= $isLocked ? 'disabled title="Fee collection is locked for this month"' : '' ?>>
                                                                                    <i class="fas fa-hand-holding-usd me-1"></i> Collect Fee
                                                                                </button>
                                                                            </li>
                                                                            <li>
                                                                                <button class="dropdown-item view-history"
                                                                                    data-student-id="<?= $student['student_id'] ?>">
                                                                                    <i class="fas fa-history me-1"></i> View History
                                                                                </button>
                                                                            </li>
                                                                            <li>
                                                                                <button class="dropdown-item send-whatsapp"
                                                                                    data-student-name="<?= htmlspecialchars($student['studentname']) ?>"
                                                                                    data-contact="<?= htmlspecialchars($student['contact']) ?>"
                                                                                    data-due-amount="<?= $student['due_amount'] ?>"
                                                                                    <?= ($student['due_amount'] <= 0) ? 'disabled title="No fee due / credit balance"' : '' ?>>
                                                                                    <i class="fab fa-whatsapp me-1"></i> Send Reminder
                                                                                </button>
                                                                            </li>
                                                                        </ul>
                                                                    </div>
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
                <form id="concessionForm" enctype="multipart/form-data">
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
                            <!-- Dropdown + Info Button -->
                            <div class="col-md-12">
                                <label for="concessionCategory" class="form-label">Concession Category</label>
                                <div class="d-flex align-items-center">
                                    <select class="form-select me-2" id="concessionCategory" name="concession_category" required>
                                        <option value="">-- Select Category --</option>
                                        <option value="non_billable">Non-Billable Adjustment</option>
                                        <option value="rounding_off">Rounding Off Adjustment</option>
                                        <option value="financial_hardship">Financial Hardship / Economic Background</option>
                                        <option value="sibling">Sibling Concession</option>
                                        <option value="staff_child">Staff Child Concession</option>
                                        <option value="special_talent">Special Talent / Merit-Based</option>
                                        <option value="early_bird">Promotional / Early Bird Offer</option>
                                        <option value="scholarship">Scholarship-Based</option>
                                        <option value="referral">Referral / Community Support</option>
                                        <option value="special_case">Special Cases / Discretionary</option>
                                    </select>
                                    <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#concessionInfoModal">
                                        <i class="bi bi-info-circle"></i> Category Info
                                    </button>
                                </div>
                            </div>
                            <!-- Add this after the reason textarea -->
                            <div class="col-md-12 mb-3">
                                <label for="supportingDocument" class="form-label">Supporting Document</label>
                                <input type="file" class="form-control" id="supportingDocument" name="supporting_document" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                <div class="form-text">Upload supporting documents (PDF, JPG, PNG, DOC). Max file size: 5MB</div>
                            </div>
                            <div class="col-md-8">
                                <label for="concessionReason" class="form-label">Reason</label>
                                <textarea class="form-control" id="concessionReason" name="reason" rows="3" required></textarea>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="concessionFromMonth" class="form-label">Effective From (Month)</label>
                                <input type="month" class="form-control" id="concessionFromMonth" required>
                                <input type="hidden" name="effective_from" id="effectiveFrom">
                            </div>
                            <div class="col-md-4">
                                <label for="concessionUntilMonth" class="form-label">Effective Until (Month)</label>
                                <input type="month" class="form-control" id="concessionUntilMonth">
                                <input type="hidden" name="effective_until" id="effectiveUntil">
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
                var formData = new FormData(this); // Use FormData for file upload

                // Get all current URL parameters and append to FormData
                var urlParams = new URLSearchParams(window.location.search);
                urlParams.forEach(function(value, key) {
                    if (key.endsWith('[]')) {
                        var values = urlParams.getAll(key);
                        values.forEach(function(val) {
                            formData.append(key, val);
                        });
                    } else {
                        formData.append(key, value);
                    }
                });

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
                    processData: false, // Important for file upload
                    contentType: false, // Important for file upload
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
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
                                            Dues can be collected either as an Admission Fee or a Monthly Fee.
                                            You can split payments between cash and online methods.
                                        </p>
                                    </div>
                                    <div id="feeActions" class="d-flex justify-content-end mb-3"></div>
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Category</th>
                                                <th>Payment Method</th>
                                                <th>Pay Now</th>
                                                <th>Reference No (if online)</th>
                                            </tr>
                                        </thead>
                                        <tbody id="feeBreakdown">
                                            <!-- Populated by JavaScript -->
                                        </tbody>
                                        <tfoot>
                                            <tr class="table-primary">
                                                <th colspan="2">Total Due</th>
                                                <th colspan="2" id="totalDueAmount">₹0.00</th>
                                            </tr>
                                            <tr class="table-primary">
                                                <th colspan="2">Total Pay Now</th>
                                                <th colspan="2" id="totalPayNow">₹0.00</th>
                                            </tr>
                                            <tr class="table-info">
                                                <th colspan="2">Total Cash Payment</th>
                                                <th colspan="2" id="totalCashAmount">₹0.00</th>
                                            </tr>
                                            <tr class="table-info">
                                                <th colspan="2">Total Online Payment</th>
                                                <th colspan="2" id="totalOnlineAmount">₹0.00</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Details -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="paymentDate" class="form-label">Payment Date:</label>
                                <input type="date" class="form-control" id="paymentDate" name="payment_date" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-4">
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
                            <div class="col-md-4">
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

    <!-- Modal -->
    <div class="modal fade" id="concessionInfoModal" tabindex="-1" aria-labelledby="concessionInfoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="concessionInfoModalLabel">Concession Category Explanation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ol>
                        <li><strong>Non-Billable Adjustment:</strong> Amount deducted from the total payable fee due to reasons like system alignment, late admission, or other administrative factors. This is not a concession but a reflection of the actual chargeable period or scope.</li>
                        <li><strong>Rounding Off Adjustment:</strong> Small fee reductions for rounding convenience (e.g., ₹4510 becomes ₹4500).</li>
                        <li><strong>Financial Hardship / Economic Background:</strong> Support for students from economically weaker backgrounds. May require documentation.</li>
                        <li><strong>Sibling Concession:</strong> Discount for families with multiple children enrolled. Typically applies to the 2nd or 3rd child.</li>
                        <li><strong>Staff Child Concession:</strong> Reduced fees for children of school staff or teachers.</li>
                        <li><strong>Special Talent / Merit-Based:</strong> Given for academic or extracurricular excellence (e.g., scoring 90%+ or excelling in sports).</li>
                        <li><strong>Promotional / Early Bird Offer:</strong> Time-limited discount for early enrollments or during specific campaigns.</li>
                        <li><strong>Scholarship-Based:</strong> Based on internal tests or government scholarship eligibility. Requires verification.</li>
                        <li><strong>Referral / Community Support:</strong> Offered when a student joins due to a parent referral or NGO/community initiative.</li>
                        <li><strong>Special Cases / Discretionary:</strong> Unique cases like parent death, medical emergencies, orphan students, etc. Requires approval.</li>
                    </ol>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" data-bs-target="#concessionModal" data-bs-toggle="modal">
                        Back to Concession Form
                    </button>
                </div>
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
                        let totalCash = 0;
                        let totalOnline = 0;

                        // Populate categories
                        allCategories = response.categories;

                        response.categories.forEach((category) => {
                            const row = `
                        <tr class="fee-category-row" data-category-id="${category.id}">
                            <td>${category.name}</td>
                            <td>
                                <select class="form-select payment-method" 
                                       data-category-id="${category.id}" 
                                       name="payment_methods[${category.id}]">
                                    <option value="cash">Cash</option>
                                    <option value="online">Online</option>
                                </select>
                            </td>
                            <td>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" class="form-control pay-now text-end" 
                                           data-category-id="${category.id}" 
                                           name="payment_amounts[${category.id}]"
                                           value="0.00"
                                           min="0"
                                           max="${category.due_amount || ''}"
                                           step="0.01">
                                </div>
                            </td>
                            <td>
                                <input type="text" class="form-control reference-number d-none" 
                                       data-category-id="${category.id}" 
                                       name="reference_numbers[${category.id}]"
                                       placeholder="Enter reference no">
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

                        // Handle payment method changes
                        $(".payment-method").change(function() {
                            const categoryId = $(this).data("category-id");
                            const method = $(this).val();

                            $(`.reference-number[data-category-id="${categoryId}"]`)
                                .toggleClass("d-none", method !== "online")
                                .prop("required", method === "online");
                        });

                        // Update payment totals when amounts change
                        $(".pay-now, .payment-method").on("input change", function() {
                            totalPayNow = 0;
                            totalCash = 0;
                            totalOnline = 0;

                            $(".fee-category-row").each(function() {
                                const categoryId = $(this).data("category-id");
                                const amount = parseFloat($(this).find(".pay-now").val()) || 0;
                                const method = $(this).find(".payment-method").val();

                                totalPayNow += amount;

                                if (method === "cash") {
                                    totalCash += amount;
                                } else {
                                    totalOnline += amount;
                                }
                            });

                            $("#totalPayNow").text("₹" + totalPayNow.toFixed(2));
                            $("#totalCashAmount").text("₹" + totalCash.toFixed(2));
                            $("#totalOnlineAmount").text("₹" + totalOnline.toFixed(2));
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

            // Payment form submission handler
            $('#paymentForm').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                let formData = form.serialize();

                // Validate that at least one payment amount is greater than 0
                let hasPayment = false;
                $(".pay-now").each(function() {
                    if (parseFloat($(this).val()) > 0) {
                        hasPayment = true;
                        return false; // break the loop
                    }
                });

                if (!hasPayment) {
                    alert("Please enter at least one payment amount greater than 0");
                    return;
                }

                // Validate online payments have reference numbers
                let valid = true;
                $(".payment-method").each(function() {
                    const method = $(this).val();
                    const amount = parseFloat($(this).closest('tr').find('.pay-now').val()) || 0;

                    if (method === "online" && amount > 0) {
                        const refNo = $(this).closest('tr').find('.reference-number').val();
                        if (!refNo || refNo.trim() === "") {
                            valid = false;
                            return false; // break the loop
                        }
                    }
                });

                if (!valid) {
                    alert("Please enter reference numbers for all online payments");
                    return;
                }

                // Get current URL parameters
                const urlParams = new URLSearchParams(window.location.search);
                // Add all parameters to form data
                urlParams.forEach((value, key) => {
                    if (key.endsWith('[]')) {
                        const values = urlParams.getAll(key);
                        values.forEach(val => {
                            formData += `&${key}=${encodeURIComponent(val)}`;
                        });
                    } else {
                        formData += `&${key}=${encodeURIComponent(value)}`;
                    }
                });

                // Ensure these default values if not present
                if (!urlParams.has('status')) {
                    formData += '&status=Active';
                }
                if (!urlParams.has('month')) {
                    formData += `&month=${encodeURIComponent('<?= $month ?>')}`;
                }
                if (!urlParams.has('year')) {
                    formData += `&year=${encodeURIComponent('<?= $year ?>')}`;
                }

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
        });
    </script>
    <script>
        // Payment History Script
        $(document).ready(function() {
            // View history button handler
            $(".view-history").click(function() {
                // Get current month/year from URL
                const urlParams = new URLSearchParams(window.location.search);
                const monthYear = urlParams.get('month_year') || '';

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
                        month_year: monthYear
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
        $("#exportReport").click(function(e) {
            e.preventDefault();

            // Get all current filter values from the form
            const status = $("select[name='status']").val();
            const monthYear = $("input[name='month_year']").val(); // Changed to match your form
            const category = $("#categorySelect").val() || [];
            const classFilter = $("#classSelect").val() || [];
            const studentIds = $("#student-select").val() || [];

            // Build export URL with all current filters
            let exportUrl = `export_monthly_fees.php?status=${encodeURIComponent(status)}&month_year=${encodeURIComponent(monthYear)}`;

            // Add category filters if any are selected
            if (category.length > 0) {
                category.forEach(c => {
                    exportUrl += `&category[]=${encodeURIComponent(c)}`;
                });
            }

            // Add class filters if any are selected
            if (classFilter.length > 0) {
                classFilter.forEach(c => {
                    exportUrl += `&class[]=${encodeURIComponent(c)}`;
                });
            }

            // Add student IDs if provided
            if (studentIds.length > 0) {
                studentIds.forEach(id => {
                    exportUrl += `&student_ids[]=${encodeURIComponent(id)}`;
                });
            }

            // Open in new tab to trigger download
            window.open(exportUrl, '_blank');
        });
    </script>
    <!-- Initialize the multi-select plugin -->
    <script>
        $(document).ready(function() {
            // Initialize class select2 with AJAX
            $('#classSelect').select2({
                ajax: {
                    url: 'fetch_class.php',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.results
                        };
                    },
                    cache: true
                },
                placeholder: "Search and select class(es)",
                // allowClear: true,
                minimumInputLength: 0,
                width: '100%'
            });
            // Initialize category select2 with AJAX
            $('#categorySelect').select2({
                ajax: {
                    url: 'fetch_category.php',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.results
                        };
                    },
                    cache: true
                },
                placeholder: "Search and select category(ies)",
                minimumInputLength: 0,
                width: '100%'
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
    <script>
        function setEffectiveDates() {
            const fromInput = document.getElementById("concessionFromMonth");
            const untilInput = document.getElementById("concessionUntilMonth");
            const effectiveFrom = document.getElementById("effectiveFrom");
            const effectiveUntil = document.getElementById("effectiveUntil");

            // Set 'From' to 1st of month
            fromInput.addEventListener("change", function() {
                const [year, month] = this.value.split("-");
                effectiveFrom.value = `${year}-${month}-01`;
            });

            // Set 'Until' to last day of month
            untilInput.addEventListener("change", function() {
                const [year, month] = this.value.split("-");
                const lastDay = new Date(year, month, 0).getDate(); // 0 gets last day of previous month
                effectiveUntil.value = `${year}-${month}-${lastDay}`;
            });
        }

        // Call the function on page load
        document.addEventListener("DOMContentLoaded", setEffectiveDates);
    </script>
    <script>
        $(document).ready(function() {
            $('#student-select').select2({
                ajax: {
                    url: 'fetch_students.php',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term, // search term
                            isActive: true // or false depending on your needs
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.results
                        };
                    },
                    cache: true
                },
                minimumInputLength: 2,
                placeholder: 'Search by Student ID or Name',
                // allowClear: true,
                width: '100%'
            });
        });
    </script>
    <script>
        $(document).on('click', '.send-whatsapp', function() {
            const studentName = $(this).data('student-name');
            const contact = $(this).data('contact');
            const dueAmount = parseFloat($(this).data('due-amount'));

            // Only proceed if amount > 0
            if (dueAmount <= 0) {
                return;
            }

            const monthName = '<?= $month ?? "वर्तमान" ?>';
            const yearName = '<?= $year  ?? "वर्तमान" ?>';

            const now = new Date();
            const day = now.getDate();
            const mIdx = now.getMonth();
            const yyyy = now.getFullYear();
            const hMonths = ['जनवरी', 'फरवरी', 'मार्च', 'अप्रैल', 'मई', 'जून', 'जुलाई', 'अगस्त', 'सितंबर', 'अक्टूबर', 'नवंबर', 'दिसंबर'];
            const hindiDate = `${day} ${hMonths[mIdx]}, ${yyyy}`;

            const formattedAmount = Math.abs(dueAmount).toFixed(2);

            let msg = `प्रिय ${studentName} के अभिभावक,\n\n`;
            msg += `आज (${hindiDate}) तक ${monthName}-${yearName} माह के लिए ₹${formattedAmount} शुल्क देय है।\nकृपया यथाशीघ्र शुल्क जमा करने का कष्ट करें।\n\nधन्यवाद,\nविद्यालय प्रबंधन`;

            const url = `https://wa.me/+91${contact}?text=${encodeURIComponent(msg)}`;
            window.open(url, '_blank');
        });
    </script>
</body>

</html>