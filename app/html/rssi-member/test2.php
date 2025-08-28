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

// Set date range based on academic year if selected
if ($filter_type == 'academic_year' && $academic_year) {
    $years = explode('-', $academic_year);
    $start_date = $years[0] . '-04-01';
    $end_date = $years[1] . '-03-31';
}

// Get dashboard statistics
$stats_query = "
    SELECT 
        COUNT(DISTINCT so.distributed_to) AS total_beneficiaries,
        SUM(so.quantity_distributed) AS total_quantity,
        COUNT(DISTINCT so.date) AS distribution_days
    FROM stock_out so
    JOIN stock_item si ON so.item_distributed = si.item_id
    WHERE so.date BETWEEN '$start_date' AND '$end_date'
      AND so.distributed_to IS NOT NULL
      AND si.is_ration = true
";

$stats_result = pg_query($con, $stats_query);
$stats = pg_fetch_assoc($stats_result);

// Get distribution dates
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
    GROUP BY so.date
    ORDER BY so.date DESC
";

$dates_result = pg_query($con, $dates_query);

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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        body {
            background-color: var(--body-bg);
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: #333;
            line-height: 1.6;
        }

        .navbar-brand {
            font-weight: 700;
            color: white;
        }

        .page-header {
            padding: 1.5rem 0;
            margin-bottom: 1.5rem;
            background: linear-gradient(120deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 0 0 12px 12px;
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
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-utensils me-2"></i>Ration Distribution Portal
            </a>
        </div>
    </nav>

    <div class="page-header">
        <div class="container">
            <h1 class="display-6 fw-bold">Distribution Analytics</h1>
            <p class="lead">Track and analyze ration distribution data</p>
        </div>
    </div>

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
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number"><?= $stats['total_beneficiaries'] ?></div>
                    <div class="stat-title">Total Beneficiaries</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-weight-hanging"></i>
                    </div>
                    <div class="stat-number"><?= $stats['total_quantity'] ?></div>
                    <div class="stat-title">Total Quantity Distributed</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-number"><?= $stats['distribution_days'] ?></div>
                    <div class="stat-title">Distribution Days</div>
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
                    <?php $index = 0;
                    while ($row = pg_fetch_assoc($dates_result)): $index++; ?>
                        <div class="distribution-date" data-bs-toggle="collapse" data-bs-target="#distribution<?= $index ?>">
                            <div>
                                <strong><?= date('d M, Y', strtotime($row['date'])) ?></strong>
                                <div class="text-muted small"><?= $row['beneficiaries_count'] ?> beneficiaries, <?= $row['total_quantity'] ?> units distributed</div>
                            </div>
                            <div class="text-end">
                                <span class="text-muted small">Click to view details</span>
                                <i class="fas fa-chevron-down ms-2"></i>
                            </div>
                        </div>
                        <div class="collapse mb-3" id="distribution<?= $index ?>">
                            <div class="distribution-details">
                                <h6 class="mb-2">Items Distributed:</h6>
                                <p class="mb-0"><?= $row['items'] ?></p>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
</body>

</html>