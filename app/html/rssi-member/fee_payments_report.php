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

// Initialize filter variables
$from_month = isset($_GET['from_month']) ? $_GET['from_month'] : '';
$to_month = isset($_GET['to_month']) ? $_GET['to_month'] : '';
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : '';
$category_ids = isset($_GET['category_ids']) ? $_GET['category_ids'] : [];

// Check if months are selected
$months_selected = !empty($from_month) && !empty($to_month);

// Initialize variables
$has_data = false;
$total_amount = 0;
$total_records = 0;
$result = null;
$month_wise_result = null;
$category_wise_result = null;
$month_wise_totals = [];
$category_wise_totals = [];
$chart_labels = [];
$chart_data = [];
$pie_labels = [];
$pie_data = [];
$pie_colors = [];

// Build the query only if months are selected
if ($months_selected) {
    $query = "
        SELECT 
            fp.id,
            fp.student_id,
            s.studentname,
            fp.academic_year,
            fp.month,
            fp.amount,
            fp.payment_type,
            fp.transaction_id,
            fp.collected_by,
            m.fullname as collector_name,
            fp.collection_date,
            fp.notes,
            fc.category_name
        FROM fee_payments fp
        LEFT JOIN rssimyprofile_student s ON fp.student_id = s.student_id
        LEFT JOIN rssimyaccount_members m ON fp.collected_by = m.associatenumber
        LEFT JOIN fee_categories fc ON fp.category_id = fc.id
        WHERE 1=1
    ";

    $params = [];
    $param_count = 0;

    // Add date range filter
    if ($months_selected) {
        // Convert YYYY-MM to academic year and month for filtering
        list($from_year, $from_month_num) = explode('-', $from_month);
        list($to_year, $to_month_num) = explode('-', $to_month);

        $from_month_name = date('F', mktime(0, 0, 0, $from_month_num, 1));
        $to_month_name = date('F', mktime(0, 0, 0, $to_month_num, 1));

        $from_academic_year = $from_year;
        $to_academic_year = $to_year;

        $month_order = [
            'January' => 1,
            'February' => 2,
            'March' => 3,
            'April' => 4,
            'May' => 5,
            'June' => 6,
            'July' => 7,
            'August' => 8,
            'September' => 9,
            'October' => 10,
            'November' => 11,
            'December' => 12
        ];

        $query .= " AND (";

        if ($to_academic_year - $from_academic_year > 1) {
            $query .= " (fp.academic_year::integer > $from_academic_year AND fp.academic_year::integer < $to_academic_year) OR";
        }

        if ($from_academic_year == $to_academic_year) {
            $query .= " (fp.academic_year::integer = $from_academic_year AND 
                CASE fp.month 
                    WHEN 'January' THEN 1 WHEN 'February' THEN 2 WHEN 'March' THEN 3 
                    WHEN 'April' THEN 4 WHEN 'May' THEN 5 WHEN 'June' THEN 6 
                    WHEN 'July' THEN 7 WHEN 'August' THEN 8 WHEN 'September' THEN 9 
                    WHEN 'October' THEN 10 WHEN 'November' THEN 11 WHEN 'December' THEN 12 
                END BETWEEN {$month_order[$from_month_name]} AND {$month_order[$to_month_name]})";
        } else {
            $query .= " (fp.academic_year::integer = $from_academic_year AND 
                CASE fp.month 
                    WHEN 'January' THEN 1 WHEN 'February' THEN 2 WHEN 'March' THEN 3 
                    WHEN 'April' THEN 4 WHEN 'May' THEN 5 WHEN 'June' THEN 6 
                    WHEN 'July' THEN 7 WHEN 'August' THEN 8 WHEN 'September' THEN 9 
                    WHEN 'October' THEN 10 WHEN 'November' THEN 11 WHEN 'December' THEN 12 
                END >= {$month_order[$from_month_name]}) OR";

            if ($to_academic_year - $from_academic_year > 1) {
                $query .= " (fp.academic_year::integer > $from_academic_year AND fp.academic_year::integer < $to_academic_year) OR";
            }

            $query .= " (fp.academic_year::integer = $to_academic_year AND 
                CASE fp.month 
                    WHEN 'January' THEN 1 WHEN 'February' THEN 2 WHEN 'March' THEN 3 
                    WHEN 'April' THEN 4 WHEN 'May' THEN 5 WHEN 'June' THEN 6 
                    WHEN 'July' THEN 7 WHEN 'August' THEN 8 WHEN 'September' THEN 9 
                    WHEN 'October' THEN 10 WHEN 'November' THEN 11 WHEN 'December' THEN 12 
                END <= {$month_order[$to_month_name]})";
        }

        $query .= ")";
    }

    // Add other filters
    if (!empty($student_id)) {
        $param_count++;
        $query .= " AND fp.student_id = $$param_count";
        $params[] = $student_id;
    }

    if (!empty($category_ids) && is_array($category_ids)) {
        $category_placeholders = [];
        foreach ($category_ids as $category_id) {
            $param_count++;
            $category_placeholders[] = "$$param_count";
            $params[] = $category_id;
        }
        if (!empty($category_placeholders)) {
            $query .= " AND fc.id IN (" . implode(',', $category_placeholders) . ")";
        }
    }

    // Add ordering
    $query .= " ORDER BY fp.academic_year DESC, 
        CASE fp.month 
            WHEN 'April' THEN 1 WHEN 'May' THEN 2 WHEN 'June' THEN 3 
            WHEN 'July' THEN 4 WHEN 'August' THEN 5 WHEN 'September' THEN 6 
            WHEN 'October' THEN 7 WHEN 'November' THEN 8 WHEN 'December' THEN 9 
            WHEN 'January' THEN 10 WHEN 'February' THEN 11 WHEN 'March' THEN 12 
        END DESC, 
        fp.collection_date DESC";

    // Execute query
    $result = pg_query_params($con, $query, $params);

    // Check if we have data
    if ($result && pg_num_rows($result) > 0) {
        $has_data = true;
        $total_records = pg_num_rows($result);

        // Calculate total amount
        while ($row = pg_fetch_assoc($result)) {
            $total_amount += floatval($row['amount']);
        }
        pg_result_seek($result, 0); // Reset pointer to beginning

        // Analytics queries
        if ($months_selected) {
            // Month-wise totals query
            $month_wise_query = "
                SELECT 
                    fp.academic_year,
                    fp.month,
                    SUM(fp.amount) as month_total,
                    COUNT(*) as payment_count
                FROM fee_payments fp
                LEFT JOIN fee_categories fc ON fp.category_id = fc.id
                WHERE 1=1
            ";

            // Add the same date range conditions
            $month_wise_query .= " AND (";
            if ($to_academic_year - $from_academic_year > 1) {
                $month_wise_query .= " (fp.academic_year::integer > $from_academic_year AND fp.academic_year::integer < $to_academic_year) OR";
            }

            if ($from_academic_year == $to_academic_year) {
                $month_wise_query .= " (fp.academic_year::integer = $from_academic_year AND 
                    CASE fp.month 
                        WHEN 'January' THEN 1 WHEN 'February' THEN 2 WHEN 'March' THEN 3 
                        WHEN 'April' THEN 4 WHEN 'May' THEN 5 WHEN 'June' THEN 6 
                        WHEN 'July' THEN 7 WHEN 'August' THEN 8 WHEN 'September' THEN 9 
                        WHEN 'October' THEN 10 WHEN 'November' THEN 11 WHEN 'December' THEN 12 
                    END BETWEEN {$month_order[$from_month_name]} AND {$month_order[$to_month_name]})";
            } else {
                $month_wise_query .= " (fp.academic_year::integer = $from_academic_year AND 
                    CASE fp.month 
                        WHEN 'January' THEN 1 WHEN 'February' THEN 2 WHEN 'March' THEN 3 
                        WHEN 'April' THEN 4 WHEN 'May' THEN 5 WHEN 'June' THEN 6 
                        WHEN 'July' THEN 7 WHEN 'August' THEN 8 WHEN 'September' THEN 9 
                        WHEN 'October' THEN 10 WHEN 'November' THEN 11 WHEN 'December' THEN 12 
                    END >= {$month_order[$from_month_name]}) OR";

                if ($to_academic_year - $from_academic_year > 1) {
                    $month_wise_query .= " (fp.academic_year::integer > $from_academic_year AND fp.academic_year::integer < $to_academic_year) OR";
                }

                $month_wise_query .= " (fp.academic_year::integer = $to_academic_year AND 
                    CASE fp.month 
                        WHEN 'January' THEN 1 WHEN 'February' THEN 2 WHEN 'March' THEN 3 
                        WHEN 'April' THEN 4 WHEN 'May' THEN 5 WHEN 'June' THEN 6 
                        WHEN 'July' THEN 7 WHEN 'August' THEN 8 WHEN 'September' THEN 9 
                        WHEN 'October' THEN 10 WHEN 'November' THEN 11 WHEN 'December' THEN 12 
                    END <= {$month_order[$to_month_name]})";
            }
            $month_wise_query .= ")";

            // Add other filters
            if (!empty($student_id)) {
                $month_wise_query .= " AND fp.student_id = '$student_id'";
            }
            if (!empty($category_ids) && is_array($category_ids)) {
                $category_ids_str = implode("','", $category_ids);
                $month_wise_query .= " AND fc.id IN ('$category_ids_str')";
            }

            $month_wise_query .= " GROUP BY fp.academic_year, fp.month
                                  ORDER BY fp.academic_year DESC, 
                                  CASE fp.month 
                                      WHEN 'April' THEN 1 WHEN 'May' THEN 2 WHEN 'June' THEN 3 
                                      WHEN 'July' THEN 4 WHEN 'August' THEN 5 WHEN 'September' THEN 6 
                                      WHEN 'October' THEN 7 WHEN 'November' THEN 8 WHEN 'December' THEN 9 
                                      WHEN 'January' THEN 10 WHEN 'February' THEN 11 WHEN 'March' THEN 12 
                                  END DESC";

            $month_wise_result = pg_query($con, $month_wise_query);

            if ($month_wise_result && pg_num_rows($month_wise_result) > 0) {
                while ($row = pg_fetch_assoc($month_wise_result)) {
                    $month_wise_totals[] = $row;
                    $chart_labels[] = $row['month'] . ' ' . $row['academic_year'];
                    $chart_data[] = floatval($row['month_total']);
                }
            }

            // Category-wise totals query
            $category_wise_query = "
                SELECT 
                    fc.category_name,
                    SUM(fp.amount) as category_total,
                    COUNT(*) as payment_count
                FROM fee_payments fp
                LEFT JOIN fee_categories fc ON fp.category_id = fc.id
                WHERE 1=1
            ";

            // Add the same date range conditions
            $category_wise_query .= " AND (";
            if ($to_academic_year - $from_academic_year > 1) {
                $category_wise_query .= " (fp.academic_year::integer > $from_academic_year AND fp.academic_year::integer < $to_academic_year) OR";
            }

            if ($from_academic_year == $to_academic_year) {
                $category_wise_query .= " (fp.academic_year::integer = $from_academic_year AND 
                    CASE fp.month 
                        WHEN 'January' THEN 1 WHEN 'February' THEN 2 WHEN 'March' THEN 3 
                        WHEN 'April' THEN 4 WHEN 'May' THEN 5 WHEN 'June' THEN 6 
                        WHEN 'July' THEN 7 WHEN 'August' THEN 8 WHEN 'September' THEN 9 
                        WHEN 'October' THEN 10 WHEN 'November' THEN 11 WHEN 'December' THEN 12 
                    END BETWEEN {$month_order[$from_month_name]} AND {$month_order[$to_month_name]})";
            } else {
                $category_wise_query .= " (fp.academic_year::integer = $from_academic_year AND 
                    CASE fp.month 
                        WHEN 'January' THEN 1 WHEN 'February' THEN 2 WHEN 'March' THEN 3 
                        WHEN 'April' THEN 4 WHEN 'May' THEN 5 WHEN 'June' THEN 6 
                        WHEN 'July' THEN 7 WHEN 'August' THEN 8 WHEN 'September' THEN 9 
                        WHEN 'October' THEN 10 WHEN 'November' THEN 11 WHEN 'December' THEN 12 
                    END >= {$month_order[$from_month_name]}) OR";

                if ($to_academic_year - $from_academic_year > 1) {
                    $category_wise_query .= " (fp.academic_year::integer > $from_academic_year AND fp.academic_year::integer < $to_academic_year) OR";
                }

                $category_wise_query .= " (fp.academic_year::integer = $to_academic_year AND 
                    CASE fp.month 
                        WHEN 'January' THEN 1 WHEN 'February' THEN 2 WHEN 'March' THEN 3 
                        WHEN 'April' THEN 4 WHEN 'May' THEN 5 WHEN 'June' THEN 6 
                        WHEN 'July' THEN 7 WHEN 'August' THEN 8 WHEN 'September' THEN 9 
                        WHEN 'October' THEN 10 WHEN 'November' THEN 11 WHEN 'December' THEN 12 
                    END <= {$month_order[$to_month_name]})";
            }
            $category_wise_query .= ")";

            // Add other filters
            if (!empty($student_id)) {
                $category_wise_query .= " AND fp.student_id = '$student_id'";
            }
            if (!empty($category_ids) && is_array($category_ids)) {
                $category_ids_str = implode("','", $category_ids);
                $category_wise_query .= " AND fc.id IN ('$category_ids_str')";
            }

            $category_wise_query .= " GROUP BY fc.category_name
                                     ORDER BY category_total DESC";

            $category_wise_result = pg_query($con, $category_wise_query);

            if ($category_wise_result && pg_num_rows($category_wise_result) > 0) {
                $color_palette = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#5a5c69', '#6f42c1', '#e83e8c', '#fd7e14'];
                $color_index = 0;

                while ($row = pg_fetch_assoc($category_wise_result)) {
                    $category_wise_totals[] = $row;
                    $pie_labels[] = $row['category_name'];
                    $pie_data[] = floatval($row['category_total']);
                    $pie_colors[] = $color_palette[$color_index % count($color_palette)];
                    $color_index++;
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'includes/meta.php' ?>
    
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .card {
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            border: 1px solid #e3e6f0;
        }

        .table th {
            background-color: #4e73df;
            color: white;
        }

        .total-row {
            background-color: #f8f9fc;
            font-weight: bold;
        }

        .filter-section {
            background-color: #f8f9fc;
            border-radius: 0.35rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .summary-card {
            transition: transform 0.2s;
        }

        .summary-card:hover {
            transform: translateY(-5px);
        }

        .alert-warning {
            border-left: 4px solid #ffc107;
        }

        .analytics-section {
            margin-bottom: 2rem;
        }

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        .analytics-card {
            height: 100%;
        }

        .no-data-message {
            padding: 3rem 1rem;
            text-align: center;
            color: #6c757d;
        }

        .no-data-message i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1><?php echo getPageTitle(); ?></h1>
            <?php echo generateDynamicBreadcrumb(); ?>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">

                <!-- Reports -->
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <br>
                            <div class="container-fluid">
                                <div class="row">
                                    <div class="col-12">
                                        <div class="card mt-4">
                                            <div class="card-body">
                                                <!-- Summary Cards - Only show if we have data -->
                                                <?php if ($months_selected && $has_data): ?>
                                                    <div class="row mt-4 mb-4">
                                                        <div class="col-md-3">
                                                            <div class="card summary-card bg-primary text-white">
                                                                <div class="card-body mt-2">
                                                                    <div class="d-flex justify-content-between">
                                                                        <div>
                                                                            <div class="text-white-50 small">Total Records</div>
                                                                            <div class="fs-5 fw-bold"><?php echo $total_records; ?></div>
                                                                        </div>
                                                                        <div class="col-auto">
                                                                            <i class="fas fa-list fa-2x text-white-50"></i>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="card summary-card bg-success text-white">
                                                                <div class="card-body mt-2">
                                                                    <div class="d-flex justify-content-between">
                                                                        <div>
                                                                            <div class="text-white-50 small">Total Amount</div>
                                                                            <div class="fs-5 fw-bold">₹<?php echo number_format($total_amount, 2); ?></div>
                                                                        </div>
                                                                        <div class="col-auto">
                                                                            <i class="fas fa-rupee-sign fa-2x text-white-50"></i>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>

                                                <!-- Filter Section -->
                                                <div class="filter-section">
                                                    <form method="GET" class="row g-3">
                                                        <div class="col-md-6">
                                                            <label class="form-label">Month Range *</label>
                                                            <div class="input-group">
                                                                <input type="month" class="form-control" id="from_month" name="from_month"
                                                                    value="<?php echo htmlspecialchars($from_month); ?>" required>
                                                                <span class="input-group-text">to</span>
                                                                <input type="month" class="form-control" id="to_month" name="to_month"
                                                                    value="<?php echo htmlspecialchars($to_month); ?>" required>
                                                            </div>
                                                            <div class="form-text text-danger">Please select both From and To months to view results</div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label for="student_id" class="form-label">Student ID</label>
                                                            <select class="form-select" id="student_id" name="student_id">
                                                                <?php if (!empty($student_id)): ?>
                                                                    <option value="<?php echo htmlspecialchars($student_id); ?>" selected>
                                                                        <?php echo htmlspecialchars($student_id); ?>
                                                                    </option>
                                                                <?php endif; ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label for="category_ids" class="form-label">Fee Categories</label>
                                                            <select class="form-select" id="category_ids" name="category_ids[]" multiple="multiple">
                                                                <?php foreach ($category_ids as $id): ?>
                                                                    <option value="<?php echo $id; ?>" selected></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-12">
                                                            <button type="submit" class="btn btn-primary">
                                                                <i class="fas fa-filter me-1"></i> Apply Filters
                                                            </button>
                                                            <a href="fee_payments_report.php" class="btn btn-secondary">
                                                                <i class="fas fa-redo me-1"></i> Reset
                                                            </a>
                                                        </div>
                                                    </form>
                                                </div>

                                                <!-- No Data Alert -->
                                                <?php if ($months_selected && !$has_data): ?>
                                                    <div class="alert alert-info alert-dismissible fade show mt-4" role="alert">
                                                        <i class="fas fa-info-circle me-2"></i>
                                                        <strong>No data found</strong> for the selected criteria. Please try different filters.
                                                    </div>
                                                <?php endif; ?>

                                                <!-- Analytics Section - Only show if we have data -->
                                                <?php if ($months_selected && $has_data): ?>
                                                    <div class="analytics-section">
                                                        <div class="row">
                                                            <!-- Month-wise Collection -->
                                                            <div class="col-md-6 mb-4">
                                                                <div class="card analytics-card">
                                                                    <div class="card-header">
                                                                        <h5 class="card-title mb-0">
                                                                            <i class="fas fa-calendar-alt me-2"></i>
                                                                            Month-wise Collection
                                                                        </h5>
                                                                    </div>
                                                                    <div class="card-body">
                                                                        <?php if (!empty($month_wise_totals)): ?>
                                                                            <div class="chart-container">
                                                                                <canvas id="monthChart"></canvas>
                                                                            </div>
                                                                            <div class="table-responsive mt-3">
                                                                                <table class="table table-sm table-bordered">
                                                                                    <thead>
                                                                                        <tr>
                                                                                            <th>Academic Year</th>
                                                                                            <th>Month</th>
                                                                                            <th>Total Amount</th>
                                                                                            <th>Payments</th>
                                                                                        </tr>
                                                                                    </thead>
                                                                                    <tbody>
                                                                                        <?php foreach ($month_wise_totals as $month_data): ?>
                                                                                            <tr>
                                                                                                <td><?php echo htmlspecialchars($month_data['academic_year']); ?></td>
                                                                                                <td><?php echo htmlspecialchars($month_data['month']); ?></td>
                                                                                                <td>₹<?php echo number_format($month_data['month_total'], 2); ?></td>
                                                                                                <td><?php echo htmlspecialchars($month_data['payment_count']); ?></td>
                                                                                            </tr>
                                                                                        <?php endforeach; ?>
                                                                                    </tbody>
                                                                                </table>
                                                                            </div>
                                                                        <?php else: ?>
                                                                            <div class="no-data-message">
                                                                                <i class="fas fa-chart-line"></i><br>
                                                                                No analytics data available for month-wise collection.
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- Category-wise Collection -->
                                                            <div class="col-md-6 mb-4">
                                                                <div class="card analytics-card">
                                                                    <div class="card-header">
                                                                        <h5 class="card-title mb-0">
                                                                            <i class="fas fa-chart-pie me-2"></i>
                                                                            Category-wise Collection
                                                                        </h5>
                                                                    </div>
                                                                    <div class="card-body">
                                                                        <?php if (!empty($category_wise_totals)): ?>
                                                                            <div class="chart-container">
                                                                                <canvas id="categoryChart"></canvas>
                                                                            </div>
                                                                            <div class="table-responsive mt-3">
                                                                                <table class="table table-sm table-bordered">
                                                                                    <thead>
                                                                                        <tr>
                                                                                            <th>Category</th>
                                                                                            <th>Total Amount</th>
                                                                                            <th>Payments</th>
                                                                                        </tr>
                                                                                    </thead>
                                                                                    <tbody>
                                                                                        <?php foreach ($category_wise_totals as $category_data): ?>
                                                                                            <tr>
                                                                                                <td><?php echo htmlspecialchars($category_data['category_name']); ?></td>
                                                                                                <td>₹<?php echo number_format($category_data['category_total'], 2); ?></td>
                                                                                                <td><?php echo htmlspecialchars($category_data['payment_count']); ?></td>
                                                                                            </tr>
                                                                                        <?php endforeach; ?>
                                                                                    </tbody>
                                                                                </table>
                                                                            </div>
                                                                        <?php else: ?>
                                                                            <div class="no-data-message">
                                                                                <i class="fas fa-chart-pie"></i><br>
                                                                                No analytics data available for category-wise collection.
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>

                                                <!-- Results Section -->
                                                <div class="table-responsive">
                                                    <?php if (!$months_selected): ?>
                                                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                                            <strong>Please select a month range</strong> to view fee payments data. Choose both "From" and "To" months above and click "Apply Filters".
                                                        </div>
                                                    <?php elseif ($has_data): ?>
                                                        <table class="table table-bordered table-hover" id="paymentsTable">
                                                            <thead>
                                                                <tr>
                                                                    <th>ID</th>
                                                                    <th>Student ID</th>
                                                                    <th>Student Name</th>
                                                                    <th>Academic Year</th>
                                                                    <th>Month</th>
                                                                    <th>Category</th>
                                                                    <th>Amount</th>
                                                                    <th>Payment Type</th>
                                                                    <th>Transaction ID</th>
                                                                    <th>Collected By</th>
                                                                    <th>Collection Date</th>
                                                                    <th>Notes</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php while ($row = pg_fetch_assoc($result)): ?>
                                                                    <tr>
                                                                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                                                                        <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                                                                        <td><?php echo htmlspecialchars($row['studentname'] ?? ''); ?></td>
                                                                        <td><?php echo htmlspecialchars($row['academic_year']); ?></td>
                                                                        <td><?php echo htmlspecialchars($row['month']); ?></td>
                                                                        <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                                                        <td>₹<?php echo number_format($row['amount'], 2); ?></td>
                                                                        <td><?php echo htmlspecialchars($row['payment_type']); ?></td>
                                                                        <td><?php echo htmlspecialchars($row['transaction_id'] ?? ''); ?></td>
                                                                        <td><?php echo htmlspecialchars($row['collector_name']); ?></td>
                                                                        <td><?php echo date('d-M-Y', strtotime($row['collection_date'])); ?></td>
                                                                        <td><?php echo htmlspecialchars($row['notes'] ?? ''); ?></td>
                                                                    </tr>
                                                                <?php endwhile; ?>
                                                            </tbody>
                                                        </table>
                                                    <?php endif; ?>
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
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable only if we have data and table exists
            <?php if ($months_selected && $has_data): ?>
                $('#paymentsTable').DataTable({
                    "pageLength": 25,
                    "order": [
                        [0, 'desc']
                    ],
                    "dom": '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>'
                });
            <?php endif; ?>

            // Date validation - ensure to_month is not before from_month
            $('#from_month, #to_month').change(function() {
                const fromMonth = $('#from_month').val();
                const toMonth = $('#to_month').val();

                if (fromMonth && toMonth && fromMonth > toMonth) {
                    alert('To Month cannot be before From Month');
                    $('#to_month').val(fromMonth);
                }
            });

            // Initialize Charts only if we have data
            <?php if ($months_selected && $has_data && !empty($chart_labels) && !empty($chart_data)): ?>
                // Month-wise Line Chart
                const monthCtx = document.getElementById('monthChart');
                if (monthCtx) {
                    const monthChart = new Chart(monthCtx.getContext('2d'), {
                        type: 'line',
                        data: {
                            labels: <?php echo json_encode($chart_labels); ?>,
                            datasets: [{
                                label: 'Collection Amount (₹)',
                                data: <?php echo json_encode($chart_data); ?>,
                                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                                borderColor: '#4e73df',
                                borderWidth: 2,
                                pointBackgroundColor: '#4e73df',
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 2,
                                pointRadius: 5,
                                pointHoverRadius: 7,
                                fill: true,
                                tension: 0.3
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return '₹' + value.toLocaleString();
                                        }
                                    },
                                    grid: {
                                        drawBorder: false
                                    }
                                },
                                x: {
                                    grid: {
                                        display: false
                                    }
                                }
                            },
                            plugins: {
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return 'Amount: ₹' + context.parsed.y.toLocaleString();
                                        }
                                    }
                                },
                                legend: {
                                    display: true,
                                    position: 'top',
                                }
                            },
                            interaction: {
                                intersect: false,
                                mode: 'index'
                            }
                        }
                    });
                }
            <?php endif; ?>

            <?php if ($months_selected && $has_data && !empty($pie_labels) && !empty($pie_data)): ?>
                // Category-wise Pie Chart
                const categoryCtx = document.getElementById('categoryChart');
                if (categoryCtx) {
                    const categoryChart = new Chart(categoryCtx.getContext('2d'), {
                        type: 'pie',
                        data: {
                            labels: <?php echo json_encode($pie_labels); ?>,
                            datasets: [{
                                data: <?php echo json_encode($pie_data); ?>,
                                backgroundColor: <?php echo json_encode($pie_colors); ?>,
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'right',
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const label = context.label || '';
                                            const value = context.parsed || 0;
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = Math.round((value / total) * 100);
                                            return `${label}: ₹${value.toLocaleString()} (${percentage}%)`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            <?php endif; ?>
        });
    </script>
    <script>
        $(document).ready(function() {
            // Initialize Select2 for student IDs
            $('#student_id').select2({
                ajax: {
                    url: 'fetch_students.php',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term,
                            //isActive: true
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.results || []
                        };
                    },
                    cache: true
                },
                minimumInputLength: 2,
                placeholder: 'Select student',
                allowClear: true,
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            // Initialize Select2 for categories
            $('#category_ids').select2({
                placeholder: "Select Fee Categories",
                allowClear: true,
                ajax: {
                    url: "fetch_fee_categories.php",
                    type: "GET",
                    dataType: "json",
                    delay: 250,
                    data: function(params) {
                        return {
                            search: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: $.map(data, function(item) {
                                return {
                                    id: item.id,
                                    text: item.category_name
                                };
                            })
                        };
                    }
                }
            });

            // Load preselected category names
            <?php if (!empty($category_ids)): ?>
                $.ajax({
                    url: "fetch_fee_categories.php",
                    type: "GET",
                    data: {
                        preload: "<?php echo implode(',', $category_ids); ?>"
                    },
                    dataType: "json",
                    success: function(data) {
                        $('#category_ids').empty();
                        data.forEach(function(item) {
                            var option = new Option(item.category_name, item.id, true, true);
                            $('#category_ids').append(option).trigger('change');
                        });
                    }
                });
            <?php endif; ?>
        });
    </script>
</body>

</html>

<?php
// Close database connections
if (isset($result) && $result) {
    pg_free_result($result);
}
if (isset($month_wise_result) && $month_wise_result) {
    pg_free_result($month_wise_result);
}
if (isset($category_wise_result) && $category_wise_result) {
    pg_free_result($category_wise_result);
}
?>