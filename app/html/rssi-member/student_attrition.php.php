<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

// Function to get count of active students as of today
function getActiveStudentsToday($filters = [])
{
    global $con;

    $where_conditions = ["s.filterstatus = 'Active'", "s.doa <= CURRENT_DATE"];

    if (!empty($filters['class']) && $filters['class'] != 'all') {
        $classes = is_array($filters['class']) ? $filters['class'] : [$filters['class']];
        $class_list = "'" . implode("','", array_map(function ($c) use ($con) {
            return pg_escape_string($con, $c);
        }, $classes)) . "'";
        $where_conditions[] = "s.class IN ($class_list)";
    }

    if (!empty($filters['category']) && $filters['category'] != 'all') {
        $categories = is_array($filters['category']) ? $filters['category'] : [$filters['category']];
        $category_list = "'" . implode("','", array_map(function ($c) use ($con) {
            return pg_escape_string($con, $c);
        }, $categories)) . "'";
        $where_conditions[] = "s.category IN ($category_list)";
    }

    if (!empty($filters['student_id']) && $filters['student_id'] != 'all') {
        $student_ids = is_array($filters['student_id']) ? $filters['student_id'] : [$filters['student_id']];
        $student_id_list = "'" . implode("','", array_map(function ($c) use ($con) {
            return pg_escape_string($con, $c);
        }, $student_ids)) . "'";
        $where_conditions[] = "s.student_id IN ($student_id_list)";
    }

    $where_clause = implode(' AND ', $where_conditions);

    $query = "SELECT COUNT(*) as count FROM rssimyprofile_student s WHERE $where_clause";
    $result = pg_query($con, $query);
    $row = pg_fetch_assoc($result);

    return $row['count'];
}

// Function to get count of active students at start of period
function getActiveStudentsAtStart($start_date, $filters = [])
{
    global $con;

    $where_conditions = [
        "s.doa <= '$start_date'",
        "(s.effectivefrom IS NULL OR s.effectivefrom > '$start_date')"
    ];

    if (!empty($filters['class']) && $filters['class'] != 'all') {
        $classes = is_array($filters['class']) ? $filters['class'] : [$filters['class']];
        $class_list = "'" . implode("','", array_map(function ($c) use ($con) {
            return pg_escape_string($con, $c);
        }, $classes)) . "'";
        $where_conditions[] = "s.class IN ($class_list)";
    }

    if (!empty($filters['category']) && $filters['category'] != 'all') {
        $categories = is_array($filters['category']) ? $filters['category'] : [$filters['category']];
        $category_list = "'" . implode("','", array_map(function ($c) use ($con) {
            return pg_escape_string($con, $c);
        }, $categories)) . "'";
        $where_conditions[] = "s.category IN ($category_list)";
    }

    if (!empty($filters['student_id']) && $filters['student_id'] != 'all') {
        $student_ids = is_array($filters['student_id']) ? $filters['student_id'] : [$filters['student_id']];
        $student_id_list = "'" . implode("','", array_map(function ($c) use ($con) {
            return pg_escape_string($con, $c);
        }, $student_ids)) . "'";
        $where_conditions[] = "s.student_id IN ($student_id_list)";
    }

    $where_clause = implode(' AND ', $where_conditions);

    $query = "SELECT COUNT(*) as count FROM rssimyprofile_student s WHERE $where_clause";
    $result = pg_query($con, $query);
    $row = pg_fetch_assoc($result);

    return $row['count'];
}

