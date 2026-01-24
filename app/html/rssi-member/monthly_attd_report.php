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

// Get filters
$id = $_GET['get_aid'] ?? 'Active';
$month = $_GET['get_month'] ?? date('Y-m');
$selectedCategories = $_GET['categories'] ?? [];

// Date range
$startDate = date("Y-m-01", strtotime($month));
$endDate = date("Y-m-t", strtotime($month));

// Validate selected categories
$validCategories = [];
if (!empty($selectedCategories)) {
    $placeholders = implode(',', array_map(fn($i) => '$' . ($i + 1), array_keys($selectedCategories)));
    $sql = "SELECT category_value FROM school_categories WHERE category_value IN ($placeholders)";
    $result = pg_query_params($con, $sql, $selectedCategories);
    $validCategories = $result ? array_column(pg_fetch_all($result) ?: [], 'category_value') : [];
}

// Build SQL WHERE clause
$conditions = [];

if (!empty($id)) {
    $conditions[] = "s.filterstatus = '" . pg_escape_string($con, $id) . "'";
}

if (!empty($validCategories)) {
    $escaped = array_map(fn($c) => pg_escape_literal($con, $c), $validCategories);
    $conditions[] = "s.category IN (" . implode(',', $escaped) . ")";
}

$whereClause = !empty($conditions) ? ' AND ' . implode(' AND ', $conditions) : '';
$requireCategorySelection = empty($validCategories);

// Main query (same logic)
$query = "
WITH date_range AS (
    SELECT generate_series('$startDate'::date, '$endDate'::date, interval '1 day')::date AS attendance_date
),
holidays AS (
    SELECT holiday_date FROM holidays 
    WHERE holiday_date BETWEEN '$startDate'::date AND '$endDate'::date
),
student_exceptions AS (
    SELECT 
        m.student_id,
        e.exception_date AS attendance_date
    FROM 
        student_class_days_exceptions e
    JOIN 
        student_exception_mapping m ON e.exception_id = m.exception_id
    WHERE 
        e.exception_date BETWEEN '$startDate'::date AND '$endDate'::date
),
attendance_data AS (
    SELECT
        s.student_id,
        s.filterstatus,
        s.studentname,
        s.category,
        s.class,
        s.effectivefrom,
        s.doa,
        d.attendance_date,
        COALESCE(
            CASE
                WHEN a.user_id IS NOT NULL THEN 'P' -- Present if attendance record exists
                WHEN h.holiday_date IS NOT NULL THEN NULL -- NULL for holidays (not counted)
                WHEN ex.attendance_date IS NOT NULL THEN NULL -- NULL for exceptions (not counted)
                WHEN a.user_id IS NULL
                     AND EXISTS (SELECT 1 FROM attendance att WHERE att.date = d.attendance_date)
                     AND EXISTS (
                        SELECT 1 FROM student_class_days cw
                        WHERE cw.category = s.category
                          AND cw.effective_from <= d.attendance_date
                          AND (cw.effective_to IS NULL OR cw.effective_to >= d.attendance_date)
                          AND POSITION(TO_CHAR(d.attendance_date, 'Dy') IN cw.class_days) > 0
                     )
                     AND s.doa <= d.attendance_date
                     THEN 'A' -- Absent only if it's a class day and not holiday/exception
                ELSE NULL -- NULL for non-class days
            END
        ) AS attendance_status
    FROM
        date_range d
    CROSS JOIN
        rssimyprofile_student s
    LEFT JOIN
        attendance a ON s.student_id = a.user_id AND a.date = d.attendance_date
    LEFT JOIN
        holidays h ON d.attendance_date = h.holiday_date
    LEFT JOIN
        student_exceptions ex ON d.attendance_date = ex.attendance_date AND s.student_id = ex.student_id
    WHERE
        (
            s.effectivefrom IS NULL OR 
            DATE_TRUNC('month', s.effectivefrom) = DATE_TRUNC('month', TO_DATE('$month', 'YYYY-MM'))
        )
        AND DATE_TRUNC('month', s.doa) <= DATE_TRUNC('month', TO_DATE('$month', 'YYYY-MM'))
        $whereClause
)
SELECT
    student_id,
    filterstatus,
    studentname,
    category,
    class,
    attendance_date,
    attendance_status,
    COUNT(*) FILTER (WHERE attendance_status IS NOT NULL) OVER (PARTITION BY student_id) AS total_classes,
    COUNT(*) FILTER (WHERE attendance_status = 'P') OVER (PARTITION BY student_id) AS attended_classes,
    CASE
        WHEN COUNT(*) FILTER (WHERE attendance_status IS NOT NULL) OVER (PARTITION BY student_id) = 0 THEN NULL
        ELSE CONCAT(
            ROUND(
                (COUNT(*) FILTER (WHERE attendance_status = 'P') OVER (PARTITION BY student_id) * 100.0) /
                COUNT(*) FILTER (WHERE attendance_status IS NOT NULL) OVER (PARTITION BY student_id), 2
            ),
            '%'
        )
    END AS attendance_percentage
