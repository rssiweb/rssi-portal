<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}
validation();

// Start timer for performance monitoring
$startTime = microtime(true);

// Get filter parameters with validation
$status = isset($_GET['status']) ? $_GET['status'] : 'Active';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Initialize filter arrays
$selectedCategories = isset($_GET['categories']) ? (array)$_GET['categories'] : [];
$selectedClasses = isset($_GET['classes']) ? (array)$_GET['classes'] : [];
$selectedStudents = isset($_GET['students']) ? (array)$_GET['students'] : [];

// Get all available categories, classes, and students
$categoriesQuery = "SELECT DISTINCT category FROM rssimyprofile_student WHERE category IS NOT NULL ORDER BY category";
$categoriesResult = pg_query($con, $categoriesQuery);
$allCategories = pg_fetch_all_columns($categoriesResult, 0);

// Get all available classes
$classesQuery = "SELECT DISTINCT class FROM rssimyprofile_student WHERE class IS NOT NULL ORDER BY class";
$classesResult = pg_query($con, $classesQuery);
$allClasses = pg_fetch_all_columns($classesResult, 0);

// Validate selections against available options
$validCategories = array_filter($selectedCategories, fn($cat) => in_array($cat, $allCategories));
$validClasses = array_filter($selectedClasses, fn($cls) => in_array($cls, $allClasses));
$validStudents = [];

if (!empty($selectedStudents)) {
    // Use placeholders for prepared statement
    $placeholders = [];
    for ($i = 0; $i < count($selectedStudents); $i++) {
        $placeholders[] = '$' . ($i + 1);
    }
    $placeholders = implode(',', $placeholders);

    $sql = "SELECT student_id, studentname FROM rssimyprofile_student WHERE student_id IN ($placeholders)";
    $result = pg_query_params($con, $sql, $selectedStudents);
    if ($result) {
        $validStudents = pg_fetch_all($result) ?: [];
    }
}

// Build SQL conditions with proper escaping
$conditions = ["s.filterstatus = '" . pg_escape_string($con, $status) . "'"];

if (!empty($validCategories)) {
    $categoryList = "'" . implode("','", array_map(fn($c) => pg_escape_string($con, $c), $validCategories)) . "'";
    $conditions[] = "s.category IN ($categoryList)";
}

if (!empty($validClasses)) {
    $classList = "'" . implode("','", array_map(fn($c) => pg_escape_string($con, $c), $validClasses)) . "'";
    $conditions[] = "s.class IN ($classList)";
}

if (!empty($validStudents)) {
    $studentIds = array_column($validStudents, 'student_id');
    $studentList = "'" . implode("','", array_map(fn($s) => pg_escape_string($con, $s), $studentIds)) . "'";
    $conditions[] = "s.student_id IN ($studentList)";
}

$whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Main optimized query (same as before)
$query = "
WITH date_range AS (
    SELECT generate_series(
        '$startDate'::date, '$endDate'::date, '1 day'::interval
    )::date AS attendance_date
),
filtered_students AS (
    SELECT student_id, studentname, category, class, doa
    FROM rssimyprofile_student s
    $whereClause
),
student_class_days_filtered AS (
    SELECT 
        fs.student_id,
        fs.studentname,
        fs.category,
        fs.class,
        d.attendance_date,
        TO_CHAR(d.attendance_date, 'YYYY-MM') AS month_year,
        EXISTS (
            SELECT 1 FROM student_class_days cw
            WHERE cw.category = fs.category
              AND cw.effective_from <= d.attendance_date
              AND (cw.effective_to IS NULL OR cw.effective_to >= d.attendance_date)
              AND POSITION(TO_CHAR(d.attendance_date, 'Dy') IN cw.class_days) > 0
        ) AS is_class_day,
        EXISTS (
            SELECT 1 FROM attendance a 
            WHERE a.user_id = fs.student_id 
            AND a.date = d.attendance_date
        ) AS is_present
    FROM date_range d
    JOIN filtered_students fs ON d.attendance_date >= fs.doa
),
attendance_data AS (
    SELECT
        student_id,
        studentname,
        category,
        class,
        month_year,
        attendance_date,
        CASE
            WHEN is_present THEN 'P'
            WHEN is_class_day AND EXISTS (SELECT 1 FROM attendance WHERE date = attendance_date) THEN 'A'
            ELSE NULL
        END AS attendance_status
    FROM student_class_days_filtered
)
SELECT 
    student_id,
    studentname,
    category,
    class,
    month_year,
    COUNT(DISTINCT CASE WHEN attendance_status IS NOT NULL THEN attendance_date END) AS total_classes,
    COUNT(DISTINCT CASE WHEN attendance_status = 'P' THEN attendance_date END) AS attended_classes,
    CASE 
        WHEN COUNT(DISTINCT CASE WHEN attendance_status IS NOT NULL THEN attendance_date END) = 0 THEN NULL
        ELSE ROUND(
            (COUNT(DISTINCT CASE WHEN attendance_status = 'P' THEN attendance_date END) * 100.0) /
            COUNT(DISTINCT CASE WHEN attendance_status IS NOT NULL THEN attendance_date END), 
            2
        )
    END AS attendance_percentage
FROM attendance_data
GROUP BY student_id, studentname, category, class, month_year
ORDER BY studentname, month_year;
";

