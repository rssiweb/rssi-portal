<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];

    header("Location: index.php");
    exit;
}
validation();

if ($role == 'Admin') {
    // Fetching the data and populating the $teachers array
    $query = "SELECT associatenumber, fullname FROM rssimyaccount_members WHERE filterstatus = 'Active' AND COALESCE(substring(class FROM '^[^-]+'), NULL)='Offline'";
    $result = pg_query($con, $query);

    if (!$result) {
        die("Error in SQL query: " . pg_last_error());
    }

    $teachers = array();
    while ($row = pg_fetch_assoc($result)) {
        $teachers[] = $row;
    }

    // Free resultset
    pg_free_result($result);
}

// Get filter values from GET parameters
$id = isset($_GET['get_aid']) ? $_GET['get_aid'] : 'Active';

$selectedTeachers = isset($_GET['teacher_id_viva']) ? $_GET['teacher_id_viva'] : [];
?>
<?php
$month = isset($_GET['get_month']) ? $_GET['get_month'] : date('Y-m');

// Calculate the start and end dates of the month
$startDate = date("Y-m-01", strtotime($month));
$endDate = date("Y-m-t", strtotime($month));

// Construct the ID condition
$idCondition = $id != null ? "AND m.filterstatus = '" . pg_escape_string($con, $id) . "'" : '';

// Construct the teacher condition
$teacherCondition = '';
if (!empty($selectedTeachers)) {
    $escapedTeachers = array_map(function ($teacher) use ($con) {
        return pg_escape_string($con, $teacher);
    }, $selectedTeachers);
    $teacherList = implode("','", $escapedTeachers);
    $teacherCondition = "AND m.associatenumber IN ('$teacherList')";
}

