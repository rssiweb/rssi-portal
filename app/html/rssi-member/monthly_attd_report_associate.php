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
    // Get pre-selected teachers from GET
    $selectedTeachers = $_GET['teacher_id_viva'] ?? [];

    // Only fetch data for selected teachers (for preloading into Select2)
    $teachers = [];

    if (!empty($selectedTeachers)) {
        $placeholders = implode(',', array_map(
            fn($i) => '$' . ($i + 1),
            array_keys($selectedTeachers)
        ));

        $query = "
            SELECT associatenumber, fullname 
            FROM rssimyaccount_members 
            WHERE associatenumber IN ($placeholders) 
              AND filterstatus = 'Active' 
              -- AND engagement IN ('Employee', 'Intern') OR position IN ('Intern')
        ";

        $result = pg_query_params($con, $query, $selectedTeachers);

        if (!$result) {
            die("Error in SQL query: " . pg_last_error());
        }

        while ($row = pg_fetch_assoc($result)) {
            $teachers[] = $row;
        }

        pg_free_result($result);
    }
}

// Get filter values from GET parameters
$id = isset($_GET['get_aid']) ? $_GET['get_aid'] : 'Active';

$selectedTeachers = isset($_GET['teacher_id_viva']) ? $_GET['teacher_id_viva'] : [];
$engagementFilter = isset($_GET['engagement']) ? $_GET['engagement'] : '';
?>
<?php
// $month = isset($_GET['get_month']) ? $_GET['get_month'] : date('Y-m');

// Check if month parameter is set, if not, set to empty
$month = isset($_GET['get_month']) ? $_GET['get_month'] : '';

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

// NEW: Construct the engagement condition
$engagementCondition = '';
if (!empty($engagementFilter)) {
    $engagementCondition = "AND m.engagement = '" . pg_escape_string($con, $engagementFilter) . "'";
}

