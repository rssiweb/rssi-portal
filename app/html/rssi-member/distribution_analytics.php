<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

// Function to get academic year
function getAcademicYear($date)
{
    $year = date('Y', strtotime($date));
    $month = date('m', strtotime($date));
    if ($month >= 4) {
        return $year . '-' . ($year + 1);
    } else {
        return ($year - 1) . '-' . $year;
    }
}

// Process filters
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'date_range';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$academic_year = isset($_GET['academic_year']) ? $_GET['academic_year'] : getAcademicYear(date('Y-m-d'));
$student_ids = isset($_GET['student_ids']) ? $_GET['student_ids'] : [];

// If student_ids is a string (from GET parameter), convert to array
if (is_string($student_ids)) {
    $student_ids = explode(',', $student_ids);
}

// Set date range based on academic year if selected
if ($filter_type == 'academic_year' && $academic_year) {
    $years = explode('-', $academic_year);
    $start_date = $years[0] . '-04-01';
    $end_date = $years[1] . '-03-31';
}

// Build WHERE conditions for student IDs
$student_where_condition = "";
if (!empty($student_ids)) {
    $student_ids_escaped = array_map(function ($id) use ($con) {
        return pg_escape_string($con, $id);
    }, $student_ids);

    $student_ids_list = "'" . implode("','", $student_ids_escaped) . "'";
    $student_where_condition = "AND (so.distributed_to IN ($student_ids_list))";
}

// Get dashboard statistics (updated with total spend calculation)
$stats_query = "
    SELECT 
        COUNT(DISTINCT so.distributed_to) AS total_beneficiaries,
        SUM(so.quantity_distributed) AS total_quantity,
        COUNT(DISTINCT so.date) AS distribution_days,
        SUM(
            COALESCE(
                (so.quantity_distributed * 
                 (SELECT original_price 
                  FROM stock_item_price sip 
                  WHERE sip.item_id = so.item_distributed 
                    AND sip.unit_id = so.unit 
                    AND sip.effective_start_date <= so.date 
                    AND (sip.effective_end_date IS NULL OR sip.effective_end_date >= so.date)
                  ORDER BY sip.effective_start_date DESC 
                  LIMIT 1)
                ), 0
            )
        ) AS total_original_amount,
        SUM(
            COALESCE(
                (so.quantity_distributed * 
                 (SELECT original_price * (1 - COALESCE(discount_percentage, 0)/100)
                  FROM stock_item_price sip 
                  WHERE sip.item_id = so.item_distributed 
                    AND sip.unit_id = so.unit 
                    AND sip.effective_start_date <= so.date 
                    AND (sip.effective_end_date IS NULL OR sip.effective_end_date >= so.date)
                  ORDER BY sip.effective_start_date DESC 
                  LIMIT 1)
                ), 0
            )
        ) AS total_discounted_amount
    FROM stock_out so
    JOIN stock_item si ON so.item_distributed = si.item_id
    WHERE so.date BETWEEN '$start_date' AND '$end_date'
      AND so.distributed_to IS NOT NULL
      AND si.is_ration = true
      $student_where_condition
";

$stats_result = pg_query($con, $stats_query);
$stats = pg_fetch_assoc($stats_result);

// Detailed breakdown query to see the calculation step by step
// $breakdown_query = "
//     SELECT 
//         so.date,
//         si.item_name,
//         u.unit_name,
//         so.quantity_distributed,
//         sip.original_price,
//         sip.discount_percentage,
//         (sip.original_price * so.quantity_distributed) AS original_amount,
//         (sip.original_price * (1 - COALESCE(sip.discount_percentage, 0)/100) * so.quantity_distributed) AS discounted_amount
//     FROM stock_out so
//     JOIN stock_item si ON so.item_distributed = si.item_id
//     JOIN stock_item_unit u ON so.unit = u.unit_id
//     LEFT JOIN stock_item_price sip ON (
//         sip.item_id = so.item_distributed 
//         AND sip.unit_id = so.unit 
//         AND sip.effective_start_date <= so.date 
//         AND (sip.effective_end_date IS NULL OR sip.effective_end_date >= so.date)
//     )
//     WHERE so.date BETWEEN '$start_date' AND '$end_date'
//       AND so.distributed_to IS NOT NULL
//       AND si.is_ration = true
//     ORDER BY so.date, si.item_name
// ";