$result = pg_query($con, $query);
$attendanceData = pg_fetch_all($result) ?: [];

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

// Handle CSV export - THIS IS THE FIXED SECTION
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=attendance_summary_' . date('Y-m-d') . '.csv');

    $output = fopen('php://output', 'w');

    // Write CSV headers
    $headers = ['Sl. No.', 'Student ID', 'Student Name', 'Category', 'Class'];

    // Get unique months from the FILTERED data
    $months = [];
    foreach ($attendanceData as $row) {
        if (!in_array($row['month_year'], $months)) {
            $months[] = $row['month_year'];
        }
    }
    sort($months);

    foreach ($months as $month) {
        $headers[] = date('M Y', strtotime($month . '-01')) . ' Present';
        $headers[] = date('M Y', strtotime($month . '-01')) . ' Total';
        $headers[] = date('M Y', strtotime($month . '-01')) . ' Percentage';
    }
    $headers[] = 'Overall Percentage';

    fputcsv($output, $headers);

    // Group FILTERED data by student
    $students = [];
    foreach ($attendanceData as $row) {
        $studentId = $row['student_id'];
        if (!isset($students[$studentId])) {
            $students[$studentId] = [
                'info' => [
                    'studentname' => $row['studentname'],
                    'category' => $row['category'],
                    'class' => $row['class']
                ],
                'months' => [],
                'total_present' => 0,
                'total_classes' => 0
            ];
        }

        if ($row['month_year']) {
            $students[$studentId]['months'][$row['month_year']] = [
                'present' => $row['attended_classes'],
                'total' => $row['total_classes'],
                'percentage' => $row['attendance_percentage']
            ];
            $students[$studentId]['total_present'] += $row['attended_classes'];
            $students[$studentId]['total_classes'] += $row['total_classes'];
        }
    }

    // Write student rows from FILTERED data
    $slNo = 1;
    foreach ($students as $studentId => $data) {
        $rowData = [
            $slNo++,
            $studentId,
            $data['info']['studentname'],
            $data['info']['category'],
            $data['info']['class']
        ];

        // Add monthly data
        foreach ($months as $month) {
            $monthData = $data['months'][$month] ?? null;
            $rowData[] = $monthData ? $monthData['present'] : '';
            $rowData[] = $monthData ? $monthData['total'] : '';
            $rowData[] = $monthData ? ($monthData['percentage'] . '%') : '';
        }

        // Calculate overall percentage from FILTERED data
        $overallPercentage = $data['total_classes'] > 0
            ? round(($data['total_present'] / $data['total_classes']) * 100, 2)
            : 0;
        $rowData[] = $overallPercentage . '%';

        fputcsv($output, $rowData);
    }

    fclose($output);
    exit;
}

// Log performance metrics
error_log("Attendance summary generated in " . round((microtime(true) - $startTime), 3) . " seconds");
?>

<!doctype html>
<html lang="en">

<head>
    <!-- Your existing head content -->
    <title>Attendance Summary Report</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
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
            <h1>Attendance Summary Report</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item active">Attendance Summary</li>
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
                                    <label>Status</label>
                                    <select name="status" class="form-select">
                                        <option value="Active" <?= $status == 'Active' ? 'selected' : '' ?>>Active</option>
                                        <option value="Inactive" <?= $status == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <label>Start Date</label>
                                    <input type="date" name="start_date" class="form-control" value="<?= $startDate ?>">
                                </div>

                                <div class="col-md-2">
                                    <label>End Date</label>
                                    <input type="date" name="end_date" class="form-control" value="<?= $endDate ?>">
                                </div>

                                <!-- New Class Filter -->
                                <div class="col-md-2">
                                    <label>Class</label>
                                    <select name="classes[]" id="classes" class="form-select" multiple>
                                        <?php foreach ($allClasses as $class): ?>
                                            <option value="<?= htmlspecialchars($class) ?>"
                                                <?= in_array($class, $selectedClasses) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($class) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <label>Categories</label>
                                    <select name="categories[]" id="categories" class="form-select" multiple>
                                        <?php foreach ($allCategories as $category): ?>
                                            <option value="<?= htmlspecialchars($category) ?>"
                                                <?= in_array($category, $selectedCategories) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($category) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label>Students (Optional)</label>
                                    <select name="students[]" id="students" class="form-select" multiple>
                                        <?php foreach ($validStudents as $student): ?>
                                            <option value="<?= htmlspecialchars($student['student_id']) ?>" selected>
                                                <?= htmlspecialchars($student['studentname']) ?> - <?= htmlspecialchars($student['student_id']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary">Generate Report</button>
                                    <button type="submit" name="export" value="csv" class="btn btn-success">
                                        <i class="bi bi-file-earmark-excel"></i> Export CSV
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
                                                // Get unique months in the date range
                                                $months = array_unique(array_column($attendanceData, 'month_year'));
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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
    <script>
        $(document).ready(function() {
            $('#classes, #categories').select2({
                placeholder: "Select...",
                width: '100%'
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            $('#students').select2({
                ajax: {
                    url: 'fetch_students.php',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term // search term
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
                placeholder: 'Search by name or ID',
                width: '100%'
            });
        });
    </script>
</body>

</html>