// Function to get students who left during the period
function getStudentsLeftDuringPeriod($start_date, $end_date, $filters = [])
{
    global $con;

    $where_conditions = ["s.filterstatus = 'Inactive'", "s.effectivefrom BETWEEN '$start_date' AND '$end_date'"];

    if (!empty($filters['class']) && $filters['class'] != 'all') {
        $classes = is_array($filters['class']) ? $filters['class'] : [$filters['class']];
        $class_list = "'" . implode("','", array_map(function ($c) use ($con) {
            return pg_escape_string($con, $c);
        }, $classes)) . "'";
        $where_conditions[] = "s.class IN ($class_list)";
    }

    if (!empty($filters['category']) && $filters['category'] != 'all') {
        $categories = is_array($filters['category']) ? $filters['category'] : [$filters['category']];
        $category_list = "'" . implode("','", array_map(function ($c) use ($con) {
            return pg_escape_string($con, $c);
        }, $categories)) . "'";
        $where_conditions[] = "s.category IN ($category_list)";
    }

    if (!empty($filters['student_id']) && $filters['student_id'] != 'all') {
        $student_ids = is_array($filters['student_id']) ? $filters['student_id'] : [$filters['student_id']];
        $student_id_list = "'" . implode("','", array_map(function ($c) use ($con) {
            return pg_escape_string($con, $c);
        }, $student_ids)) . "'";
        $where_conditions[] = "s.student_id IN ($student_id_list)";
    }

    $where_clause = implode(' AND ', $where_conditions);

    $query = "SELECT COUNT(*) as count FROM rssimyprofile_student s WHERE $where_clause";
    $result = pg_query($con, $query);
    $row = pg_fetch_assoc($result);

    return $row['count'];
}

// Function to get students data with filters
function getStudentsData($filters = [])
{
    global $con;

    // Default date range (current month)
    $start_date = date('Y-m-01');
    $end_date = date('Y-m-d');

    // Apply filters
    if (!empty($filters['start_date'])) {
        $start_date = $filters['start_date'];
    }
    if (!empty($filters['end_date'])) {
        $end_date = $filters['end_date'];
    }

    $where_conditions = ["s.effectivefrom BETWEEN '$start_date' AND '$end_date'"];

    if (!empty($filters['class']) && $filters['class'] != 'all') {
        $classes = is_array($filters['class']) ? $filters['class'] : [$filters['class']];
        $class_list = "'" . implode("','", array_map(function ($c) use ($con) {
            return pg_escape_string($con, $c);
        }, $classes)) . "'";
        $where_conditions[] = "s.class IN ($class_list)";
    }

    if (!empty($filters['category']) && $filters['category'] != 'all') {
        $categories = is_array($filters['category']) ? $filters['category'] : [$filters['category']];
        $category_list = "'" . implode("','", array_map(function ($c) use ($con) {
            return pg_escape_string($con, $c);
        }, $categories)) . "'";
        $where_conditions[] = "s.category IN ($category_list)";
    }

    if (!empty($filters['student_id']) && $filters['student_id'] != 'all') {
        $student_ids = is_array($filters['student_id']) ? $filters['student_id'] : [$filters['student_id']];
        $student_id_list = "'" . implode("','", array_map(function ($c) use ($con) {
            return pg_escape_string($con, $c);
        }, $student_ids)) . "'";
        $where_conditions[] = "s.student_id IN ($student_id_list)";
    }

    $where_clause = implode(' AND ', $where_conditions);

    $query = "
        SELECT 
            s.student_id,
            s.studentname,
            s.category,
            s.class,
            s.filterstatus,
            s.effectivefrom,
            s.doa,
            s.gender,
            s.effectivefrom,
            s.schooladmissionrequired,
            MAX(a.punch_in) as last_attended_date,
            (MAX(a.punch_in)::date - s.doa::date) as total_duration_days
        FROM rssimyprofile_student s
        LEFT JOIN attendance a ON a.user_id = s.student_id
        WHERE $where_clause
        GROUP BY s.student_id, s.studentname, s.category, s.class, s.filterstatus, 
                 s.effectivefrom, s.doa, s.gender, s.effectivefrom, s.schooladmissionrequired
        ORDER BY s.effectivefrom DESC
    ";

    $result = pg_query($con, $query);
    $students = [];

    while ($row = pg_fetch_assoc($result)) {
        $students[] = $row;
    }

    return $students;
}