// $breakdown_result = pg_query($con, $breakdown_query);

// echo "<h3>Price Calculation Breakdown:</h3>";
// echo "<table border='1' cellpadding='5' cellspacing='0'>";
// echo "<tr>
//         <th>Date</th>
//         <th>Item</th>
//         <th>Unit</th>
//         <th>Quantity</th>
//         <th>Original Price</th>
//         <th>Discount %</th>
//         <th>Original Amount</th>
//         <th>Discounted Amount</th>
//       </tr>";

// $total_original = 0;
// $total_discounted = 0;

// while ($row = pg_fetch_assoc($breakdown_result)) {
//     echo "<tr>";
//     echo "<td>" . $row['date'] . "</td>";
//     echo "<td>" . $row['item_name'] . "</td>";
//     echo "<td>" . $row['unit_name'] . "</td>";
//     echo "<td>" . $row['quantity_distributed'] . "</td>";
//     echo "<td>" . ($row['original_price'] ?? 'NULL') . "</td>";
//     echo "<td>" . ($row['discount_percentage'] ?? '0') . "</td>";
//     echo "<td>" . ($row['original_amount'] ?? '0') . "</td>";
//     echo "<td>" . ($row['discounted_amount'] ?? '0') . "</td>";
//     echo "</tr>";

//     $total_original += floatval($row['original_amount'] ?? 0);
//     $total_discounted += floatval($row['discounted_amount'] ?? 0);
// }

// echo "<tr style='font-weight: bold;'>";
// echo "<td colspan='6' align='right'>TOTALS:</td>";
// echo "<td>" . number_format($total_original, 2) . "</td>";
// echo "<td>" . number_format($total_discounted, 2) . "</td>";
// echo "</tr>";

// echo "</table>";

// // Also show a summary of missing price records
// $missing_prices_query = "
//     SELECT 
//         so.date,
//         si.item_name,
//         u.unit_name,
//         so.quantity_distributed,
//         COUNT(sip.price_id) as price_records_found
//     FROM stock_out so
//     JOIN stock_item si ON so.item_distributed = si.item_id
//     JOIN stock_item_unit u ON so.unit = u.unit_id
//     LEFT JOIN stock_item_price sip ON (
//         sip.item_id = so.item_distributed 
//         AND sip.unit_id = so.unit 
//         AND sip.effective_start_date <= so.date 
//         AND (sip.effective_end_date IS NULL OR sip.effective_end_date >= so.date)
//     )
//     WHERE so.date BETWEEN '$start_date' AND '$end_date'
//       AND so.distributed_to IS NOT NULL
//       AND si.is_ration = true
//     GROUP BY so.date, si.item_name, u.unit_name, so.quantity_distributed
//     HAVING COUNT(sip.price_id) = 0
// ";

// $missing_prices_result = pg_query($con, $missing_prices_query);

// if (pg_num_rows($missing_prices_result) > 0) {
//     echo "<h3 style='color: red;'>Items with Missing Price Records:</h3>";
//     echo "<table border='1' cellpadding='5' cellspacing='0'>";
//     echo "<tr>
//             <th>Date</th>
//             <th>Item</th>
//             <th>Unit</th>
//             <th>Quantity</th>
//           </tr>";

//     while ($row = pg_fetch_assoc($missing_prices_result)) {
//         echo "<tr>";
//         echo "<td>" . $row['date'] . "</td>";
//         echo "<td>" . $row['item_name'] . "</td>";
//         echo "<td>" . $row['unit_name'] . "</td>";
//         echo "<td>" . $row['quantity_distributed'] . "</td>";
//         echo "</tr>";
//     }

