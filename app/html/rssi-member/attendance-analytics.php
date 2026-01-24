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

// Default filter values
$current_year = date('Y');
$current_month = date('m');
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : $current_year;
$selected_month = isset($_GET['month']) ? intval($_GET['month']) : $current_month;
$start_year = isset($_GET['start_year']) ? intval($_GET['start_year']) : $current_year - 2;
$end_year = isset($_GET['end_year']) ? intval($_GET['end_year']) : $current_year;
$view_mode = isset($_GET['view']) ? $_GET['view'] : 'daily'; // 'daily' or 'yearly'
$compare_year = isset($_GET['compare_year']) ? intval($_GET['compare_year']) : null;
$compare_month = isset($_GET['compare_month']) ? intval($_GET['compare_month']) : null;

// Handle multiple prefixes
$user_prefix = isset($_GET['user_prefix']) ? $_GET['user_prefix'] : ['A'];
if (!is_array($user_prefix)) {
    $user_prefix = [$user_prefix];
}

// Build prefix condition
$prefix_conditions = [];
foreach ($user_prefix as $prefix) {
    if ($prefix === 'OTHER') {
        $prefix_conditions[] = "(user_id NOT LIKE 'A%' AND user_id NOT LIKE 'B%')";
    } else {
        $escaped = pg_escape_string($con, $prefix);
        $prefix_conditions[] = "user_id LIKE '$escaped%'";
    }
}
$where_prefix_filter = !empty($prefix_conditions) ? 'AND (' . implode(' OR ', $prefix_conditions) . ')' : '';

/*********************** DAILY DATA ***********************/
$daily_data = null;
$comparison_data = null;
$labels = [];
$main_counts = [];
$compare_counts = [];

$final_daily_data = []; // Initialize the array

if ($view_mode === 'daily') {
    // Function to get days in month
    function getDaysInMonth($month, $year)
    {
        $date = new DateTime("$year-$month-01");
        return (int)$date->format('t');
    }

    // Get number of days in the selected month
    $days_in_month = getDaysInMonth($selected_month, $selected_year);

    // Main query for selected period
    $daily_query = "
    SELECT 
        DATE(punch_in) AS day,
        EXTRACT(DAY FROM punch_in) AS day_num,
        COUNT(DISTINCT user_id) AS daily_count
    FROM attendance
    WHERE EXTRACT(YEAR FROM punch_in) = $selected_year
    AND EXTRACT(MONTH FROM punch_in) = $selected_month
    $where_prefix_filter
    GROUP BY DATE(punch_in), EXTRACT(DAY FROM punch_in)
    ORDER BY DATE(punch_in)";

    $daily_result = pg_query($con, $daily_query);
    $daily_data = pg_fetch_all($daily_result);

    // Create arrays for all days of month, initialized with null
    $all_labels = [];
    $all_main_counts = array_fill(1, $days_in_month, null);

    if ($daily_data) {
        foreach ($daily_data as $row) {
            $day_num = (int)$row['day_num'];
            $all_main_counts[$day_num] = (int)$row['daily_count'];
        }
    }

    // CASE 1: No comparison
    if (!$compare_year || !$compare_month) {
        for ($day = 1; $day <= $days_in_month; $day++) {
            $labels[] = date('M j', mktime(0, 0, 0, $selected_month, $day, $selected_year));
            $main_counts[] = $all_main_counts[$day];
        }

        foreach ($daily_data as $row) {
            $date = new DateTime($row['day']);
            $final_daily_data[] = [
                'date' => $date->format('M j'),
                'day' => $date->format('D'),
                'main_count' => $row['daily_count'],
                'compare_count' => null,
                'difference' => null
            ];
        }
    }
    // CASE 2: With comparison
    else {
        // Get number of days in comparison month
        $compare_days_in_month = getDaysInMonth($compare_month, $compare_year);

        // Query all comparison data for the month
        $compare_query = "
        SELECT 
            EXTRACT(DAY FROM punch_in) AS day_num,
            COUNT(DISTINCT user_id) AS daily_count
        FROM attendance
        WHERE EXTRACT(YEAR FROM punch_in) = $compare_year
        AND EXTRACT(MONTH FROM punch_in) = $compare_month
        $where_prefix_filter
        GROUP BY day_num
        ORDER BY day_num";

        $compare_result = pg_query($con, $compare_query);
        $comparison_data = pg_fetch_all($compare_result);

        // Create array for comparison counts
        $all_compare_counts = array_fill(1, $compare_days_in_month, null);
        if ($comparison_data) {
            foreach ($comparison_data as $row) {
                $day_num = (int)$row['day_num'];
                $all_compare_counts[$day_num] = (int)$row['daily_count'];
            }
        }

        // Prepare chart data for all days that exist in both months
        $max_days = min($days_in_month, $compare_days_in_month);

        for ($day = 1; $day <= $max_days; $day++) {
            $labels[] = date('M j', mktime(0, 0, 0, $selected_month, $day, $selected_year));
            $main_count = $all_main_counts[$day];
            $compare_count = $all_compare_counts[$day];
            $difference = null;

            if ($main_count !== null && $compare_count !== null) {
                $difference = $main_count - $compare_count;
            }

            $main_counts[] = $main_count;
            $compare_counts[] = $compare_count;

            $final_daily_data[] = [
                'date' => date('M j', mktime(0, 0, 0, $selected_month, $day, $selected_year)),
                'day' => date('D', mktime(0, 0, 0, $selected_month, $day, $selected_year)),
                'main_count' => $main_count,
                'compare_count' => $compare_count,
                'difference' => $difference
            ];
        }
    }
}
/*********************** YEARLY DATA ***********************/
$yearly_results = null;
$yearly_labels = [];
$yearly_data = [];
$monthly_breakdown_data = [];