FROM attendance_data
GROUP BY
    student_id,
    filterstatus,
    studentname,
    category,
    class,
    attendance_date,
    attendance_status
ORDER BY
    CASE WHEN class = 'Pre-school' THEN 0 ELSE 1 END,
    category,
    class,
    student_id,
    attendance_date;
";

// Execute query if category is selected
$studentIDCount = null;
if (!$requireCategorySelection) {
    $result = pg_query($con, $query);
    if (!$result) {
        echo "Query failed.";
        exit();
    }
    $attendanceData = pg_fetch_all($result);
    $studentIDCount = count(array_unique(array_column($attendanceData, 'student_id')));
}

pg_close($con);
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php include 'includes/meta.php' ?>

    

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <!-- Initialize the multi-select plugin (using Select2 as example) -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
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
                            <div class="d-flex justify-content-between align-items-center position-absolute top-5 end-0 p-3">
                                <form method="POST" action="export_function.php">
                                    <input type="hidden" value="monthly_attd" name="export_type" />
                                    <input type="hidden" value="<?php echo $id ?>" name="id" />
                                    <input type="hidden" value="<?php echo $month ?>" name="month" />
                                    <!-- Add hidden field for selected categories -->
                                    <?php foreach ($selectedCategories as $cat): ?>
                                        <input type="hidden" name="categories[]" value="<?php echo htmlspecialchars($cat); ?>">
                                    <?php endforeach; ?>

                                    <button type="submit" id="export" name="export" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none;
                                    padding: 0px;
                                    border: none;" title="Export CSV">
                                        <i class="bi bi-file-earmark-excel" style="font-size:large;"></i>
                                    </button>
                                </form>
                            </div>
                            <br>
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    Record count:&nbsp;<?php echo $studentIDCount ?>
                                    <p>To customize the view result, please select a filter value.</p>
                                </div>
                                <!-- HTML Form -->
                                <form action="" method="GET" class="row g-2 align-items-center">
                                    <div class="row">
                                        <div class="col-12 col-sm-2">
                                            <div class="form-group">
                                                <select name="get_aid" id="get_aid" class="form-select" style="display:inline-block" required>
                                                    <?php if ($id == null) { ?>
                                                        <option disabled selected hidden>Select Status</option>
                                                    <?php } else { ?>
                                                        <option hidden selected><?php echo $id ?></option>
                                                    <?php } ?>
                                                    <option>Active</option>
                                                    <option>Inactive</option>
                                                </select>
                                                <small class="form-text text-muted">Select Status</small>
                                            </div>
                                        </div>

                                        <div class="col-12 col-sm-2">
                                            <div class="form-group">
                                                <input type="month" name="get_month" id="get_month" class="form-control" placeholder="Month" value="<?php echo $getMonth = isset($_GET['get_month']) ? htmlspecialchars($_GET['get_month']) : date('Y-m'); ?>">
                                                <small class="form-text text-muted">Select Month</small>
                                            </div>
                                        </div>

                                        <div class="col-12 col-sm-2">
                                            <div class="form-group">
                                                <!-- <label>Categories</label> -->
                                                <select name="categories[]" id="categories" class="form-select" multiple="multiple" required>
                                                    <?php foreach ($validCategories as $category): ?>
                                                        <option value="<?= htmlspecialchars($category) ?>" selected>
                                                            <?= htmlspecialchars($category) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <small class="form-text text-muted">Select one or more categories (required)</small>
                                            </div>
                                        </div>

                                        <div class="col-12 col-sm-2">
                                            <button type="submit" name="search_by_id" class="btn btn-success" style="outline: none;">
                                                <i class="bi bi-search"></i> Search
                                            </button>
                                        </div>
                                    </div>
                                </form>
                                <?php if ($requireCategorySelection): ?>
                                    <div class="alert alert-warning mt-3">
                                        Please select at least one category to view attendance data.
                                    </div>
                                <?php else: ?>
                                    <?php
                                    // Explode the month into year and month components
                                    $components = explode("-", $month);
                                    if (count($components) === 2) {
                                        $year = $components[0];
                                        $monthNumber = $components[1];

                                        // Create a DateTime object using the year and month
                                        $dateTime = new DateTime("$year-$monthNumber-01");
                                    } else {
                                        // Handle the case where $month is not in the expected format
                                        $dateTime = null;
                                    }
                                    ?>
                                    <div class="row align-items-center">
                                        <div class="col-6">
                                            <?php if ($dateTime !== null) : ?>
                                                You are viewing data for
                                                <span class="blink-text">
                                                    <?= $dateTime->format('F Y') ?>
                                                </span>
                                            <?php else : ?>
                                                Invalid month format
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <br>
                                    <br>
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Student ID</th>
                                                    <th>Student Name</th>
                                                    <th>Category</th>
                                                    <th>Class</th>
                                                    <th>Status</th>
                                                    <th>Present</th>
                                                    <th>Total Class</th>
                                                    <th>Percentage</th>

                                                    <?php
                                                    // Generate header row with attendance dates
                                                    $dates = array_unique(array_column($attendanceData, 'attendance_date'));
                                                    foreach ($dates as $date) {
                                                        $formattedDate = date("j", strtotime($date)); // Format the date
                                                        echo "<th>$formattedDate</th>";
                                                    }
                                                    ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                // Process attendance data and fill the table
                                                $currentStudent = null;
                                                foreach ($attendanceData as $row) {
                                                    if ($currentStudent !== $row['student_id']) {
                                                        if ($currentStudent !== null) {
                                                            echo "</tr>";
                                                        }
                                                        echo "<tr>
                                                        <td>{$row['student_id']}</td>
                                                        <td>{$row['studentname']}</td>
                                                        <td>{$row['category']}</td>
                                                        <td>{$row['class']}</td>
                                                        <td>{$row['filterstatus']}</td>
                                                        <td>{$row['attended_classes']}</td>
                                                        <td>{$row['total_classes']}</td>
                                                        <td>{$row['attendance_percentage']}</td>";
                                                        $currentStudent = $row['student_id'];
                                                    }
                                                    echo "<td>{$row['attendance_status']}</td>";
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                            </div>
                        </div>
                    </div>
                </div>
        </section>
    <?php endif; ?>
    </main>
    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

    <!-- Template Main JS File -->
      <script src="../assets_new/js/main.js"></script>
  

    <!-- Initialize Select2 AFTER all scripts are loaded -->
    <script>
        $(document).ready(function() {
            // Initialize Select2
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

            // Your other main.js functionality can go here
            // or keep it in main.js if it's properly structured
        });
    </script>
</body>

</html>