// Function to calculate attrition metrics
function calculateAttritionMetrics($students, $students_at_start, $students_left, $students_today)
{
    $metrics = [
        'students_today' => $students_today,
        'students_at_start' => $students_at_start,
        'students_left' => $students_left,
        'male_inactive' => 0,
        'female_inactive' => 0,
        'binary_inactive' => 0,
        'attrition_rate' => 0
    ];

    foreach ($students as $student) {
        if ($student['filterstatus'] === 'Inactive') {
            if ($student['gender'] === 'Male') {
                $metrics['male_inactive']++;
            } elseif ($student['gender'] === 'Female') {
                $metrics['female_inactive']++;
            } elseif ($student['gender'] === 'Binary') {
                $metrics['binary_inactive']++;
            }
        }
    }

    // Calculate attrition rate using the correct formula
    if ($metrics['students_at_start'] > 0) {
        $metrics['attrition_rate'] = round(($metrics['students_left'] / $metrics['students_at_start']) * 100, 2);
    }

    return $metrics;
}

// Get filter values
$filters = [
    'start_date' => $_GET['start_date'] ?? date('Y-m-01'),
    'end_date' => $_GET['end_date'] ?? date('Y-m-d'),
    'class' => $_GET['class'] ?? 'all',
    'category' => $_GET['category'] ?? 'all',
    'student_id' => $_GET['student_id'] ?? 'all'
];