//     echo "</table>";
// }

// Get distribution dates with beneficiary count
$dates_query = "
    SELECT 
        so.date,
        COUNT(DISTINCT so.distributed_to) as beneficiaries_count,
        SUM(so.quantity_distributed) as total_quantity,
        STRING_AGG(DISTINCT si.item_name || ' (' || so.quantity_distributed || ' ' || u.unit_name || ')', ', ') as items
    FROM stock_out so
    JOIN stock_item si ON so.item_distributed = si.item_id
    JOIN stock_item_unit u ON so.unit = u.unit_id
    WHERE so.date BETWEEN '$start_date' AND '$end_date'
      AND si.is_ration = true
      $student_where_condition
    GROUP BY so.date
    ORDER BY so.date DESC
";

$dates_result = pg_query($con, $dates_query);

// Get beneficiaries for each date
$beneficiaries_by_date = [];
if (pg_num_rows($dates_result) > 0) {
    pg_result_seek($dates_result, 0);
    while ($date_row = pg_fetch_assoc($dates_result)) {
        $date = $date_row['date'];
        $beneficiaries_query = "
            SELECT 
                so.distributed_to,
                (SELECT fullname FROM rssimyaccount_members WHERE associatenumber = so.distributed_by) AS distributed_by_name,
                COALESCE(a.fullname, s.studentname) AS fullname,
                COALESCE(a.associatenumber, s.student_id) AS associatenumber,
                STRING_AGG(si.item_name || ' (' || so.quantity_distributed || ' ' || u.unit_name || ')', ', ') AS items_received
            FROM stock_out so
            LEFT JOIN rssimyaccount_members a ON so.distributed_to = a.associatenumber
            LEFT JOIN rssimyprofile_student s ON so.distributed_to = s.student_id
            LEFT JOIN stock_item si ON so.item_distributed = si.item_id AND si.is_ration = true
            LEFT JOIN stock_item_unit u ON so.unit = u.unit_id
            WHERE so.date = '$date'
            AND si.is_ration = true
            $student_where_condition
            GROUP BY so.distributed_to, COALESCE(a.fullname, s.studentname), COALESCE(a.associatenumber, s.student_id), so.distributed_by
            ORDER BY COALESCE(a.fullname, s.studentname);
        ";
        $beneficiaries_result = pg_query($con, $beneficiaries_query);
        $beneficiaries_by_date[$date] = pg_fetch_all($beneficiaries_result);
    }
}

// Get item-wise summary
$items_query = "
    SELECT 
        si.item_name,
        u.unit_name,
        SUM(so.quantity_distributed) as total_quantity,
        COUNT(DISTINCT so.distributed_to) as beneficiaries_count,
        COUNT(DISTINCT so.date) as distribution_days
    FROM stock_out so
    JOIN stock_item si ON so.item_distributed = si.item_id
    JOIN stock_item_unit u ON so.unit = u.unit_id
    WHERE so.date BETWEEN '$start_date' AND '$end_date'
      AND si.is_ration = true
      $student_where_condition
    GROUP BY si.item_name, u.unit_name
    ORDER BY total_quantity DESC
";

$items_result = pg_query($con, $items_query);

// Monthly distribution for charts
$monthly_query = "
    SELECT 
        TO_CHAR(so.date, 'YYYY-MM') as month,
        SUM(so.quantity_distributed) as total_quantity
    FROM stock_out so
    JOIN stock_item si ON so.item_distributed = si.item_id
    WHERE so.date BETWEEN '$start_date' AND '$end_date'
      AND si.is_ration = true
      $student_where_condition
    GROUP BY TO_CHAR(so.date, 'YYYY-MM')
    ORDER BY month
";

$monthly_result = pg_query($con, $monthly_query);

$monthly_labels = [];
$monthly_data = [];
while ($monthly_row = pg_fetch_assoc($monthly_result)) {
    $monthly_labels[] = date('F Y', strtotime($monthly_row['month'] . '-01'));
    $monthly_data[] = $monthly_row['total_quantity'];
}