// Only execute the query if month is selected
$showData = !empty($month);
if ($showData) {

    $query = "
WITH date_range AS (
    SELECT generate_series(
        '$startDate'::date,
        '$endDate'::date,
        '1 day'::interval
    )::date AS attendance_date
),

-- 1. Filter members ONCE
members AS (
    SELECT
        m.associatenumber,
        m.filterstatus,
        m.fullname,
        m.engagement,
        m.position,
        COALESCE(substring(m.class FROM '^[^-]+'), NULL) AS mode,
        m.effectivedate,
        m.doj
    FROM rssimyaccount_members m
    WHERE (m.filterstatus = 'Active'
           OR (m.filterstatus = 'Inactive'
               AND DATE_TRUNC('month', m.effectivedate)::date
                   >= DATE_TRUNC('month', TO_DATE('$month','YYYY-MM'))::date))
      AND DATE_TRUNC('month', m.doj)::date
          <= DATE_TRUNC('month', TO_DATE('$month','YYYY-MM'))::date
      $idCondition
      $teacherCondition
      $engagementCondition
      " . ($role !== 'Admin' ? "AND m.associatenumber = '$associatenumber'" : "") . "
),

-- 2. Pre-aggregate punch in/out per user per day (no status split)
punch_agg AS (
    SELECT
        a.user_id,
        DATE_TRUNC('day', a.punch_in)::date AS punch_date,
        MIN(a.punch_in) AS punch_in,
        CASE WHEN COUNT(*) = 1 THEN NULL ELSE MAX(a.punch_in) END AS punch_out
    FROM attendance a
    WHERE a.punch_in >= '$startDate'::date
      AND a.punch_in <  ('$endDate'::date + INTERVAL '1 day')
    GROUP BY a.user_id, DATE_TRUNC('day', a.punch_in)::date
),

-- 3. Pre-aggregate exceptions ONCE
entry_exc AS (
    SELECT
        e.submitted_by,
        DATE(e.start_date_time) AS exc_date,
        MIN(e.start_date_time) FILTER (WHERE e.sub_exception_type = 'missed-entry') AS missed_entry_time,
        MIN(e.start_date_time) FILTER (WHERE e.sub_exception_type = 'late-entry')   AS late_entry_time
    FROM exception_requests e
    WHERE e.status = 'Approved'
      AND e.exception_type = 'entry'
      AND e.start_date_time >= '$startDate'::date
      AND e.start_date_time <  ('$endDate'::date + INTERVAL '1 day')
    GROUP BY e.submitted_by, DATE(e.start_date_time)
),
exit_exc AS (
    SELECT
        e.submitted_by,
        DATE(e.end_date_time) AS exc_date,
        MIN(e.end_date_time) AS exit_time
    FROM exception_requests e
    WHERE e.status = 'Approved'
      AND e.exception_type = 'exit'
      AND e.end_date_time >= '$startDate'::date
      AND e.end_date_time <  ('$endDate'::date + INTERVAL '1 day')
    GROUP BY e.submitted_by, DATE(e.end_date_time)
),

-- 4. Pre-aggregate leaves ONCE per (applicant, date, halfday)
leave_agg AS (
    SELECT
        l.applicantid,
        d.attendance_date,
        MAX(CASE WHEN l.halfday = 0 THEN 1 ELSE 0 END) AS has_full_leave,
        MAX(CASE WHEN l.halfday = 1 THEN 1 ELSE 0 END) AS halfday_count
    FROM leavedb_leavedb l
    JOIN date_range d
      ON d.attendance_date BETWEEN l.fromdate AND l.todate
    WHERE l.status = 'Approved'
    GROUP BY l.applicantid, d.attendance_date
),

-- 5. Schedule lookup (single pass, uses end_date if available)
sched AS (
    SELECT DISTINCT ON (s.associate_number, s.workday, s.start_date)
        s.associate_number,
        s.workday,
        s.start_date,
        s.reporting_time,
        s.exit_time
    FROM associate_schedule_v2 s
    ORDER BY s.associate_number, s.workday, s.start_date DESC
),

-- 6. Base grid: members × date_range (small × days) joined to schedule & punches
base AS (
    SELECT
        m.associatenumber,
        m.filterstatus,
        m.fullname,
        m.engagement,
        m.position,
        m.mode,
        m.effectivedate,
        m.doj,
        d.attendance_date,
        p.punch_in,
        p.punch_out,
        sc.reporting_time,
        sc.exit_time,
        ee.missed_entry_time,
        ee.late_entry_time,
        xe.exit_time AS exc_exit_time,
        COALESCE(la.has_full_leave, 0)  AS has_full_leave,
        COALESCE(la.halfday_count, 0)   AS halfday_count
    FROM members m
    CROSS JOIN date_range d
    LEFT JOIN punch_agg p
           ON p.user_id = m.associatenumber
          AND p.punch_date = d.attendance_date
    LEFT JOIN sched sc
           ON sc.associate_number = m.associatenumber
          AND sc.workday = CASE EXTRACT(DOW FROM d.attendance_date)
                              WHEN 1 THEN 'Mon' WHEN 2 THEN 'Tue' WHEN 3 THEN 'Wed'
                              WHEN 4 THEN 'Thu' WHEN 5 THEN 'Fri' WHEN 6 THEN 'Sat'
                              WHEN 0 THEN 'Sun' END
          AND sc.start_date <= d.attendance_date
    LEFT JOIN entry_exc ee
           ON ee.submitted_by = m.associatenumber
          AND ee.exc_date = d.attendance_date
    LEFT JOIN exit_exc xe
           ON xe.submitted_by = m.associatenumber
          AND xe.exc_date = d.attendance_date
        LEFT JOIN leave_agg la
           ON la.applicantid = m.associatenumber
          AND la.attendance_date = d.attendance_date
),

-- 7. Apply all the CASE logic in one place
final AS (
    SELECT
        b.*,
        COALESCE(b.missed_entry_time, b.punch_in)   AS eff_punch_in,
        COALESCE(b.exc_exit_time,     b.punch_out)  AS eff_punch_out,

        CASE
            WHEN b.punch_in IS NOT NULL THEN 'P'
            WHEN b.doj > b.attendance_date THEN NULL
            ELSE 'A'
        END AS attendance_status,

        CASE
            WHEN b.has_full_leave = 1 THEN 'Leave'
            WHEN b.halfday_count >= 2 THEN 'Leave'
            WHEN b.halfday_count = 1  THEN 'HF'
            WHEN b.late_entry_time IS NOT NULL THEN
                CASE
                    WHEN b.punch_in IS NOT NULL
                     AND b.punch_in::time <= b.late_entry_time::time THEN 'Exc.'
                    WHEN b.punch_in IS NOT NULL THEN 'Exc.L'
                    ELSE NULL
                END
            WHEN b.missed_entry_time IS NOT NULL THEN
                CASE
                    WHEN b.missed_entry_time::time
                         > b.reporting_time + INTERVAL '10 minutes' THEN 'L'
                    WHEN b.missed_entry_time::time > b.reporting_time
                         AND b.missed_entry_time::time
                             <= b.reporting_time + INTERVAL '10 minutes' THEN 'W'
                    ELSE NULL
                END
            WHEN b.punch_in IS NOT NULL THEN
                CASE
                    WHEN b.reporting_time IS NULL THEN 'NA'
                    WHEN b.punch_in::time > b.reporting_time + INTERVAL '1 minute'
                     AND b.punch_in::time <= b.reporting_time + INTERVAL '11 minutes' THEN 'W'
                    WHEN b.punch_in::time > b.reporting_time + INTERVAL '10 minutes' THEN 'L'
                    ELSE NULL
                END
            ELSE NULL
        END AS late_status,

        CASE
            WHEN b.exc_exit_time IS NOT NULL THEN 'Exc.'
            WHEN b.punch_out IS NOT NULL
             AND b.exit_time IS NOT NULL
             AND b.punch_out::time < b.exit_time
             AND b.halfday_count = 0 THEN 'EE'
            ELSE NULL
        END AS exit_status,

        CASE
            WHEN b.missed_entry_time IS NOT NULL THEN
                CASE WHEN b.reporting_time IS NULL THEN 'Exc.NA' ELSE 'Exc.' END
            ELSE NULL
        END AS exception_status
    FROM base b
)

SELECT
    associatenumber,
    filterstatus,
    fullname,
    engagement,
    position,
    mode,
    attendance_date,
    attendance_status,
    eff_punch_in AS punch_in,
    eff_punch_out AS punch_out,
    reporting_time,
    late_status,
    exit_status,
    exception_status,
    COUNT(*) FILTER (WHERE attendance_status = 'P')
        OVER (PARTITION BY associatenumber) AS attended_classes
FROM final
ORDER BY associatenumber, attendance_date;
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
} else {
    // Set empty data when no month is selected
    $attendanceData = [];
    $uniqueAssociateNumbers = [];
    $associateNumberCount = 0;
}


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
    <?php include 'includes/meta.php' ?>



    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css?v=1.1.0" rel="stylesheet">

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
    <!-- Include Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Include Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize Select2 for associate numbers
            $('#teacher_id_viva').select2({
                ajax: {
                    url: 'fetch_associates.php',
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
                placeholder: 'Select associate(s)',
                // allowClear: true,
                multiple: true
            });
            // Prepopulate selected values (on form reload)
            <?php if (!empty($_GET['teacher_id_viva'])): ?>
                var selectedTeachers = <?php echo json_encode($_GET['teacher_id_viva']); ?>;
                <?php foreach ($teachers as $teacher): ?>
                    <?php if (in_array($teacher['associatenumber'], $_GET['teacher_id_viva'])): ?>
                        var option = new Option("<?= $teacher['associatenumber'] . ' - ' . $teacher['fullname'] ?>", "<?= $teacher['associatenumber'] ?>", true, true);
                        $('#teacher_id_viva').append(option).trigger('change');
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        });
    </script>

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
                                <!-- Export Button -->
                                <form method="POST" action="export_function.php">
                                    <input type="hidden" value="monthly_attd_associate" name="export_type" />
                                    <input type="hidden" value="<?php echo $id ?>" name="id" />
                                    <input type="hidden" value="<?php echo $month ?>" name="month" />
                                    <input type="hidden" value="<?php echo $associatenumber ?>" name="associateNumber" />
                                    <input type="hidden" value="<?php echo $role ?>" name="role" />
                                    <input type="hidden" value="<?php echo implode(',', $selectedTeachers) ?>" name="selectedTeachers" />
                                    <input type="hidden" value="<?php echo $engagementFilter ?>" name="engagementFilter" />

                                    <button type="submit" id="export" name="export" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none;
            padding: 0px;
            border: none;" title="Export CSV">
                                        <i class="bi bi-file-earmark-excel" style="font-size:large;"></i>
                                    </button>
                                </form>
                                |&nbsp;
                                <!-- Modal Trigger Link -->
                                <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#statusModal">View Explanation</a>
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
                                                            <option disabled selected hidden>Select Status</option>
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
                                            <!-- NEW: Engagement Dropdown -->
                                            <div class="col-md-3 col-lg-2">
                                                <div class="form-group">
                                                    <select name="engagement" id="engagement" class="form-select">
                                                        <option value="">All Engagements</option>
                                                        <option value="Employee" <?php echo (isset($_GET['engagement']) && $_GET['engagement'] == 'Employee') ? 'selected' : ''; ?>>Employee</option>
                                                        <option value="Intern" <?php echo (isset($_GET['engagement']) && $_GET['engagement'] == 'Intern') ? 'selected' : ''; ?>>Intern</option>
                                                        <option value="Member" <?php echo (isset($_GET['engagement']) && $_GET['engagement'] == 'Member') ? 'selected' : ''; ?>>Member</option>
                                                        <option value="Volunteer" <?php echo (isset($_GET['engagement']) && $_GET['engagement'] == 'Volunteer') ? 'selected' : ''; ?>>Volunteer</option>
                                                        <!-- Add more options based on your actual engagement values -->
                                                    </select>
                                                    <small class="form-text text-muted">Engagement Type</small>
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <select class="form-select" id="teacher_id_viva" name="teacher_id_viva[]" multiple="multiple">
                                                    <!-- Leave empty; Select2 will load options dynamically -->
                                                </select>
                                                <small class="form-text text-muted">Associate ID</small>
                                            </div>

                                        <?php } ?>

                                        <div class="col-12 col-sm-2">
                                            <div class="form-group">
                                                <!-- <input type="month" name="get_month" id="get_month" class="form-control"
                                                    placeholder="Month"
                                                    value="<?php echo $getMonth = isset($_GET['get_month']) ? htmlspecialchars($_GET['get_month']) : date('Y-m'); ?>">
                                                <small class="form-text text-muted">Select Month</small> -->
                                                <input type="month" name="get_month" id="get_month" class="form-control"
                                                    placeholder="Select Month"
                                                    value="<?php echo isset($_GET['get_month']) ? htmlspecialchars($_GET['get_month']) : ''; ?>">
                                                <small class="form-text text-muted">Select Month</small>
                                            </div>
                                        </div>
                                        <!-- <script>
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
                                        </script> -->
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
                                <?php if (empty($month)): ?>
                                    <div class="alert alert-info mt-3">
                                        <i class="bi bi-info-circle"></i> Please select a month and click Search to view attendance data.
                                    </div>
                                <?php elseif (empty($attendanceData)): ?>
                                    <div class="alert alert-warning mt-3">
                                        <i class="bi bi-exclamation-triangle"></i> No attendance data found for the selected month.
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <?php
                                            // Build the FULL sorted list of dates for the selected month (from calendar, not from data)
                                            $dates = [];
                                            $period = new DatePeriod(
                                                new DateTime($startDate),
                                                new DateInterval('P1D'),
                                                (new DateTime($endDate))->modify('+1 day')
                                            );
                                            foreach ($period as $d) {
                                                $dates[] = $d->format('Y-m-d');
                                            }

                                            // Group attendance rows by [associate][date] for O(1) lookup
                                            $grouped = [];
                                            foreach ($attendanceData as $row) {
                                                $aid  = $row['associatenumber'];
                                                $date = $row['attendance_date'];
                                                if (!isset($grouped[$aid][$date])) {
                                                    $grouped[$aid][$date] = $row;
                                                }
                                            }
                                            ?>
                                            <thead>
                                                <tr>
                                                    <th>Associate number</th>
                                                    <th>Name</th>
                                                    <th>Category</th>
                                                    <th>Status</th>
                                                    <th>Present</th>
                                                    <?php foreach ($dates as $date): ?>
                                                        <th><?= date("j", strtotime($date)) ?> (In)</th>
                                                        <th><?= date("j", strtotime($date)) ?> (Out)</th>
                                                    <?php endforeach; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($grouped as $aid => $rowsByDate):
                                                    $first = reset($rowsByDate);
                                                ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($first['associatenumber']) ?></td>
                                                        <td><?= htmlspecialchars($first['fullname']) ?></td>
                                                        <td><?= htmlspecialchars($first['engagement']) ?></td>
                                                        <td><?= htmlspecialchars($first['filterstatus']) ?></td>
                                                        <td><?= htmlspecialchars($first['attended_classes']) ?></td>
                                                        <?php foreach ($dates as $date):
                                                            if (isset($rowsByDate[$date])) {
                                                                $row = $rowsByDate[$date];
                                                                $punchIn  = $row['punch_in']  ? date("h:i A", strtotime($row['punch_in']))  : '';
                                                                $punchOut = $row['punch_out'] ? date("h:i A", strtotime($row['punch_out'])) : '';

                                                                $inCell  = $punchIn
                                                                    . ($row['late_status']      ? " (" . $row['late_status'] . ")"      : '')
                                                                    . ($row['exception_status'] ? " (" . $row['exception_status'] . ")" : '');
                                                                $outCell = $punchOut
                                                                    . ($row['exit_status'] ? " (" . $row['exit_status'] . ")" : '');
                                                            } else {
                                                                $inCell = $outCell = '';
                                                            }
                                                        ?>
                                                            <td><?= $inCell ?></td>
                                                            <td><?= $outCell ?></td>
                                                        <?php endforeach; ?>
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
    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i
            class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4"
        crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>


    <!-- Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="statusModalLabel">Detailed Explanation with Examples</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="accordion" id="statusAccordion">

                        <!-- L (Late) -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingL">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseL" aria-expanded="true" aria-controls="collapseL">
                                    L (Late)
                                </button>
                            </h2>
                            <div id="collapseL" class="accordion-collapse collapse show" aria-labelledby="headingL" data-bs-parent="#statusAccordion">
                                <div class="accordion-body">
                                    <p>
                                        Definition:
                                    <ol>
                                        <li>The associate's punch-in time exceeds the reporting time by more than 10 minutes.</li>
                                        <li>There is no approved exception request for the entry on that day.</li>
                                    </ol>
                                    </p>
                                    <p>Example:</p>
                                    <ul>
                                        <li>Reporting time: 10:30 AM</li>
                                        <li>Punch-in time: 10:50 AM (20 minutes late).</li>
                                        <li>No exception approved: Status is L.</li>
                                    </ul>
                                    <p>Result: <strong>L</strong> (Late)</p>
                                </div>
                            </div>
                        </div>

                        <!-- W (On Time) -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingW">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseW" aria-expanded="false" aria-controls="collapseW">
                                    W (Grace entry)
                                </button>
                            </h2>
                            <div id="collapseW" class="accordion-collapse collapse" aria-labelledby="headingW" data-bs-parent="#statusAccordion">
                                <div class="accordion-body">
                                    <ol>
                                        <li>The associate's punch-in time is recorded within 10 minutes of the reporting time.</li>
                                        <li>There is no approved exception request for the entry on that day.</li>
                                    </ol>
                                    </p>
                                    <p>Example:</p>
                                    <ul>
                                        <li>Reporting time: 10:30 AM</li>
                                        <li>Punch-in time: 10:37 AM (7 minutes late, within 10-minute tolerance).</li>
                                        <li>No exception approved: Status is W.</li>
                                    </ul>
                                    <p>Result: <strong>W</strong> (Warning)</p>
                                </div>
                            </div>
                        </div>
                        <!-- EE (Early Exit) -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingEE">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEE" aria-expanded="false" aria-controls="collapseEE">
                                    EE (Early Exit)
                                </button>
                            </h2>
                            <div id="collapseEE" class="accordion-collapse collapse" aria-labelledby="headingEE" data-bs-parent="#statusAccordion">
                                <div class="accordion-body">
                                    <p>This status is assigned when:</p>
                                    <ol>
                                        <li>The associate punches out before their scheduled exit time.</li>
                                        <li>There is no approved exit exception request for that day.</li>
                                        <li>It is not a half-day leave with afternoon shift (AFN, ASH, MSH).</li>
                                    </ol>
                                    <p>Example:</p>
                                    <ul>
                                        <li>Scheduled exit time: 6:30 PM</li>
                                        <li>Punch-out time: 6:15 PM (15 minutes early)</li>
                                        <li>No exit exception approved: Status is EE.</li>
                                    </ul>
                                    <p><strong>Note:</strong> Early exit is not marked if the associate has an approved half-day leave for the afternoon shift.</p>
                                    <p>Result: <strong>EE</strong> (Early Exit)</p>
                                </div>
                            </div>
                        </div>

                        <!-- Exc. (Exception) -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingExc">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseExc" aria-expanded="false" aria-controls="collapseExc">
                                    Exc. (Exception)
                                </button>
                            </h2>
                            <div id="collapseExc" class="accordion-collapse collapse" aria-labelledby="headingExc" data-bs-parent="#statusAccordion">
                                <div class="accordion-body">
                                    <ol>
                                        <li>There is an approved entry exception request for the day.</li>
                                        <li>The entry time is overridden by the time specified in the exception request.</li>
                                        <li>The punch-in is recorded as per the exception database.</li>
                                    </ol>
                                    </p>
                                    <p>Example:</p>
                                    <ul>
                                        <li>Exception request: Missed-entry at 10:38 AM, approved.</li>
                                        <li>Reporting time: 10:30 AM</li>
                                        <li>Actual punch-in: 10:47 AM.</li>
                                        <li>Overridden time: 10:38 AM (from exception request).</li>
                                        <li>Status is Exc. because it comes from the exception.</li>
                                    </ul>
                                    <p>Result: <strong>Exc.</strong> (Exception)</p>
                                </div>
                            </div>
                        </div>

                        <!-- Exc.L (Late with Exception) -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingExcL">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseExcL" aria-expanded="false" aria-controls="collapseExcL">
                                    Exc.L (Late with Exception)
                                </button>
                            </h2>
                            <div id="collapseExcL" class="accordion-collapse collapse" aria-labelledby="headingExcL" data-bs-parent="#statusAccordion">
                                <div class="accordion-body">
                                    <p>Definition: Indicates that the associate has a late punch-in with an approved exception like a missed-entry.</p>
                                    <p>Example:</p>
                                    <ul>
                                        <li>Scheduled Reporting Time: 10:00 AM</li>
                                        <li>Approved Exception Start Time: 10:30 AM</li>
                                        <li>Punch-in Time: 10:45 AM</li>
                                    </ul>
                                    <p>Result: <strong>Exc.L</strong> (Late with Exception)</p>
                                </div>
                            </div>
                        </div>

                        <!-- NA (No Allocation) -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingNA">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNA" aria-expanded="false" aria-controls="collapseNA">
                                    NA (No Allocation)
                                </button>
                            </h2>
                            <div id="collapseNA" class="accordion-collapse collapse" aria-labelledby="headingNA" data-bs-parent="#statusAccordion">
                                <div class="accordion-body">
                                    <p>Definition: Indicates that there is no work or reporting allocated for the day.</p>
                                    <p>Result: <strong>NA</strong> (No Allocation)</p>
                                    <p>If 'NA' is marked, please contact your immediate supervisor or manager to ensure proper allocation is updated in the system.</p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

</body>

</html>