// Get the data for correct attrition calculation
$students_today = getActiveStudentsToday($filters);
$students_at_start = getActiveStudentsAtStart($filters['start_date'], $filters);
$students_left = getStudentsLeftDuringPeriod($filters['start_date'], $filters['end_date'], $filters);
$students = getStudentsData($filters);
$metrics = calculateAttritionMetrics($students, $students_at_start, $students_left, $students_today);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Attrition Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
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

        body {
            background-color: #f8f9fc;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
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

        .bg-primary {
            background: linear-gradient(45deg, var(--primary), #2e59d9);
        }

        .bg-success {
            background: linear-gradient(45deg, var(--success), #17a673);
        }

        .bg-danger {
            background: linear-gradient(45deg, var(--danger), #be2617);
        }

        .bg-warning {
            background: linear-gradient(45deg, var(--warning), #dda20a);
        }

        .table th {
            border-top: none;
            font-weight: 600;
            color: var(--dark);
        }

        .badge {
            font-weight: 500;
            padding: 0.5em 0.8em;
        }

        h1 {
            color: var(--dark);
            font-weight: 700;
            margin-bottom: 1.5rem;
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

        .select2-container--default .select2-selection--multiple {
            border: 1px solid #d1d3e2;
            border-radius: 0.35rem;
            min-height: calc(1.5em + 0.75rem + 2px);
        }

        .filter-btn {
            min-width: 120px;
        }
    </style>
</head>

<body>
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-graduation-cap me-2"></i>Student Attrition Dashboard</h1>
            <div class="d-flex">
                <button class="btn btn-outline-secondary me-2" onclick="window.print()">
                    <i class="fas fa-print me-1"></i> Print Report
                </button>
                <button class="btn btn-outline-primary">
                    <i class="fas fa-download me-1"></i> Export Data
                </button>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="filter-section">
            <form method="GET" id="filterForm">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">Date Range</label>
                        <input type="date" class="form-control" name="start_date"
                            value="<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">To</label>
                        <input type="date" class="form-control" name="end_date"
                            value="<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-bold">Class</label>
                        <select class="form-select class-select" name="class[]" multiple>
                            <?php
                            $class_query = "SELECT DISTINCT class FROM rssimyprofile_student ORDER BY class";
                            $class_result = pg_query($con, $class_query);
                            while ($class = pg_fetch_assoc($class_result)) {
                                $selected = (isset($_GET['class']) && in_array($class['class'], (array)$_GET['class'])) ? 'selected' : '';
                                echo "<option value='{$class['class']}' $selected>{$class['class']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-bold">Category</label>
                        <select class="form-select category-select" name="category[]" multiple>
                            <?php
                            $category_query = "SELECT DISTINCT category FROM rssimyprofile_student ORDER BY category";
                            $category_result = pg_query($con, $category_query);
                            while ($category = pg_fetch_assoc($category_result)) {
                                $selected = (isset($_GET['category']) && in_array($category['category'], (array)$_GET['category'])) ? 'selected' : '';
                                echo "<option value='{$category['category']}' $selected>{$category['category']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-bold">Student ID</label>
                        <select class="form-select student-select" name="student_id[]" multiple>
                            <?php
                            $student_query = "SELECT student_id, studentname FROM rssimyprofile_student ORDER BY student_id";
                            $student_result = pg_query($con, $student_query);
                            while ($student = pg_fetch_assoc($student_result)) {
                                $selected = (isset($_GET['student_id']) && in_array($student['student_id'], (array)$_GET['student_id'])) ? 'selected' : '';
                                echo "<option value='{$student['student_id']}' $selected>{$student['student_id']} - {$student['studentname']}</option>";
                            }
                            ?>
                        </select>
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

        <!-- Metrics Cards -->
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="metric-card bg-primary">
                    <div class="metric-value"><?php echo $metrics['students_today']; ?></div>
                    <div class="metric-label">Total Students (Today)</div>
                    <i class="fas fa-users"></i>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="metric-card bg-success">
                    <div class="metric-value"><?php echo $metrics['students_at_start']; ?></div>
                    <div class="metric-label">Active at Period Start</div>
                    <i class="fas fa-user-check"></i>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="metric-card bg-danger">
                    <div class="metric-value"><?php echo $metrics['students_left']; ?></div>
                    <div class="metric-label">Students Left</div>
                    <i class="fas fa-user-times"></i>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="metric-card bg-warning">
                    <div class="metric-value"><?php echo $metrics['attrition_rate']; ?>%</div>
                    <div class="metric-label">Attrition Rate</div>
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row mt-4">
            <div class="col-lg-6">
                <div class="dashboard-card">
                    <h4 class="mb-4"><i class="fas fa-venus-mars me-2"></i>Gender Distribution in Attrition</h4>
                    <div class="chart-container">
                        <canvas id="genderChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="dashboard-card">
                    <h4 class="mb-4"><i class="fas fa-chart-pie me-2"></i>Attrition Overview</h4>
                    <div class="chart-container">
                        <canvas id="attritionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Students Table -->
        <div class="dashboard-card mt-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0"><i class="fas fa-list me-2"></i>Student Details</h4>
                <span class="badge bg-primary"><?php echo count($students); ?> records found</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Class</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Gender</th>
                            <th>DOA</th>
                            <th>Last Attended</th>
                            <th>Duration (Days)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($students) > 0): ?>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><span class="fw-bold"><?php echo htmlspecialchars($student['student_id']); ?></span></td>
                                    <td><?php echo htmlspecialchars($student['studentname']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($student['class']); ?></span></td>
                                    <td><?php echo htmlspecialchars($student['category']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $student['filterstatus'] === 'Active' ? 'success' : 'danger'; ?>">
                                            <?php echo htmlspecialchars($student['filterstatus']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $student['gender'] === 'Male' ? 'info' : 'warning'; ?>">
                                            <?php echo htmlspecialchars($student['gender']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($student['doa']); ?></td>
                                    <td><?php echo htmlspecialchars($student['last_attended_date'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="fw-bold"><?php echo htmlspecialchars($student['total_duration_days'] ?? 'N/A'); ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-4">
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize all Select2 dropdowns
            $('.class-select').select2({
                placeholder: "Select Class",
                allowClear: true,
                width: '100%'
            });

            $('.category-select').select2({
                placeholder: "Select Category",
                allowClear: true,
                width: '100%'
            });

            $('.student-select').select2({
                placeholder: "Select Student ID",
                allowClear: true,
                width: '100%'
            });

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
                                    position: 'bottom'
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
                            labels: ['Active at Start', 'Students Left'],
                            datasets: [{
                                data: [
                                    <?php echo $metrics['students_at_start']; ?>,
                                    <?php echo $metrics['students_left']; ?>
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
                                    position: 'bottom'
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
</body>

</html>