if ($view_mode === 'yearly') {
    $yearly_query = "
    SELECT 
        EXTRACT(YEAR FROM punch_in)::int AS year,
        COUNT(DISTINCT user_id || '-' || DATE(punch_in)) AS total_present_days,
        COUNT(DISTINCT CASE WHEN EXTRACT(MONTH FROM punch_in) = 1 THEN user_id || '-' || DATE(punch_in) END) AS jan,
        COUNT(DISTINCT CASE WHEN EXTRACT(MONTH FROM punch_in) = 2 THEN user_id || '-' || DATE(punch_in) END) AS feb,
        COUNT(DISTINCT CASE WHEN EXTRACT(MONTH FROM punch_in) = 3 THEN user_id || '-' || DATE(punch_in) END) AS mar,
        COUNT(DISTINCT CASE WHEN EXTRACT(MONTH FROM punch_in) = 4 THEN user_id || '-' || DATE(punch_in) END) AS apr,
        COUNT(DISTINCT CASE WHEN EXTRACT(MONTH FROM punch_in) = 5 THEN user_id || '-' || DATE(punch_in) END) AS may,
        COUNT(DISTINCT CASE WHEN EXTRACT(MONTH FROM punch_in) = 6 THEN user_id || '-' || DATE(punch_in) END) AS jun,
        COUNT(DISTINCT CASE WHEN EXTRACT(MONTH FROM punch_in) = 7 THEN user_id || '-' || DATE(punch_in) END) AS jul,
        COUNT(DISTINCT CASE WHEN EXTRACT(MONTH FROM punch_in) = 8 THEN user_id || '-' || DATE(punch_in) END) AS aug,
        COUNT(DISTINCT CASE WHEN EXTRACT(MONTH FROM punch_in) = 9 THEN user_id || '-' || DATE(punch_in) END) AS sep,
        COUNT(DISTINCT CASE WHEN EXTRACT(MONTH FROM punch_in) = 10 THEN user_id || '-' || DATE(punch_in) END) AS oct,
        COUNT(DISTINCT CASE WHEN EXTRACT(MONTH FROM punch_in) = 11 THEN user_id || '-' || DATE(punch_in) END) AS nov,
        COUNT(DISTINCT CASE WHEN EXTRACT(MONTH FROM punch_in) = 12 THEN user_id || '-' || DATE(punch_in) END) AS dec
    FROM attendance
    WHERE EXTRACT(YEAR FROM punch_in) BETWEEN $start_year AND $end_year
    $where_prefix_filter
    GROUP BY EXTRACT(YEAR FROM punch_in)
    ORDER BY year DESC";

    $yearly_result = pg_query($con, $yearly_query);
    $yearly_results = pg_fetch_all($yearly_result);

    if ($yearly_results) {
        foreach ($yearly_results as $row) {
            $yearly_labels[] = $row['year'];
            $yearly_data[] = $row['total_present_days'];

            // Prepare monthly breakdown data
            $monthly_breakdown_data[$row['year']] = [
                $row['jan'],
                $row['feb'],
                $row['mar'],
                $row['apr'],
                $row['may'],
                $row['jun'],
                $row['jul'],
                $row['aug'],
                $row['sep'],
                $row['oct'],
                $row['nov'],
                $row['dec']
            ];
        }
    }
}

