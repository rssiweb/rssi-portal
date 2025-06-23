<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}
validation();

$startTime = microtime(true);

function makePlaceholders($array)
{
    return implode(',', array_map(fn($i) => '$' . ($i + 1), array_keys($array)));
}

$status = $_GET['status'] ?? 'Active';
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');
$selectedCategories = (array)($_GET['categories'] ?? []);
$selectedClasses = (array)($_GET['classes'] ?? []);
$selectedStudents = (array)($_GET['students'] ?? []);

function validateSelection($con, $table, $column, $values)
{
    if (empty($values)) return [];
    $ph = makePlaceholders($values);
    $sql = "SELECT $column FROM $table WHERE $column IN ($ph)";
    $res = pg_query_params($con, $sql, $values);
    return $res ? array_column(pg_fetch_all($res) ?: [], $column) : [];
}

$validCategories = validateSelection($con, 'school_categories', 'category_value', $selectedCategories);
$validClasses = validateSelection($con, 'school_classes', 'value', $selectedClasses);
$validStudents = [];
if (!empty($selectedStudents)) {
    $ph = makePlaceholders($selectedStudents);
    $sql = "SELECT student_id, studentname FROM rssimyprofile_student WHERE student_id IN ($ph)";
    $res = pg_query_params($con, $sql, $selectedStudents);
    $validStudents = $res ? pg_fetch_all($res) ?: [] : [];
}

$conditions = ["s.filterstatus = '" . pg_escape_string($con, $status) . "'"];
if (!empty($validCategories)) {
    $list = implode("','", array_map(fn($v) => pg_escape_string($con, $v), $validCategories));
    $conditions[] = "s.category IN ('$list')";
}
if (!empty($validClasses)) {
    $list = implode("','", array_map(fn($v) => pg_escape_string($con, $v), $validClasses));
    $conditions[] = "s.class IN ('$list')";
}
if (!empty($validStudents)) {
    $ids = array_column($validStudents, 'student_id');
    $list = implode("','", array_map(fn($v) => pg_escape_string($con, $v), $ids));
    $conditions[] = "s.student_id IN ('$list')";
}
$whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$query = "
WITH date_range AS (
    SELECT generate_series('$startDate'::date, '$endDate'::date, '1 day')::date AS attendance_date
),
filtered_students AS (
    SELECT student_id, studentname, category, class, doa
    FROM rssimyprofile_student s
    $whereClause
),
student_class_days_filtered AS (
    SELECT 
        fs.student_id, fs.studentname, fs.category, fs.class,
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
            WHERE a.user_id = fs.student_id AND a.date = d.attendance_date
        ) AS is_present
    FROM date_range d
    JOIN filtered_students fs ON d.attendance_date >= fs.doa
),
attendance_data AS (
    SELECT
        student_id, studentname, category, class, month_year, attendance_date,
        CASE
            WHEN is_present THEN 'P'
            WHEN is_class_day AND EXISTS (SELECT 1 FROM attendance WHERE date = attendance_date) THEN 'A'
            ELSE NULL
        END AS attendance_status
    FROM student_class_days_filtered
)
SELECT 
    student_id, studentname, category, class, month_year,
    COUNT(DISTINCT CASE WHEN attendance_status IS NOT NULL THEN attendance_date END) AS total_classes,
    COUNT(DISTINCT CASE WHEN attendance_status = 'P' THEN attendance_date END) AS attended_classes,
    CASE 
        WHEN COUNT(DISTINCT CASE WHEN attendance_status IS NOT NULL THEN attendance_date END) = 0 THEN NULL
        ELSE ROUND(
            (COUNT(DISTINCT CASE WHEN attendance_status = 'P' THEN attendance_date END) * 100.0) /
            COUNT(DISTINCT CASE WHEN attendance_status IS NOT NULL THEN attendance_date END), 2
        )
    END AS attendance_percentage
FROM attendance_data
GROUP BY student_id, studentname, category, class, month_year
ORDER BY studentname, month_year;
";

$result = pg_query($con, $query);
$attendanceData = pg_fetch_all($result) ?: [];

$totalPercentage = 0;
$monthCount = 0;
foreach ($attendanceData as $row) {
    if ($row['attendance_percentage'] !== null) {
        $totalPercentage += $row['attendance_percentage'];
        $monthCount++;
    }
}
$averagePercentage = $monthCount > 0 ? round($totalPercentage / $monthCount, 2) : 0;

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=attendance_summary_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');

    $headers = ['Sl. No.', 'Student ID', 'Student Name', 'Category', 'Class'];
    $months = array_unique(array_column($attendanceData, 'month_year'));
    sort($months);

    foreach ($months as $month) {
        $label = date('M Y', strtotime($month . '-01'));
        $headers[] = "$label Present";
        $headers[] = "$label Total";
        $headers[] = "$label Percentage";
    }
    $headers[] = 'Overall Percentage';
    fputcsv($output, $headers);

    $students = [];
    foreach ($attendanceData as $row) {
        $id = $row['student_id'];
        if (!isset($students[$id])) {
            $students[$id] = [
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
        $students[$id]['months'][$row['month_year']] = [
            'present' => $row['attended_classes'],
            'total' => $row['total_classes'],
            'percentage' => $row['attendance_percentage']
        ];
        $students[$id]['total_present'] += $row['attended_classes'];
        $students[$id]['total_classes'] += $row['total_classes'];
    }

    $sl = 1;
    foreach ($students as $id => $data) {
        $row = [
            $sl++,
            $id,
            $data['info']['studentname'],
            $data['info']['category'],
            $data['info']['class']
        ];
        foreach ($months as $m) {
            $month = $data['months'][$m] ?? ['present' => '', 'total' => '', 'percentage' => ''];
            $row[] = $month['present'];
            $row[] = $month['total'];
            $row[] = $month['percentage'] !== '' ? $month['percentage'] . '%' : '';
        }
        $overall = $data['total_classes'] > 0
            ? round(($data['total_present'] / $data['total_classes']) * 100, 2) . '%'
            : '0%';
        $row[] = $overall;
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

error_log("Attendance summary generated in " . round((microtime(true) - $startTime), 3) . " seconds");
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Attendance Summary Report</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
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

                                <div class="col-md-2">
                                    <label>Class</label>
                                    <select name="classes[]" id="classes" class="form-select" multiple>
                                        <?php foreach ($validClasses as $class): ?>
                                            <option value="<?= htmlspecialchars($class) ?>" selected>
                                                <?= htmlspecialchars($class) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <label>Categories</label>
                                    <select name="categories[]" id="categories" class="form-select" multiple>
                                        <?php foreach ($validCategories as $category): ?>
                                            <option value="<?= htmlspecialchars($category) ?>" selected>
                                                <?= htmlspecialchars($category) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-4">
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
                                    <button type="submit" class="btn btn-primary" id="generateBtn">
                                        <span class="spinner-border spinner-border-sm d-none me-1" role="status" aria-hidden="true"></span>
                                        <span class="btn-text">Generate Report</span>
                                    </button>

                                    <button type="submit" name="export" value="csv" class="btn btn-success" id="exportBtn">
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
                minimumInputLength: 1,
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

</body>

</html>