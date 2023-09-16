<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];

    header("Location: index.php");
    exit;
}
if ($password_updated_by == null || $password_updated_on < $default_pass_updated_on) {

    echo '<script type="text/javascript">';
    echo 'window.location.href = "defaultpasswordreset.php";';
    echo '</script>';
}

if ($role != 'Admin' && $role != 'Offline Manager') {
    echo '<script type="text/javascript">';
    echo 'alert("Access Denied. You are not authorized to access this web page.");';
    echo 'window.location.href = "home.php";';
    echo '</script>';
}
?>
<?php
$id = isset($_GET['get_aid']) ? strtoupper($_GET['get_aid']) : null;
$month = isset($_GET['get_month']) ? $_GET['get_month'] : date('Y-m');

// Calculate the start and end dates of the month
$startDate = date("Y-m-01", strtotime($month));
$endDate = date("Y-m-t", strtotime($month));

$idCondition = "";
if ($id != null) {
    $idCondition = "AND s.student_id = '$id'";
}

$query = "WITH date_range AS (
    SELECT generate_series(
        '$startDate'::date, '$endDate'::date, '1 day'::interval
    )::date AS attendance_date
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
                WHEN a.user_id IS NOT NULL THEN 'P'
                WHEN a.user_id IS NULL AND d.attendance_date NOT IN (SELECT date FROM attendance) THEN NULL
                WHEN TO_DATE(s.doa, 'DD/MM/YYYY') > d.attendance_date THEN NULL
                ELSE 'A'
            END
        ) AS attendance_status
    FROM
        date_range d
    CROSS JOIN
        rssimyprofile_student s
    LEFT JOIN
        attendance a
        ON s.student_id = a.user_id AND a.date = d.attendance_date
    WHERE
        (
        s.effectivefrom = '' OR
        DATE_TRUNC('month', TO_DATE(s.effectivefrom, 'DD/MM/YYYY'))::DATE = DATE_TRUNC('month', TO_DATE('$month', 'YYYY-MM'))::DATE
        )
        AND DATE_TRUNC('month', TO_DATE(s.doa, 'DD/MM/YYYY'))::DATE <= DATE_TRUNC('month', TO_DATE('$month', 'YYYY-MM'))::DATE
        AND s.category != 'LG4'
        $idCondition
)
SELECT
    student_id,
    filterstatus,
    studentname,
    category,
    class,
    attendance_date,
    attendance_status,
    COUNT(*) FILTER (WHERE attendance_status != '') OVER (PARTITION BY student_id) AS total_classes,
    COUNT(*) FILTER (WHERE attendance_status = 'P') OVER (PARTITION BY student_id) AS attended_classes,
    CASE
        WHEN COUNT(*) FILTER (WHERE attendance_status != '') OVER (PARTITION BY student_id) = 0 THEN NULL
        ELSE CONCAT(
            ROUND(
                (COUNT(*) FILTER (WHERE attendance_status = 'P') OVER (PARTITION BY student_id) * 100.0) /
                COUNT(*) FILTER (WHERE attendance_status != '') OVER (PARTITION BY student_id), 2
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

$result = pg_query($con, $query);

if (!$result) {
    echo "Query failed.";
    exit();
}

// Fetch attendance data
$attendanceData = pg_fetch_all($result);
$uniqueStudentIDs = array_unique(array_column($attendanceData, 'student_id'));
$studentIDCount = count($uniqueStudentIDs);

// Close the connection
pg_close($con); ?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Attendance Report</title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
    <!-- Glow Cookies v3.0.1 -->
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            //facebookPixel: '',
            policyLink: 'https://www.rssi.in/disclaimer'
        });
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>

    <!-- Include jQuery UI CSS and JavaScript -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

    <style>
        .blink-text {
            color: red;
            animation: blinkAnimation 1s infinite;
        }

        @keyframes blinkAnimation {

            0%,
            50% {
                opacity: 0;
            }

            100% {
                opacity: 1;
            }
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Attendance Report</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Class details</a></li>
                    <li class="breadcrumb-item"><a href="attendx.php">AttendX</a></li>
                    <li class="breadcrumb-item active">Attendance Report</li>
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
                            <div class="d-flex justify-content-between align-items-center position-absolute top-5 end-0 p-3">
                                <form method="POST" action="export_function.php">
                                    <input type="hidden" value="monthly_attd" name="export_type" />
                                    <input type="hidden" value="<?php echo $id ?>" name="id" />
                                    <input type="hidden" value="<?php echo $month ?>" name="month" />

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
                                <form action="" method="GET" class="row g-2 align-items-center">
                                    <div class="row">
                                        <div class="col-12 col-sm-2">
                                            <div class="form-group">
                                                <input type="text" name="get_aid" id="get_aid" class="form-control" placeholder="User Id" value="<?php echo isset($_GET['get_aid']) ? htmlspecialchars($_GET['get_aid']) : ''; ?>">
                                                <small class="form-text text-muted">Enter User Id</small>
                                            </div>
                                        </div>

                                        <div class="col-12 col-sm-2">
                                            <div class="form-group">
                                                <input type="text" name="get_month" id="get_month" class="form-control" placeholder="Month" value="<?php echo $getMonth = isset($_GET['get_month']) ? htmlspecialchars($_GET['get_month']) : date('Y-m'); ?>">
                                                <small class="form-text text-muted">Select Month</small>
                                            </div>
                                        </div>
                                        <script>
                                            $(function() {
                                                $("#get_month").datepicker({
                                                    dateFormat: "yy-mm", // Format to show in the input
                                                    changeMonth: true,
                                                    changeYear: true,
                                                    showButtonPanel: true,
                                                    onClose: function(dateText, inst) {
                                                        var year = $("#ui-datepicker-div .ui-datepicker-year :selected").val();
                                                        var month = $("#ui-datepicker-div .ui-datepicker-month :selected").val();
                                                        $(this).val(year + '-' + (parseInt(month) + 1)); // Adjust month by adding 1
                                                    }
                                                });
                                            });
                                        </script>



                                        <div class="col-12 col-sm-2">
                                            <button type="submit" name="search_by_id" class="btn btn-success" style="outline: none;">
                                                <i class="bi bi-search"></i> Search
                                            </button>
                                        </div>
                                    </div>

                                </form>
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
    </main>
    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

</body>

</html>