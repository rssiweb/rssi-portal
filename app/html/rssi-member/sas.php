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

// Initialize all variables used in the form
$status = $_GET['status'] ?? 'Active';
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');
$selectedCategories = (array)($_GET['categories'] ?? []);
$selectedClasses = (array)($_GET['classes'] ?? []);
$selectedStudents = (array)($_GET['students'] ?? []);

// Initialize variables
$hasFilters = false;
$attendanceData = [];
$averagePercentage = 0;
$message = "Please select filters to view attendance data";

// Only process if at least one filter is set
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty(array_filter($_GET, function ($v, $k) {
    $defaults = ['status' => 'Active', 'start_date' => date('Y-m-01'), 'end_date' => date('Y-m-t')];
    return !(array_key_exists($k, $defaults) && $v == $defaults[$k]);
}, ARRAY_FILTER_USE_BOTH))) {

    $hasFilters = true;
    $startTime = microtime(true);

    // Build WHERE conditions for student filtering
    $conditions = ["s.filterstatus = '" . pg_escape_string($con, $status) . "'"];
    $params = [];
    $paramCount = 1;

    if (!empty($selectedCategories)) {
        $placeholders = implode(',', array_fill(0, count($selectedCategories), '$' . $paramCount));
        $params = array_merge($params, $selectedCategories);
        $conditions[] = "s.category IN ($placeholders)";
        $paramCount += count($selectedCategories);
    }

    if (!empty($selectedClasses)) {
        $placeholders = implode(',', array_fill(0, count($selectedClasses), '$' . $paramCount));
        $params = array_merge($params, $selectedClasses);
        $conditions[] = "s.class IN ($placeholders)";
        $paramCount += count($selectedClasses);
    }

    if (!empty($selectedStudents)) {
        $placeholders = implode(',', array_fill(0, count($selectedStudents), '$' . $paramCount));
        $params = array_merge($params, $selectedStudents);
        $conditions[] = "s.student_id IN ($placeholders)";
        $paramCount += count($selectedStudents);
    }

    $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

    // Main query - OPTIMIZED VERSION
    if (!empty($conditions)) {
        $query = "
        WITH filtered_students AS (
            SELECT student_id, studentname, category, class, doa
            FROM rssimyprofile_student s
            $whereClause
        ),
        -- Get all relevant dates once
        relevant_dates AS (
            SELECT DISTINCT a.date AS attendance_date
            FROM attendance a
            JOIN filtered_students fs ON a.user_id = fs.student_id
            WHERE a.date BETWEEN $1 AND $2
            UNION
            SELECT holiday_date AS attendance_date
            FROM holidays
            WHERE holiday_date BETWEEN $1 AND $2
            UNION
            SELECT e.exception_date AS attendance_date
            FROM student_class_days_exceptions e
            JOIN student_exception_mapping m ON e.exception_id = m.exception_id
            JOIN filtered_students fs ON m.student_id = fs.student_id
            WHERE e.exception_date BETWEEN $1 AND $2
        ),
        -- Get holidays and exceptions as arrays
        holidays_array AS (
            SELECT array_agg(holiday_date) AS dates
            FROM holidays
            WHERE holiday_date BETWEEN $1 AND $2
        ),
        student_exceptions_array AS (
            SELECT 
                m.student_id,
                array_agg(DISTINCT e.exception_date) AS exception_dates
            FROM student_class_days_exceptions e
            JOIN student_exception_mapping m ON e.exception_id = m.exception_id
            WHERE e.exception_date BETWEEN $1 AND $2
            GROUP BY m.student_id
        ),
        -- Get class days configuration
        class_days_config AS (
            SELECT 
                category,
                effective_from,
                effective_to,
                class_days
            FROM student_class_days
            WHERE effective_to >= $1 OR effective_to IS NULL
        ),
        -- Get attendance records
        student_attendance AS (
            SELECT 
                fs.student_id,
                a.date AS attendance_date,
                COUNT(*) as present_count
            FROM filtered_students fs
            JOIN attendance a ON fs.student_id = a.user_id
            WHERE a.date BETWEEN $1 AND $2
            GROUP BY fs.student_id, a.date
        )
        SELECT 
            fs.student_id,
            fs.studentname,
            fs.category,
            fs.class,
            TO_CHAR(d.attendance_date, 'YYYY-MM') AS month_year,
            -- Check if it's a class day
            CASE 
                WHEN EXISTS (
                    SELECT 1 FROM class_days_config c
                    WHERE c.category = fs.category
                    AND c.effective_from <= d.attendance_date
                    AND (c.effective_to IS NULL OR c.effective_to >= d.attendance_date)
                    AND POSITION(TO_CHAR(d.attendance_date, 'Dy') IN c.class_days) > 0
                ) THEN 1
                ELSE 0
            END AS is_class_day,
            -- Check attendance
            CASE 
                WHEN sa.present_count > 0 THEN 'P'
                WHEN ha.dates IS NOT NULL AND d.attendance_date = ANY(ha.dates) THEN NULL
                WHEN sea.exception_dates IS NOT NULL AND d.attendance_date = ANY(sea.exception_dates) THEN NULL
                WHEN EXISTS (
                    SELECT 1 FROM class_days_config c
                    WHERE c.category = fs.category
                    AND c.effective_from <= d.attendance_date
                    AND (c.effective_to IS NULL OR c.effective_to >= d.attendance_date)
                    AND POSITION(TO_CHAR(d.attendance_date, 'Dy') IN c.class_days) > 0
                ) THEN 'A'
                ELSE NULL
            END AS attendance_status
        FROM filtered_students fs
        CROSS JOIN relevant_dates d
        LEFT JOIN holidays_array ha ON 1=1
        LEFT JOIN student_exceptions_array sea ON sea.student_id = fs.student_id
        LEFT JOIN student_attendance sa ON sa.student_id = fs.student_id AND sa.attendance_date = d.attendance_date
        WHERE d.attendance_date >= fs.doa
        ORDER BY fs.student_id, d.attendance_date
        ";

        // Add start and end dates to parameters
        array_unshift($params, $endDate, $startDate);

        $result = pg_query_params($con, $query, $params);

        if ($result) {
            $rawData = pg_fetch_all($result) ?: [];

            // Process data in PHP instead of complex SQL
            $groupedData = [];
            foreach ($rawData as $row) {
                $key = $row['student_id'] . '|' . $row['month_year'];

                if (!isset($groupedData[$key])) {
                    $groupedData[$key] = [
                        'student_id' => $row['student_id'],
                        'studentname' => $row['studentname'],
                        'category' => $row['category'],
                        'class' => $row['class'],
                        'month_year' => $row['month_year'],
                        'total_classes' => 0,
                        'attended_classes' => 0
                    ];
                }

                if ($row['attendance_status'] !== null) {
                    $groupedData[$key]['total_classes']++;
                    if ($row['attendance_status'] === 'P') {
                        $groupedData[$key]['attended_classes']++;
                    }
                }
            }

            // Calculate percentages
            foreach ($groupedData as &$data) {
                $data['attendance_percentage'] = $data['total_classes'] > 0
                    ? round(($data['attended_classes'] * 100.0) / $data['total_classes'], 2)
                    : null;
            }

            $attendanceData = array_values($groupedData);

            // Calculate average percentage
            $totalPercentage = 0;
            $monthCount = 0;
            foreach ($attendanceData as $row) {
                if ($row['attendance_percentage'] !== null) {
                    $totalPercentage += $row['attendance_percentage'];
                    $monthCount++;
                }
            }
            $averagePercentage = $monthCount > 0 ? round($totalPercentage / $monthCount, 2) : 0;

            echo "<script>console.log('Attendance summary generated in " . round((microtime(true) - $startTime), 3) . " seconds');</script>";
        }
    }
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Attendance Summary</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
    <!-- Include Date Range Picker CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.css">
    <style>
        .summary-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .table-responsive {
            overflow-x: auto;
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>SAS</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Attendance Portal</a></li>
                    <li class="breadcrumb-item"><a href="attendx.php">AttendX</a></li>
                    <li class="breadcrumb-item active">SAS</li>
                </ol>
            </nav>
        </div>

        <section class="section dashboard">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <br>
                            <form action="" method="GET" class="row g-3 top-5">
                                <div class="col-md-2">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="Active" <?= $status == 'Active' ? 'selected' : '' ?>>Active</option>
                                        <option value="Inactive" <?= $status == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label">Date Range</label>
                                    <input type="text" name="date_range" class="form-control date-range-picker"
                                        placeholder="Select date range"
                                        value="<?= !empty($startDate) && !empty($endDate) ? htmlspecialchars("$startDate - $endDate") : '' ?>">
                                    <input type="hidden" name="start_date" value="<?= htmlspecialchars($startDate) ?>">
                                    <input type="hidden" name="end_date" value="<?= htmlspecialchars($endDate) ?>">
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label">Class</label>
                                    <select name="classes[]" id="classes" class="form-select" multiple>
                                        <?php foreach ($validClasses as $class): ?>
                                            <option value="<?= htmlspecialchars($class) ?>" selected>
                                                <?= htmlspecialchars($class) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label">Categories</label>
                                    <select name="categories[]" id="categories" class="form-select" multiple>
                                        <?php foreach ($validCategories as $category): ?>
                                            <option value="<?= htmlspecialchars($category) ?>" selected>
                                                <?= htmlspecialchars($category) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Students (Optional)</label>
                                    <select name="students[]" id="students" class="form-select" multiple>
                                        <?php foreach ($validStudents as $student): ?>
                                            <option value="<?= htmlspecialchars($student['student_id']) ?>" selected>
                                                <?= htmlspecialchars($student['studentname']) ?> - <?= htmlspecialchars($student['student_id']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-12 mt-3">
                                    <button type="submit" class="btn btn-primary" id="generateBtn">
                                        <span class="spinner-border spinner-border-sm d-none me-1" role="status" aria-hidden="true"></span>
                                        <span class="btn-text">Generate Report</span>
                                    </button>

                                    <button type="submit" name="export" value="csv" class="btn btn-outline-success ms-2" id="exportBtn">
                                        <span class="spinner-border spinner-border-sm d-none me-1" role="status" aria-hidden="true"></span>
                                        <span class="btn-text">
                                            <i class="bi bi-file-earmark-excel"></i> Export CSV
                                        </span>
                                    </button>
                                </div>
                            </form>

                            <?php if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['status'])): ?>
                                <div class="summary-card mt-4">
                                    <h5>Report Summary</h5>
                                    <p>Date Range: <?= date('M j, Y', strtotime($startDate)) ?> to <?= date('M j, Y', strtotime($endDate)) ?></p>
                                    <p>Status: <?= $status ?></p>
                                    <p>Average Attendance Percentage: <?= $averagePercentage ?>%</p>
                                </div>

                                <div class="table-responsive mt-4">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Sl. No.</th>
                                                <th>Student ID</th>
                                                <th>Student Name</th>
                                                <th>Category</th>
                                                <th>Class</th>
                                                <?php
                                                // Get unique months in the date range and SORT THEM PROPERLY
                                                $months = array_unique(array_column($attendanceData, 'month_year'));
                                                usort($months, function ($a, $b) {
                                                    return strtotime($a . '-01') <=> strtotime($b . '-01');
                                                });

                                                foreach ($months as $month) {
                                                    echo "<th>" . date('M Y', strtotime($month . '-01')) . "<br>Present/Total</th>";
                                                }
                                                ?>
                                                <th>Overall Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $students = [];
                                            foreach ($attendanceData as $row) {
                                                $students[$row['student_id']]['info'] = [
                                                    'studentname' => $row['studentname'],
                                                    'category' => $row['category'],
                                                    'class' => $row['class']
                                                ];
                                                $students[$row['student_id']]['months'][$row['month_year']] = [
                                                    'present' => $row['attended_classes'],
                                                    'total' => $row['total_classes'],
                                                    'percentage' => $row['attendance_percentage']
                                                ];
                                            }

                                            $slNo = 1;
                                            foreach ($students as $studentId => $data):
                                                // Calculate overall stats
                                                $totalPresent = 0;
                                                $totalClasses = 0;
                                                foreach ($data['months'] as $month) {
                                                    $totalPresent += $month['present'];
                                                    $totalClasses += $month['total'];
                                                }
                                                $overallPercentage = $totalClasses > 0 ? round(($totalPresent / $totalClasses) * 100, 2) : 0;
                                            ?>
                                                <tr>
                                                    <td><?= $slNo++ ?></td>
                                                    <td><?= htmlspecialchars($studentId) ?></td>
                                                    <td><?= htmlspecialchars($data['info']['studentname']) ?></td>
                                                    <td><?= htmlspecialchars($data['info']['category']) ?></td>
                                                    <td><?= htmlspecialchars($data['info']['class']) ?></td>
                                                    <?php foreach ($months as $month):
                                                        $monthData = $data['months'][$month] ?? null;
                                                    ?>
                                                        <td>
                                                            <?php if ($monthData): ?>
                                                                <?= $monthData['present'] ?>/<?= $monthData['total'] ?>
                                                                (<?= $monthData['percentage'] ?>%)
                                                            <?php else: ?>
                                                                -
                                                            <?php endif; ?>
                                                        </td>
                                                    <?php endforeach; ?>
                                                    <td><?= $overallPercentage ?>%</td>
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
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.min.js"></script>
    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>


    <script>
        $(document).ready(function() {
            $('#students').select2({
                ajax: {
                    url: 'fetch_students.php',
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
                minimumInputLength: 2,
                placeholder: 'Search by name or ID',
                width: '100%'
            });

            $('#categories').select2({
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
                minimumInputLength: 1,
                placeholder: 'Search by category',
                width: '100%'
            });

            $('#classes').select2({
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
                minimumInputLength: 1,
                placeholder: 'Search by class',
                width: '100%'
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            $('.date-range-picker').daterangepicker({
                opens: 'right',
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Clear',
                    format: 'YYYY-MM-DD'
                }
            });

            $('.date-range-picker').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
                $('input[name="start_date"]').val(picker.startDate.format('YYYY-MM-DD'));
                $('input[name="end_date"]').val(picker.endDate.format('YYYY-MM-DD'));
            });

            $('.date-range-picker').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
                $('input[name="start_date"]').val('');
                $('input[name="end_date"]').val('');
            });
        });
    </script>

</body>

</html>