// Get min/max years for dropdowns
$min_year_query = "SELECT EXTRACT(YEAR FROM MIN(punch_in))::int AS min_year FROM attendance";
$max_year_query = "SELECT EXTRACT(YEAR FROM MAX(punch_in))::int AS max_year FROM attendance";
$min_year_result = pg_query($con, $min_year_query);
$max_year_result = pg_query($con, $max_year_query);
$min_year = pg_fetch_result($min_year_result, 0, 0);
$max_year = pg_fetch_result($max_year_result, 0, 0);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'includes/meta.php' ?>
    
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <style>
        .card {
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .table-responsive {
            margin-top: 20px;
        }

        .chart-container {
            position: relative;
            height: 400px;
            width: 100%;
        }

        .filter-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .comparison-section {
            background-color: #e9f7ef;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
        }

        .stats-card {
            text-align: center;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .stats-value {
            font-size: 1.8rem;
            font-weight: bold;
        }

        .stats-label {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .view-switcher {
            margin-bottom: 20px;
        }

        .nav-pills .nav-link.active {
            background-color: #0d6efd;
        }

        .tab-content {
            padding: 20px 0;
        }

        .monthly-bars {
            height: 300px;
        }

        .difference-positive {
            color: #28a745;
            font-weight: bold;
        }

        .difference-negative {
            color: #dc3545;
            font-weight: bold;
        }

        .year-range-section {
            background-color: #f0f8ff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
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
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <br>
                            <div class="container-fluid py-4">
                                <!-- View Switcher -->
                                <ul class="nav nav-pills view-switcher">
                                    <li class="nav-item">
                                        <a class="nav-link <?= $view_mode === 'daily' ? 'active' : '' ?>"
                                            href="?view=daily&year=<?= $selected_year ?>&month=<?= $selected_month ?>&user_prefix[]=<?= implode('&user_prefix[]=', $user_prefix) ?>">
                                            <i class="bi bi-calendar-day"></i> Daily Analysis
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?= $view_mode === 'yearly' ? 'active' : '' ?>"
                                            href="?view=yearly&start_year=<?= $start_year ?>&end_year=<?= $end_year ?>&user_prefix[]=<?= implode('&user_prefix[]=', $user_prefix) ?>">
                                            <i class="bi bi-calendar-range"></i> Yearly Trends
                                        </a>
                                    </li>
                                </ul>

                                <!-- Filter Section -->
                                <div class="filter-section mb-4">
                                    <form method="get" class="row g-3">
                                        <input type="hidden" name="view" value="<?= $view_mode ?>">

                                        <?php if ($view_mode === 'daily'): ?>
                                            <div class="col-md-3">
                                                <label for="year" class="form-label">Year</label>
                                                <select class="form-select" id="year" name="year">
                                                    <?php for ($y = $min_year; $y <= $max_year; $y++): ?>
                                                        <option value="<?= $y ?>" <?= $y == $selected_year ? 'selected' : '' ?>><?= $y ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="month" class="form-label">Month</label>
                                                <select class="form-select" id="month" name="month">
                                                    <?php for ($m = 1; $m <= 12; $m++): ?>
                                                        <option value="<?= $m ?>" <?= $m == $selected_month ? 'selected' : '' ?>>
                                                            <?= DateTime::createFromFormat('!m', $m)->format('F') ?>
                                                        </option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                        <?php else: ?>
                                            <!-- Year Range Selector for Yearly View -->
                                            <div class="year-range-section col-12">
                                                <h5><i class="bi bi-calendar-range"></i> Year Range</h5>
                                                <div class="row g-3">
                                                    <div class="col-md-3">
                                                        <label for="start_year" class="form-label">From Year</label>
                                                        <select class="form-select" id="start_year" name="start_year">
                                                            <?php for ($y = $min_year; $y <= $max_year; $y++): ?>
                                                                <option value="<?= $y ?>" <?= $y == $start_year ? 'selected' : '' ?>><?= $y ?></option>
                                                            <?php endfor; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label for="end_year" class="form-label">To Year</label>
                                                        <select class="form-select" id="end_year" name="end_year">
                                                            <?php for ($y = $min_year; $y <= $max_year; $y++): ?>
                                                                <option value="<?= $y ?>" <?= $y == $end_year ? 'selected' : '' ?>><?= $y ?></option>
                                                            <?php endfor; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-2 d-flex align-items-end">
                                                        <button type="submit" class="btn btn-primary me-2"><i class="bi bi-arrow-repeat"></i> Update Range</button>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <div class="col-md-4">
                                            <label for="user_prefix" class="form-label">User ID Prefix</label>
                                            <select class="form-select" id="user_prefix" name="user_prefix[]" multiple>
                                                <option value="A" <?= in_array('A', $user_prefix) ? 'selected' : '' ?>>Starts with A</option>
                                                <option value="B" <?= in_array('B', $user_prefix) ? 'selected' : '' ?>>Starts with B</option>
                                                <option value="OTHER" <?= in_array('OTHER', $user_prefix) ? 'selected' : '' ?>>Does NOT start with A or B</option>
                                            </select>
                                            <small>Hold Ctrl (Windows) or Command (Mac) to select multiple</small>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end">
                                            <button type="submit" class="btn btn-primary me-2"><i class="bi bi-filter"></i> Apply</button>
                                        </div>

                                        <?php if ($view_mode === 'daily'): ?>
                                            <!-- Comparison Section -->
                                            <div class="comparison-section col-12">
                                                <h5><i class="bi bi-compass"></i> Compare With</h5>
                                                <div class="row g-3">
                                                    <div class="col-md-3">
                                                        <label for="compare_year" class="form-label">Year</label>
                                                        <select class="form-select" id="compare_year" name="compare_year">
                                                            <option value="">-- Select Year --</option>
                                                            <?php for ($y = $min_year; $y <= $max_year; $y++): ?>
                                                                <option value="<?= $y ?>" <?= $y == $compare_year ? 'selected' : '' ?>><?= $y ?></option>
                                                            <?php endfor; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label for="compare_month" class="form-label">Month</label>
                                                        <select class="form-select" id="compare_month" name="compare_month">
                                                            <option value="">-- Select Month --</option>
                                                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                                                <option value="<?= $m ?>" <?= $m == $compare_month ? 'selected' : '' ?>>
                                                                    <?= DateTime::createFromFormat('!m', $m)->format('F') ?>
                                                                </option>
                                                            <?php endfor; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-2 d-flex align-items-end">
                                                        <button type="submit" class="btn btn-outline-primary me-2"><i class="bi bi-arrow-left-right"></i> Compare</button>
                                                        <a href="?view=daily&year=<?= $selected_year ?>&month=<?= $selected_month ?>&user_prefix[]=<?= implode('&user_prefix[]=', $user_prefix) ?>" class="btn btn-outline-secondary">Clear</a>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </form>
                                </div>

                                <?php if ($view_mode === 'daily'): ?>
                                    <!-- DAILY VIEW CONTENT -->
                                    <?php if ($daily_data): ?>
                                        <!-- Stats Cards -->
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="stats-card bg-light">
                                                    <div class="stats-value text-primary">
                                                        <?= array_sum(array_column($daily_data, 'daily_count')) ?>
                                                    </div>
                                                    <div class="stats-label">Total Footfall</div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="stats-card bg-light">
                                                    <div class="stats-value text-success">
                                                        <?= round(array_sum(array_column($daily_data, 'daily_count')) / count($daily_data), 1) ?>
                                                    </div>
                                                    <div class="stats-label">Daily Average</div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="stats-card bg-light">
                                                    <div class="stats-value text-info">
                                                        <?= max(array_column($daily_data, 'daily_count')) ?>
                                                    </div>
                                                    <div class="stats-label">Peak Day</div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="stats-card bg-light">
                                                    <div class="stats-value text-warning">
                                                        <?= min(array_column($daily_data, 'daily_count')) ?>
                                                    </div>
                                                    <div class="stats-label">Lowest Day</div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Main Chart -->
                                        <div class="card mt-4">
                                            <div class="card-header">
                                                <h5>Daily Footfall Trend - <?= DateTime::createFromFormat('!m', $selected_month)->format('F') ?> <?= $selected_year ?></h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="chart-container">
                                                    <canvas id="dailyTrendChart"></canvas>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Data Table -->
                                        <div class="card mt-4">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <h5>Daily Footfall Data</h5>
                                                <div>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="exportToExcel('daily')">
                                                        <i class="bi bi-download"></i> Export to Excel
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div class="table-responsive">
                                                    <table class="table table-striped table-hover" id="dailyFootfallTable">
                                                        <thead class="table-dark">
                                                            <tr>
                                                                <th>Date</th>
                                                                <th>Day</th>
                                                                <th>Footfall Count</th>
                                                                <?php if ($comparison_data): ?>
                                                                    <th>Comparison (<?= DateTime::createFromFormat('!m', $compare_month)->format('F') ?> <?= $compare_year ?>)</th>
                                                                    <th>Difference</th>
                                                                <?php endif; ?>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($final_daily_data as $row): ?>
                                                                <tr>
                                                                    <td><?= htmlspecialchars($row['date']) ?></td>
                                                                    <td><?= htmlspecialchars($row['day']) ?></td>
                                                                    <td><?= $row['main_count'] !== null ? $row['main_count'] : 'N/A' ?></td>
                                                                    <?php if ($comparison_data): ?>
                                                                        <td><?= $row['compare_count'] !== null ? $row['compare_count'] : 'N/A' ?></td>
                                                                        <td class="<?= $row['difference'] > 0 ? 'text-success' : ($row['difference'] < 0 ? 'text-danger' : '') ?>">
                                                                            <?php if ($row['difference'] !== null): ?>
                                                                                <?= $row['difference'] > 0 ? '+' : '' ?><?= $row['difference'] ?>
                                                                            <?php else: ?>
                                                                                N/A
                                                                            <?php endif; ?>
                                                                        </td>
                                                                    <?php endif; ?>
                                                                </tr>
                                                            <?php endforeach; ?>

                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-warning mt-4">
                                            <i class="bi bi-exclamation-triangle"></i> No footfall data found for the selected filters.
                                        </div>
                                    <?php endif; ?>

                                <?php else: ?>
                                    <!-- YEARLY VIEW CONTENT -->
                                    <?php if ($yearly_results): ?>
                                        <!-- Stats Cards -->
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="stats-card bg-light">
                                                    <div class="stats-value text-primary">
                                                        <?= count($yearly_results) ?>
                                                    </div>
                                                    <div class="stats-label">Years Analyzed</div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="stats-card bg-light">
                                                    <div class="stats-value text-success">
                                                        <?= $yearly_results ? round(array_sum(array_column($yearly_results, 'total_present_days')) / count($yearly_results), 0) : 0 ?>
                                                    </div>
                                                    <div class="stats-label">Avg Annual Attendance</div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="stats-card bg-light">
                                                    <div class="stats-value text-info">
                                                        <?= $yearly_results ? max(array_column($yearly_results, 'total_present_days')) : 0 ?>
                                                    </div>
                                                    <div class="stats-label">Highest Year</div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="stats-card bg-light">
                                                    <div class="stats-value text-warning">
                                                        <?= $yearly_results ? min(array_column($yearly_results, 'total_present_days')) : 0 ?>
                                                    </div>
                                                    <div class="stats-label">Lowest Year</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mt-4">
                                            <!-- Monthly Breakdown (Line Graph) -->
                                            <div class="col-md-6">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h5>Monthly Breakdown</h5>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="chart-container">
                                                            <canvas id="monthlyBreakdownChart"></canvas>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Yearly Trend Chart (Bar Graph) -->
                                            <div class="col-md-6">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h5>Yearly Attendance Trend</h5>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="chart-container">
                                                            <canvas id="yearlyTrendChart"></canvas>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Data Table -->
                                        <div class="card mt-4">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <h5>Detailed Yearly Data</h5>
                                                <div>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="exportToExcel('yearly')">
                                                        <i class="bi bi-download"></i> Export to Excel
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div class="table-responsive">
                                                    <table class="table table-striped table-hover" id="yearlyAttendanceTable">
                                                        <thead class="table-dark">
                                                            <tr>
                                                                <th>Year</th>
                                                                <th>Jan</th>
                                                                <th>Feb</th>
                                                                <th>Mar</th>
                                                                <th>Apr</th>
                                                                <th>May</th>
                                                                <th>Jun</th>
                                                                <th>Jul</th>
                                                                <th>Aug</th>
                                                                <th>Sep</th>
                                                                <th>Oct</th>
                                                                <th>Nov</th>
                                                                <th>Dec</th>
                                                                <th>Total</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($yearly_results as $row): ?>
                                                                <tr>
                                                                    <td><strong><?= htmlspecialchars($row['year']) ?></strong></td>
                                                                    <td><?= htmlspecialchars($row['jan']) ?></td>
                                                                    <td><?= htmlspecialchars($row['feb']) ?></td>
                                                                    <td><?= htmlspecialchars($row['mar']) ?></td>
                                                                    <td><?= htmlspecialchars($row['apr']) ?></td>
                                                                    <td><?= htmlspecialchars($row['may']) ?></td>
                                                                    <td><?= htmlspecialchars($row['jun']) ?></td>
                                                                    <td><?= htmlspecialchars($row['jul']) ?></td>
                                                                    <td><?= htmlspecialchars($row['aug']) ?></td>
                                                                    <td><?= htmlspecialchars($row['sep']) ?></td>
                                                                    <td><?= htmlspecialchars($row['oct']) ?></td>
                                                                    <td><?= htmlspecialchars($row['nov']) ?></td>
                                                                    <td><?= htmlspecialchars($row['dec']) ?></td>
                                                                    <td><strong><?= htmlspecialchars($row['total_present_days']) ?></strong></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-warning mt-4">
                                            <i class="bi bi-exclamation-triangle"></i> No yearly data found for the selected filters.
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <!-- Template Main JS File -->
      <script src="../assets_new/js/main.js"></script>
  
    <script>
        // Export to Excel function
        function exportToExcel(mode) {
            const tableId = mode === 'daily' ? 'dailyFootfallTable' : 'yearlyAttendanceTable';
            const table = document.getElementById(tableId);
            const ws = XLSX.utils.table_to_sheet(table);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, mode === 'daily' ? "DailyFootfall" : "YearlyAttendance");
            XLSX.writeFile(wb, `Attendance_${mode === 'daily' ? 'Daily' : 'Yearly'}_Report.xlsx`);
        }

        <?php if ($view_mode === 'daily' && $daily_data): ?>
            // Daily Trend Chart
            const dailyCtx = document.getElementById('dailyTrendChart').getContext('2d');
            const dailyTrendChart = new Chart(dailyCtx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($labels) ?>,
                    datasets: [{
                            label: '<?= DateTime::createFromFormat('!m', $selected_month)->format('F') ?> <?= $selected_year ?>',
                            data: <?= json_encode($main_counts) ?>,
                            borderColor: 'rgba(54, 162, 235, 1)',
                            backgroundColor: 'rgba(54, 162, 235, 0.1)',
                            borderWidth: 2,
                            tension: 0.1,
                            fill: true
                        }
                        <?php if ($comparison_data): ?>,
                            {
                                label: '<?= DateTime::createFromFormat('!m', $compare_month)->format('F') ?> <?= $compare_year ?>',
                                data: <?= json_encode($compare_counts) ?>,
                                borderColor: 'rgba(255, 99, 132, 1)',
                                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                                borderWidth: 2,
                                tension: 0.1,
                                borderDash: [5, 5],
                                fill: true
                            }
                        <?php endif; ?>
                    ]
                },
                options: {
                    responsive: true,
                    spanGaps: true, // Connect points across null values
                    showLine: true, // Always show the line (default)
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.y + ' visitors';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Visitors'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Date'
                            }
                        }
                    }
                }
            });
        <?php elseif ($view_mode === 'yearly' && $yearly_results): ?>
            // Yearly Trend Chart (Bar Graph)
            const yearlyCtx = document.getElementById('yearlyTrendChart').getContext('2d');
            const yearlyTrendChart = new Chart(yearlyCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($yearly_labels) ?>,
                    datasets: [{
                        label: 'Total Attendance Days',
                        data: <?= json_encode($yearly_data) ?>,
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.parsed.y + ' days';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Attendance Days'
                            }
                        }
                    }
                }
            });

            // Monthly Breakdown Chart (Line Graph)
            const monthlyCtx = document.getElementById('monthlyBreakdownChart').getContext('2d');
            const monthlyBreakdownChart = new Chart(monthlyCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [
                        <?php foreach ($yearly_results as $row): ?> {
                                label: '<?= $row['year'] ?>',
                                data: [
                                    <?= $row['jan'] ?>, <?= $row['feb'] ?>, <?= $row['mar'] ?>, <?= $row['apr'] ?>,
                                    <?= $row['may'] ?>, <?= $row['jun'] ?>, <?= $row['jul'] ?>, <?= $row['aug'] ?>,
                                    <?= $row['sep'] ?>, <?= $row['oct'] ?>, <?= $row['nov'] ?>, <?= $row['dec'] ?>
                                ],
                                borderWidth: 2,
                                tension: 0.1,
                                fill: false
                            },
                        <?php endforeach; ?>
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.y + ' days';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Attendance Days'
                            }
                        }
                    }
                }
            });
        <?php endif; ?>

        // Auto-enable comparison month when year is selected
        document.getElementById('compare_year')?.addEventListener('change', function() {
            if (this.value && !document.getElementById('compare_month').value) {
                document.getElementById('compare_month').value = '<?= $selected_month ?>';
            }
        });

        // Validate year range
        document.getElementById('start_year')?.addEventListener('change', function() {
            const endYear = document.getElementById('end_year');
            if (parseInt(this.value) > parseInt(endYear.value)) {
                endYear.value = this.value;
            }
        });

        document.getElementById('end_year')?.addEventListener('change', function() {
            const startYear = document.getElementById('start_year');
            if (parseInt(this.value) < parseInt(startYear.value)) {
                startYear.value = this.value;
            }
        });
    </script>
</body>

</html>