$query = "
WITH date_range AS (
    SELECT generate_series(
        '$startDate'::date,
        '$endDate'::date,
        '1 day'::interval
    ) AS attendance_date
),
PunchInOut AS (
    SELECT
        a.user_id,
        a.status,
        DATE_TRUNC('day', a.punch_in) AS punch_date,
        MIN(a.punch_in) AS punch_in,
        CASE
            WHEN COUNT(*) = 1 THEN NULL
            ELSE MAX(a.punch_in)
        END AS punch_out
    FROM attendance a
    GROUP BY a.user_id, a.status, DATE_TRUNC('day', a.punch_in)
),
attendance_data AS (
    SELECT
        m.associatenumber,
        m.filterstatus,
        m.fullname,
        m.engagement,
        COALESCE(substring(m.class FROM '^[^-]+'), NULL) AS mode,
        m.effectivedate,
        m.doj,
        d.attendance_date,
        
        -- Override punch_in if missed-entry exception exists and is approved
        COALESCE(
            (
                SELECT e.start_date_time
                FROM exception_requests e
                WHERE e.submitted_by = m.associatenumber
                AND e.status = 'Approved'
                AND e.exception_type = 'entry'
                AND e.sub_exception_type = 'missed-entry'
                AND d.attendance_date = DATE(e.start_date_time)
                LIMIT 1
            ),
            p.punch_in -- fallback to original punch_in if no exception
        ) AS punch_in,

        -- Handle punch_out logic similarly (using exception if available)
        COALESCE(
            (
                SELECT e.end_date_time
                FROM exception_requests e
                WHERE e.submitted_by = m.associatenumber
                AND e.status = 'Approved'
                AND e.exception_type = 'exit'
                AND d.attendance_date = DATE(e.end_date_time)
                LIMIT 1
            ),
            p.punch_out
        ) AS punch_out,

        -- Attendance status logic
        CASE
            WHEN p.punch_in IS NOT NULL THEN 'P'
            WHEN p.punch_in IS NULL AND d.attendance_date NOT IN (SELECT date FROM attendance) THEN NULL
            WHEN m.doj > d.attendance_date THEN NULL
            ELSE 'A'
        END AS attendance_status,

        s.reporting_time,

        -- Updated Late status logic based on the overridden punch_in
        CASE
        -- Leave condition
            WHEN EXISTS (
                SELECT 1
                FROM leavedb_leavedb l
                WHERE l.applicantid = m.associatenumber
                AND l.status = 'Approved'
                AND l.halfday = 0
                AND d.attendance_date BETWEEN l.fromdate AND l.todate
            ) THEN 'Leave'
            
            -- Half-day condition
            WHEN EXISTS (
            SELECT 1
            FROM leavedb_leavedb l
            WHERE l.applicantid = m.associatenumber
            AND l.status = 'Approved'
            AND l.halfday = 1
            AND d.attendance_date BETWEEN l.fromdate AND l.todate
            GROUP BY l.applicantid, d.attendance_date
            HAVING COUNT(*) >= 2
            ) THEN 'Leave'
            
            -- Half-day condition
            WHEN EXISTS (
                SELECT 1
                FROM leavedb_leavedb l
                WHERE l.applicantid = m.associatenumber
                AND l.status = 'Approved'
                AND l.halfday = 1
                AND d.attendance_date BETWEEN l.fromdate AND l.todate
            ) THEN 'HF'
             -- Late status logic for entry exception with late-entry subcategory
            WHEN EXISTS (
                SELECT 1
                FROM exception_requests e
                WHERE e.submitted_by = m.associatenumber
                AND e.status = 'Approved'
                AND e.exception_type = 'entry'
                AND e.sub_exception_type = 'late-entry'
                AND d.attendance_date = DATE(e.start_date_time)
            ) THEN
                CASE
                    -- If punch_in is within the approved exception time
                    WHEN p.punch_in IS NOT NULL AND EXTRACT(EPOCH FROM p.punch_in::time) <= EXTRACT(EPOCH FROM (
                        SELECT e.start_date_time 
                        FROM exception_requests e 
                        WHERE e.submitted_by = m.associatenumber
                        AND e.status = 'Approved'
                        AND e.exception_type = 'entry'
                        AND e.sub_exception_type = 'late-entry'
                        AND d.attendance_date = DATE(e.start_date_time)
                    )::time) THEN 'Exc.'
                    -- If punch_in is after the approved exception time
                    WHEN p.punch_in IS NOT NULL THEN 'Exc.L'
                    ELSE NULL
                END
            -- If missed-entry exception is applied, recalculate the status
            WHEN EXISTS (
                SELECT 1
                FROM exception_requests e
                WHERE e.submitted_by = m.associatenumber
                AND e.status = 'Approved'
                AND e.exception_type = 'entry'
                AND e.sub_exception_type = 'missed-entry'
                AND d.attendance_date = DATE(e.start_date_time)
            ) THEN
                CASE
                    -- If the overridden punch_in is late (after reporting time + 10 mins), it should be 'L'
                    WHEN EXTRACT(EPOCH FROM COALESCE(
                        (
                            SELECT e.start_date_time
                            FROM exception_requests e
                            WHERE e.submitted_by = m.associatenumber
                            AND e.status = 'Approved'
                            AND e.exception_type = 'entry'
                            AND e.sub_exception_type = 'missed-entry'
                            AND d.attendance_date = DATE(e.start_date_time)
                            LIMIT 1
                        ), p.punch_in)::time) > EXTRACT(EPOCH FROM s.reporting_time) + 600 THEN 'L'
                    -- If the overridden punch_in is within 10 mins of reporting time, it should be 'W'
                    WHEN EXTRACT(EPOCH FROM COALESCE(
                        (
                            SELECT e.start_date_time
                            FROM exception_requests e
                            WHERE e.submitted_by = m.associatenumber
                            AND e.status = 'Approved'
                            AND e.exception_type = 'entry'
                            AND e.sub_exception_type = 'missed-entry'
                            AND d.attendance_date = DATE(e.start_date_time)
                            LIMIT 1
                        ), p.punch_in)::time) > EXTRACT(EPOCH FROM s.reporting_time)
                        AND EXTRACT(EPOCH FROM COALESCE(
                            (
                                SELECT e.start_date_time
                                FROM exception_requests e
                                WHERE e.submitted_by = m.associatenumber
                                AND e.status = 'Approved'
                                AND e.exception_type = 'entry'
                                AND e.sub_exception_type = 'missed-entry'
                                AND d.attendance_date = DATE(e.start_date_time)
                                LIMIT 1
                            ), p.punch_in)::time) <= EXTRACT(EPOCH FROM s.reporting_time) + 600 THEN 'W'
                    -- If it's on time (or earlier), status should be NULL (not late)
                    ELSE NULL
                END
            -- For regular punch-ins, apply standard lateness logic
            WHEN p.punch_in IS NOT NULL THEN
                CASE
                    WHEN EXTRACT(EPOCH FROM p.punch_in::time) > EXTRACT(EPOCH FROM s.reporting_time + INTERVAL '1 minute')
                        AND EXTRACT(EPOCH FROM p.punch_in::time) <= EXTRACT(EPOCH FROM s.reporting_time + INTERVAL '1 minute') + 600 THEN 'W'
                    WHEN EXTRACT(EPOCH FROM p.punch_in::time) > EXTRACT(EPOCH FROM s.reporting_time) + 600 THEN 'L'
                    ELSE NULL
                END
            ELSE NULL
        END AS late_status,

        -- Exit status logic remains unchanged
        CASE
            WHEN p.punch_out IS NULL AND EXISTS (
                SELECT 1
                FROM exception_requests e
                WHERE e.submitted_by = m.associatenumber
                AND e.status = 'Approved'
                AND e.exception_type = 'exit'
                AND d.attendance_date = DATE(e.end_date_time)
            ) THEN 'Exc.'
            ELSE NULL
        END AS exit_status,

        -- Status 'Exc.' for overridden punch-in time from exception
        CASE
            WHEN EXISTS (
                SELECT 1
                FROM exception_requests e
                WHERE e.submitted_by = m.associatenumber
                AND e.status = 'Approved'
                AND e.exception_type = 'entry'
                AND e.sub_exception_type = 'missed-entry'
                AND d.attendance_date = DATE(e.start_date_time)
            ) THEN 'Exc.'
            ELSE NULL
        END AS exception_status
    FROM
        date_range d
    CROSS JOIN
        rssimyaccount_members m
    LEFT JOIN
        PunchInOut p
        ON m.associatenumber = p.user_id AND p.punch_date = DATE_TRUNC('day', d.attendance_date)
    LEFT JOIN
        associate_schedule s
        ON m.associatenumber = s.associate_number
        AND d.attendance_date BETWEEN s.start_date AND s.end_date
    WHERE
        (
            (m.filterstatus = 'Active')
            OR DATE_TRUNC('month', m.effectivedate)::DATE = DATE_TRUNC('month', TO_DATE('$month', 'YYYY-MM'))::DATE
        )
        AND DATE_TRUNC('month', m.doj)::DATE <= DATE_TRUNC('month', TO_DATE('$month', 'YYYY-MM'))::DATE
        $idCondition
        $teacherCondition
        " . ($role !== 'Admin' ? "AND m.associatenumber = '$associatenumber'" : "") . "
)
SELECT
    associatenumber,
    filterstatus,
    fullname,
    engagement,
    mode,
    attendance_date,
    attendance_status,
    punch_in,
    punch_out,
    reporting_time,
    late_status,
    exit_status,
    exception_status,  -- Include the exception status in the final result
    COUNT(*) FILTER (WHERE attendance_status = 'P') OVER (PARTITION BY associatenumber) AS attended_classes