// Item-wise chart
$item_chart_query = "
    SELECT 
        si.item_name,
        SUM(so.quantity_distributed) as total_quantity
    FROM stock_out so
    JOIN stock_item si ON so.item_distributed = si.item_id
    WHERE so.date BETWEEN '$start_date' AND '$end_date'
      AND si.is_ration = true
      $student_where_condition
    GROUP BY si.item_name
    ORDER BY total_quantity DESC
";

$item_chart_result = pg_query($con, $item_chart_query);
$item_labels = [];
$item_data = [];
while ($item_row = pg_fetch_assoc($item_chart_result)) {
    $item_labels[] = $item_row['item_name'];
    $item_data[] = $item_row['total_quantity'];
}

// Get all student IDs for the select2 dropdown
// $all_students_query = "
//     SELECT DISTINCT so.distributed_to, 
//            COALESCE(a.fullname, s.studentname) AS name
//     FROM stock_out so
//     LEFT JOIN rssimyaccount_members a ON so.distributed_to = a.associatenumber
//     LEFT JOIN rssimyprofile_student s ON so.distributed_to = s.student_id
//     JOIN stock_item si ON so.item_distributed = si.item_id
//     WHERE si.is_ration = true
//     ORDER BY name
// ";

// $all_students_result = pg_query($con, $all_students_query);
// $all_students = pg_fetch_all($all_students_result);