FROM attendance_data
WHERE mode = 'Offline'
GROUP BY
    associatenumber,
    filterstatus,
    fullname,
    engagement,
    mode,
    attendance_date,
    attendance_status,
    punch_in,
    punch_out,
    reporting_time,
    late_status,
    exit_status,
    exception_status
ORDER BY
    associatenumber,
    attendance_date;
";

$result = pg_query($con, $query);

if (!$result) {
    echo "Query failed.";
    exit();
}

// Fetch attendance data
$attendanceData = pg_fetch_all($result);
$uniqueAssociateNumbers = array_unique(array_column($attendanceData, 'associatenumber'));
$associateNumberCount = count($uniqueAssociateNumbers);

// Close the connection
pg_close($con);
?>



<!doctype html>
<html lang="en">

<head>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=AW-11316670180"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'AW-11316670180');
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Attendance Report</title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
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
    <?php include 'inactive_session_expire_check.php'; ?>
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
                            <div
                                class="d-flex justify-content-between align-items-center position-absolute top-5 end-0 p-3">
                                <form method="POST" action="export_function.php">
                                    <input type="hidden" value="monthly_attd_associate" name="export_type" />
                                    <input type="hidden" value="<?php echo $id ?>" name="id" />
                                    <input type="hidden" value="<?php echo $month ?>" name="month" />
                                    <input type="hidden" value="<?php echo $associatenumber ?>"
                                        name="associateNumber" />
                                    <input type="hidden" value="<?php echo $role ?>" name="role" />
                                    <input type="hidden" value="<?php echo implode(',', $selectedTeachers) ?>"
                                        name="selectedTeachers" />

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
                                    Record count:&nbsp;<?php echo $associateNumberCount ?>
                                    <p>To customize the view result, please select a filter value.</p>
                                </div>
                                <form action="" method="GET" class="row g-2 align-items-center">
                                    <div class="row">
                                        <?php if ($role == 'Admin') { ?>
                                            <div class="col-12 col-sm-2">
                                                <div class="form-group">
                                                    <select name="get_aid" id="get_aid" class="form-select"
                                                        style="display:inline-block" required>
                                                        <?php if ($id == null) { ?>
                                                            <option value="" disabled selected hidden>Select Status</option>
                                                        <?php
                                                        } else { ?>
                                                            <option hidden selected><?php echo $id ?></option>
                                                        <?php }
                                                        ?>
                                                        <option>Active</option>
                                                        <option>Inactive</option>
                                                    </select>
                                                    <small class="form-text text-muted">Select Status</small>
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <select class="form-select" id="teacher_id_viva" name="teacher_id_viva[]"
                                                    multiple>
                                                    <option value="" disabled hidden>Select Teacher's ID</option>
                                                    <?php foreach ($teachers as $teacher) { ?>
                                                        <option value="<?php echo $teacher['associatenumber']; ?>" <?php echo (isset($_GET['teacher_id_viva']) && in_array($teacher['associatenumber'], $_GET['teacher_id_viva'])) ? 'selected' : ''; ?>>
                                                            <?php echo $teacher['associatenumber'] . ' - ' . $teacher['fullname']; ?>
                                                        </option>
                                                    <?php } ?>
                                                </select>
                                                <small class="form-text text-muted">Teacher ID</small>
                                            </div>
                                        <?php } ?>

                                        <div class="col-12 col-sm-2">
                                            <div class="form-group">
                                                <input type="text" name="get_month" id="get_month" class="form-control"
                                                    placeholder="Month"
                                                    value="<?php echo $getMonth = isset($_GET['get_month']) ? htmlspecialchars($_GET['get_month']) : date('Y-m'); ?>">
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
                                            <button type="submit" name="search_by_id" class="btn btn-success"
                                                style="outline: none;">
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
                                        <?php if ($dateTime !== null): ?>
                                            You are viewing data for
                                            <span class="blink-text">
                                                <?= $dateTime->format('F Y') ?>
                                            </span>
                                        <?php else: ?>
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
                                                <th>Associate number</th>
                                                <th>Name</th>
                                                <th>Category</th>
                                                <th>Status</th>
                                                <th>Present</th>
                                                <!--<th>Total Class</th>
                                                <th>Percentage</th> -->

                                                <?php
                                                // Generate header row with attendance dates
                                                $dates = array_unique(array_column($attendanceData, 'attendance_date'));
                                                foreach ($dates as $date) {
                                                    $formattedDate = date("j", strtotime($date)); // Format the date
                                                    echo "<th>$formattedDate (In)</th><th>$formattedDate (Out)</th>";
                                                }
                                                ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Process attendance data and fill the table
                                            $currentStudent = null;
                                            foreach ($attendanceData as $row) {
                                                if ($currentStudent !== $row['associatenumber']) {
                                                    if ($currentStudent !== null) {
                                                        echo "</tr>";
                                                    }
                                                    echo "<tr>
                                                            <td>{$row['associatenumber']}</td>
                                                            <td>{$row['fullname']}</td>
                                                            <td>{$row['engagement']}</td>
                                                            <td>{$row['filterstatus']}</td>
                                                            <td>{$row['attended_classes']}</td>";
                                                    $currentStudent = $row['associatenumber'];
                                                }
                                                // Convert punch in and punch out to time format
                                                $punchIn = $row['punch_in'] ? date("h:i A", strtotime($row['punch_in'])) : '';
                                                $punchOut = $row['punch_out'] && $row['punch_out'] ? date("h:i A", strtotime($row['punch_out'])) : '';

                                                echo "<td>" . $punchIn . ($row['late_status'] ? " (" . $row['late_status'] . ")" : "") . ($row['exception_status'] ? " (" . $row['exception_status'] . ")" : "") . "</td><td>" . $punchOut . ($row['exit_status'] ? " (" . $row['exit_status'] . ")" : "") . "</td>";
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
    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i
            class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4"
        crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

</body>

</html>