// Academic year options
$current_year = date('Y');
$academic_years = [];
for ($i = -5; $i <= 1; $i++) {
    $start_year = $current_year + $i;
    $end_year = $start_year + 1;
    $academic_years[] = $start_year . '-' . $end_year;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ration Distribution Portal</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #eef0ff;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --info: #4895ef;
            --warning: #ff9e00;
            --danger: #f72585;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --body-bg: #f5f7fb;
            --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        .card {
            border-radius: 12px;
            border: none;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            margin-bottom: 1.5rem;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            background: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 600;
            padding: 1rem 1.5rem;
            border-radius: 12px 12px 0 0 !important;
        }

        .stat-card {
            text-align: center;
            padding: 1.5rem;
            height: 100%;
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--primary);
            background: var(--primary-light);
            width: 70px;
            height: 70px;
            line-height: 70px;
            border-radius: 50%;
            margin: 0 auto 1rem;
        }

        .stat-number {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--primary);
        }

        .stat-title {
            color: var(--gray);
            font-weight: 500;
        }

        .distribution-date {
            cursor: pointer;
            transition: var(--transition);
            border-left: 4px solid transparent;
            padding: 1rem 1.5rem;
            margin-bottom: 0.5rem;
            border-radius: 8px;
            background: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .distribution-date:hover {
            background-color: var(--primary-light);
            border-left-color: var(--primary);
        }

        .distribution-details {
            background-color: var(--primary-light);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .beneficiary-item {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            height: 300px;
        }

        .filter-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
        }

        .btn-primary {
            background: var(--primary);
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
        }

        .btn-primary:hover {
            background: var(--secondary);
            transform: translateY(-2px);
        }

        .btn-sm {
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
        }

        .filter-choice {
            cursor: pointer;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            margin-right: 0.5rem;
            background: #e9ecef;
            display: inline-block;
            font-weight: 500;
            transition: var(--transition);
            border: none;
        }

        .filter-choice.active {
            background: var(--primary);
            color: white;
        }

        .filter-choice:hover:not(.active) {
            background: #dde1e6;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid #e1e5eb;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.15);
        }

        .table th {
            border-top: none;
            font-weight: 600;
            color: #495057;
            background-color: var(--primary-light);
        }

        .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            background: var(--primary-light);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 200px;
        }

        .spinner {
            width: 3rem;
            height: 3rem;
        }

        .badge-count {
            background: var(--primary);
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            margin-left: 0.5rem;
        }

        /* Select2 customization */
        .select2-container--default .select2-selection--multiple {
            /* border: 1px solid #e1e5eb; */
            border-radius: 8px;
            padding: 0.5rem;
            min-height: 46px;
        }

        @media (max-width: 768px) {
            .stat-number {
                font-size: 1.8rem;
            }

            .filter-choice {
                padding: 0.5rem 1rem;
                margin-bottom: 0.5rem;
            }

            .distribution-date {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        .stat-number .text-decoration-line-through {
            font-size: 0.8em;
        }

        .stat-number .fw-bold {
            font-size: 1.2em;
            color: var(--success);
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Distribution Analytics</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Work</a></li>
                    <li class="breadcrumb-item active">Distribution Analytics</li>
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
                            <div class="container mb-5">
                                <div class="filter-section mb-4">
                                    <h4 class="mb-3"><i class="fas fa-filter me-2"></i>Filter Data</h4>
                                    <form method="GET">
                                        <div class="mb-3">
                                            <button type="button" class="filter-choice <?= $filter_type == 'date_range' ? 'active' : '' ?>" onclick="setFilterType('date_range')">
                                                <i class="fas fa-calendar-alt me-2"></i>Date Range
                                            </button>
                                            <button type="button" class="filter-choice <?= $filter_type == 'academic_year' ? 'active' : '' ?>" onclick="setFilterType('academic_year')">
                                                <i class="fas fa-graduation-cap me-2"></i>Academic Year
                                            </button>
                                            <input type="hidden" name="filter_type" id="filter_type" value="<?= $filter_type ?>">
                                        </div>

                                        <!-- Student ID Filter -->
                                        <div class="row mb-3">
                                            <div class="col-12">
                                                <label class="form-label">Filter by Student ID</label>
                                                <select class="form-control select2-multiple" id="student_ids" name="student_ids[]" multiple="multiple">
                                                    <?php foreach ($student_ids as $stu): ?>
                                                        <?php if ($stu != 'all'): ?>
                                                            <option value="<?= htmlspecialchars($stu) ?>" selected><?= htmlspecialchars($stu) ?></option>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="form-text">Leave empty to show all students</div>
                                            </div>
                                        </div>

                                        <div id="date-range-filter" style="<?= $filter_type == 'date_range' ? 'display:block' : 'display:none' ?>">
                                            <div class="row g-3 align-items-end">
                                                <div class="col-md-5">
                                                    <label class="form-label">Start Date</label>
                                                    <input type="date" class="form-control" name="start_date" value="<?= $start_date ?>">
                                                </div>
                                                <div class="col-md-5">
                                                    <label class="form-label">End Date</label>
                                                    <input type="date" class="form-control" name="end_date" value="<?= $end_date ?>">
                                                </div>
                                                <div class="col-md-2">
                                                    <button class="btn btn-primary w-100"><i class="fas fa-check me-1"></i> Apply</button>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="academic-year-filter" style="<?= $filter_type == 'academic_year' ? 'display:block' : 'display:none' ?>">
                                            <div class="row g-3 align-items-end">
                                                <div class="col-md-10">
                                                    <label class="form-label">Select Academic Year</label>
                                                    <select class="form-select" name="academic_year">
                                                        <?php foreach ($academic_years as $ay): ?>
                                                            <option value="<?= $ay ?>" <?= $ay == $academic_year ? 'selected' : '' ?>><?= $ay ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <button class="btn btn-primary w-100"><i class="fas fa-check me-1"></i> Apply</button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <!-- Dashboard stats -->
                                <div class="row mb-4">
                                    <div class="col-md-3">
                                        <div class="card stat-card">
                                            <div class="stat-icon">
                                                <i class="fas fa-users"></i>
                                            </div>
                                            <div class="stat-number"><?= $stats['total_beneficiaries'] ?></div>
                                            <div class="stat-title">Total Beneficiaries</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card stat-card">
                                            <div class="stat-icon">
                                                <i class="fas fa-weight-hanging"></i>
                                            </div>
                                            <div class="stat-number"><?= $stats['total_quantity'] ?></div>
                                            <div class="stat-title">Total Quantity Distributed</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card stat-card">
                                            <div class="stat-icon">
                                                <i class="fas fa-calendar-day"></i>
                                            </div>
                                            <div class="stat-number"><?= $stats['distribution_days'] ?></div>
                                            <div class="stat-title">Distribution Days</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card stat-card">
                                            <div class="stat-icon">
                                                <i class="fas fa-indian-rupee-sign"></i>
                                            </div>
                                            <div class="stat-number">
                                                <div class="d-flex justify-content-between">
                                                    <span class="text-decoration-line-through text-muted small">
                                                        ₹<?= number_format($stats['total_original_amount'], 2) ?>
                                                    </span>
                                                    <span class="fw-bold">
                                                        ₹<?= number_format($stats['total_discounted_amount'], 2) ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="stat-title">Total Spend (INR)</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Distribution Dates -->
                                <div class="card mb-4">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0"><i class="fas fa-list-alt me-2"></i>Distribution History</h5>
                                        <span class="badge bg-primary"><?= pg_num_rows($dates_result) ?> entries</span>
                                    </div>
                                    <div class="card-body">
                                        <?php if (pg_num_rows($dates_result) > 0): ?>
                                            <?php
                                            pg_result_seek($dates_result, 0);
                                            $index = 0;
                                            while ($date_row = pg_fetch_assoc($dates_result)):
                                                $index++;
                                                $date = $date_row['date'];
                                                $beneficiaries = $beneficiaries_by_date[$date] ?? [];
                                            ?>
                                                <div class="distribution-date" data-bs-toggle="collapse" data-bs-target="#distribution<?= $index ?>">
                                                    <div>
                                                        <strong><?= date('d M, Y', strtotime($date)) ?></strong>
                                                        <div class="text-muted small"><?= $date_row['beneficiaries_count'] ?> beneficiaries, <?= $date_row['total_quantity'] ?> units distributed</div>
                                                    </div>
                                                    <div class="text-end">
                                                        <span class="text-muted small">Click to view details</span>
                                                        <i class="fas fa-chevron-down ms-2"></i>
                                                    </div>
                                                </div>
                                                <div class="collapse mb-3" id="distribution<?= $index ?>">
                                                    <div class="distribution-details">
                                                        <h6 class="mb-3">Beneficiaries on <?= date('d M, Y', strtotime($date)) ?>:</h6>

                                                        <?php if (!empty($beneficiaries)): ?>
                                                            <?php foreach ($beneficiaries as $beneficiary): ?>
                                                                <div class="beneficiary-item">
                                                                    <div class="d-flex justify-content-between align-items-center">
                                                                        <div>
                                                                            <h6 class="mb-1"><?= $beneficiary['fullname'] ?> (<?= $beneficiary['associatenumber'] ?>)</h6>
                                                                            <p class="text-muted small mb-0">Recorded by: <?= $beneficiary['distributed_by_name'] ?></p>
                                                                        </div>
                                                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#itemsModal"
                                                                            data-beneficiary="<?= $beneficiary['fullname'] ?>"
                                                                            data-association-id="<?= $beneficiary['associatenumber'] ?>"
                                                                            data-date="<?= date('d M, Y', strtotime($date)) ?>"
                                                                            data-items="<?= htmlspecialchars($beneficiary['items_received']) ?>">
                                                                            <i class="fas fa-box-open me-1"></i> View Items
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <div class="text-center py-3">
                                                                <p class="text-muted mb-0">No beneficiary details available for this date.</p>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <div class="text-center py-4">
                                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">No distribution records found for the selected period.</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Charts -->
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="card">
                                            <div class="card-header">
                                                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Monthly Distribution</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="chart-container">
                                                    <canvas id="monthlyChart"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="card">
                                            <div class="card-header">
                                                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Item-wise Distribution</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="chart-container">
                                                    <canvas id="itemChart"></canvas>
                                                </div>
                                            </div>
                                        </div>
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
    <!-- Items Modal -->
    <div class="modal fade" id="itemsModal" tabindex="-1" aria-labelledby="itemsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="itemsModalLabel">Items Received</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6 id="beneficiaryName" class="mb-3"></h6>
                    <p class="text-muted" id="distributionDate"></p>

                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Item Name</th>
                                    <th>Quantity</th>
                                    <th>Unit</th>
                                </tr>
                            </thead>
                            <tbody id="itemsTableBody">
                                <!-- Items will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select2-multiple').select2({
                placeholder: "Select student IDs",
                allowClear: true,
                width: '100%'
            });
        });

        function setFilterType(type) {
            document.getElementById('filter_type').value = type;
            if (type === 'date_range') {
                document.getElementById('date-range-filter').style.display = 'block';
                document.getElementById('academic-year-filter').style.display = 'none';
            } else {
                document.getElementById('date-range-filter').style.display = 'none';
                document.getElementById('academic-year-filter').style.display = 'block';
            }

            // Update active state for filter buttons
            document.querySelectorAll('.filter-choice').forEach(btn => {
                if (btn.textContent.includes(type === 'date_range' ? 'Date Range' : 'Academic Year')) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
        }

        // Setup modal event listener
        document.addEventListener('DOMContentLoaded', function() {
            const itemsModal = document.getElementById('itemsModal');
            itemsModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const beneficiary = button.getAttribute('data-beneficiary');
                const associationId = button.getAttribute('data-association-id');
                const date = button.getAttribute('data-date');
                const itemsString = button.getAttribute('data-items');

                // Parse the items string into an array of objects
                const items = parseItemsString(itemsString);

                // Update modal title and info
                document.getElementById('beneficiaryName').textContent = `Items Received by ${beneficiary} (${associationId})`;
                document.getElementById('distributionDate').textContent = `Distribution Date: ${date}`;

                // Populate the table
                const tableBody = document.getElementById('itemsTableBody');
                tableBody.innerHTML = '';

                if (items.length > 0) {
                    items.forEach(item => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                        <td>${item.name}</td>
                        <td>${item.quantity}</td>
                        <td>${item.unit}</td>
                    `;
                        tableBody.appendChild(row);
                    });
                } else {
                    tableBody.innerHTML = '<tr><td colspan="3" class="text-center">No items found</td></tr>';
                }
            });
        });

        // Helper function to parse the items string
        function parseItemsString(itemsString) {
            const items = [];
            // Split by comma to get each item entry
            const entries = itemsString.split(', ');

            entries.forEach(entry => {
                // Match pattern: ItemName (Quantity Unit)
                const match = entry.match(/(.+)\s\(([\d.]+)\s(.+)\)/);
                if (match) {
                    items.push({
                        name: match[1].trim(),
                        quantity: match[2].trim(),
                        unit: match[3].trim()
                    });
                }
            });

            return items;
        }

        // Charts
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($monthly_labels) ?>,
                datasets: [{
                    label: 'Quantity Distributed',
                    data: <?= json_encode($monthly_data) ?>,
                    backgroundColor: '#4361ee',
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        const itemCtx = document.getElementById('itemChart').getContext('2d');
        new Chart(itemCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($item_labels) ?>,
                datasets: [{
                    label: 'Quantity Distributed',
                    data: <?= json_encode($item_data) ?>,
                    backgroundColor: [
                        '#4361ee', '#3ad29f', '#f72585', '#ffb703', '#7209b7', '#f48c06', '#4cc9f0', '#4895ef', '#f8961e', '#90be6d'
                    ],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                },
                cutout: '65%'
            }
        });
    </script>
    <script>
        $(document).ready(function() {
            // Include Student IDs
            $('#student_ids').select2({
                ajax: {
                    url: 'fetch_students.php',
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
        });
    </script>
</body>

</html>