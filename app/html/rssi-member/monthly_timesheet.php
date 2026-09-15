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

// Get filter values from GET parameters
$id = isset($_GET['get_aid']) ? $_GET['get_aid'] : 'Active';

$selectedTeachers = isset($_GET['teacher_id_viva']) ? $_GET['teacher_id_viva'] : [];
?>
<?php
// $month = isset($_GET['get_month']) ? $_GET['get_month'] : date('Y-m');
$engagementFilter = isset($_GET['engagement']) ? $_GET['engagement'] : '';

// MODIFIED: Set month to empty if not provided
$month = isset($_GET['get_month']) ? $_GET['get_month'] : '';

// Only process data if month is selected
if (!empty($month)) {
    // Calculate the start and end dates of the month

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

    $query = "
WITH date_range AS (
    SELECT generate_series('$startDate'::date, '$endDate'::date, '1 day'::interval)::date AS attendance_date
),

-- 1. Holidays and workday exceptions as simple sets
holiday_set AS (
    SELECT holiday_date FROM holidays WHERE is_flexi = false
),
workday_exc AS (
    SELECT exception_date FROM workday_exceptions WHERE is_workday = TRUE
),

-- Calendar: all dates in the month, minus holidays (unless overridden by workday_exc)
calendar AS (
    SELECT d.attendance_date
    FROM date_range d
    WHERE NOT EXISTS (SELECT 1 FROM holiday_set h WHERE h.holiday_date = d.attendance_date)
       OR EXISTS (SELECT 1 FROM workday_exc w WHERE w.exception_date = d.attendance_date)
),

-- Total Sundays (hoisted out of the SELECT list)
total_sundays AS (
    SELECT COUNT(*) AS cnt
    FROM date_range
    WHERE EXTRACT(DOW FROM attendance_date) = 0
),

-- 2. Filtered members ONCE
members AS (
    SELECT
        m.associatenumber,
        m.filterstatus,
        m.fullname,
        m.engagement,
        m.position,
        m.phone,
        m.doj,
        m.effectivedate,
        COALESCE(substring(m.class FROM '^[^-]+'), NULL) AS mode
    FROM rssimyaccount_members m
    WHERE m.grade <> 'D'
      AND DATE_TRUNC('month', m.doj) <= DATE_TRUNC('month', '$startDate'::date)
      AND (
            m.filterstatus = 'Active'
            OR (m.filterstatus = 'Inactive'
                AND DATE_TRUNC('month', m.effectivedate)::date
                    >= DATE_TRUNC('month', TO_DATE('$month','YYYY-MM'))::date)
          )
      $engagementCondition
      " . ($role !== 'Admin' ? "AND m.associatenumber = '$associatenumber'" : "") . "
),

-- 3. Schedule: one row per (associate, workday) = latest schedule as of month end.
--    dow is computed once here so joins are plain integer equality.
sched_current AS (
    SELECT DISTINCT ON (s.associate_number, s.workday)
        s.associate_number,
        s.workday,
        CASE s.workday
            WHEN 'Mon' THEN 1 WHEN 'Tue' THEN 2 WHEN 'Wed' THEN 3
            WHEN 'Thu' THEN 4 WHEN 'Fri' THEN 5 WHEN 'Sat' THEN 6
            WHEN 'Sun' THEN 0
        END AS dow,
        s.start_date,
        s.end_date,
        s.reporting_time,
        s.exit_time
    FROM associate_schedule_v2 s
    WHERE s.start_date <= '$endDate'::date
      AND (s.end_date IS NULL OR s.end_date >= '$startDate'::date)
    ORDER BY s.associate_number, s.workday, s.start_date DESC
),

-- 4. Workdays per member (replaces employee_workdays + others_workdays)
member_workdays AS (
    SELECT
        m.associatenumber,
        COUNT(c.attendance_date) AS workdays
    FROM members m
    JOIN calendar c
      ON c.attendance_date BETWEEN
            GREATEST(DATE_TRUNC('month', c.attendance_date), m.doj)
        AND LEAST(
                CASE WHEN DATE_TRUNC('month', c.attendance_date) = DATE_TRUNC('month', CURRENT_DATE)
                     THEN CURRENT_DATE
                     ELSE (DATE_TRUNC('month', c.attendance_date) + INTERVAL '1 month - 1 day')::date
                END,
                COALESCE(m.effectivedate,
                         (DATE_TRUNC('month', c.attendance_date) + INTERVAL '1 month - 1 day')::date)
            )
    JOIN sched_current sc
      ON sc.associate_number = m.associatenumber
     AND sc.dow = EXTRACT(DOW FROM c.attendance_date)::int
     AND sc.start_date <= c.attendance_date
     AND (sc.end_date IS NULL OR sc.end_date >= c.attendance_date)
    GROUP BY m.associatenumber
),

-- 5. Current schedule string per member
current_schedule_str AS (
    SELECT
        associate_number,
        STRING_AGG(
            workday, ', '
            ORDER BY CASE workday
                WHEN 'Mon' THEN 1 WHEN 'Tue' THEN 2 WHEN 'Wed' THEN 3
                WHEN 'Thu' THEN 4 WHEN 'Fri' THEN 5 WHEN 'Sat' THEN 6 WHEN 'Sun' THEN 7
            END
        ) AS current_schedule
    FROM sched_current
    GROUP BY associate_number
),

-- 6. Holiday dates string per member
holiday_dates AS (
    SELECT
        m.associatenumber,
        STRING_AGG(h.holiday_date::text, ', ') AS holiday_dates
    FROM holidays h
    JOIN members m
      ON h.holiday_date BETWEEN GREATEST(m.doj, '$startDate'::date)
                            AND LEAST(COALESCE(m.effectivedate, '$endDate'::date), '$endDate'::date)
    WHERE h.is_flexi = false
    GROUP BY m.associatenumber
),

-- 7. Punch in/out aggregated ONCE per (user, day) — no status split
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

-- 8. Exceptions aggregated ONCE
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

-- 9. Leaves aggregated ONCE
leave_agg AS (
    SELECT
        l.applicantid,
        d.attendance_date,
        MAX(CASE WHEN l.halfday = 0 THEN 1 ELSE 0 END) AS has_full_leave,
        SUM(CASE WHEN l.halfday = 1 THEN 1 ELSE 0 END) AS halfday_count
    FROM leavedb_leavedb l
    JOIN date_range d
      ON d.attendance_date BETWEEN l.fromdate AND l.todate
    WHERE l.status = 'Approved'
    GROUP BY l.applicantid, d.attendance_date
),

-- 10. Base grid: member × calendar
base AS (
    SELECT
        m.associatenumber, m.filterstatus, m.fullname, m.engagement, m.position,
        m.phone, m.mode, m.effectivedate, m.doj,
        c.attendance_date,
        p.punch_in, p.punch_out,
        sc.reporting_time, sc.exit_time,
        ee.missed_entry_time, ee.late_entry_time,
        xe.exit_time AS exc_exit_time,
        COALESCE(la.has_full_leave, 0) AS has_full_leave,
        COALESCE(la.halfday_count, 0)  AS halfday_count
    FROM members m
    CROSS JOIN calendar c
    LEFT JOIN punch_agg p
           ON p.user_id = m.associatenumber
          AND p.punch_date = c.attendance_date
    LEFT JOIN sched_current sc
           ON sc.associate_number = m.associatenumber
          AND sc.dow = EXTRACT(DOW FROM c.attendance_date)::int
          AND sc.start_date <= c.attendance_date
          AND (sc.end_date IS NULL OR sc.end_date >= c.attendance_date)
    LEFT JOIN entry_exc ee
           ON ee.submitted_by = m.associatenumber
          AND ee.exc_date = c.attendance_date
    LEFT JOIN exit_exc xe
           ON xe.submitted_by = m.associatenumber
          AND xe.exc_date = c.attendance_date
    LEFT JOIN leave_agg la
           ON la.applicantid = m.associatenumber
          AND la.attendance_date = c.attendance_date
),

-- 11. Apply CASE logic once
final AS (
    SELECT
        b.*,
        COALESCE(b.missed_entry_time, b.punch_in)  AS eff_punch_in,
        COALESCE(b.exc_exit_time,     b.punch_out) AS eff_punch_out,

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
                    WHEN b.missed_entry_time::time > b.reporting_time + INTERVAL '10 minutes' THEN 'L'
                    WHEN b.missed_entry_time::time > b.reporting_time
                     AND b.missed_entry_time::time <= b.reporting_time + INTERVAL '10 minutes' THEN 'W'
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
    f.associatenumber,
    f.fullname,
    f.engagement,
    f.position,
    f.phone,
    cs.current_schedule,
    CASE
        WHEN f.engagement = 'Employee' THEN COALESCE(mw.workdays, 0)
        WHEN f.engagement = 'Member'   THEN 0
        ELSE COALESCE(mw.workdays, 0)
    END AS work_schedule,
    hd.holiday_dates,
    (SELECT cnt FROM total_sundays) AS total_sundays,
    COUNT(*) FILTER (WHERE f.eff_punch_in IS NOT NULL AND f.eff_punch_out IS NOT NULL) AS days_worked,
    COUNT(*) FILTER (WHERE f.late_status = 'L') AS late_count,
    STRING_AGG(f.attendance_date::text, ', ') FILTER (WHERE f.late_status = 'L') AS late_dates,
    COUNT(*) FILTER (WHERE f.late_status = 'W') AS warning_count,
    STRING_AGG(f.attendance_date::text, ', ') FILTER (WHERE f.late_status = 'W') AS warning_dates,
    COUNT(*) FILTER (WHERE f.late_status = 'Leave') AS leave_count,
    STRING_AGG(f.attendance_date::text, ', ') FILTER (WHERE f.late_status = 'Leave') AS leave_dates,
    COUNT(*) FILTER (WHERE f.late_status = 'HF') AS halfday_count,
    STRING_AGG(f.attendance_date::text, ', ') FILTER (WHERE f.late_status = 'HF') AS halfday_dates,
    COUNT(*) FILTER (
        WHERE f.exception_status IN ('Exc.', 'Exc.NA')
           OR f.exit_status = 'Exc.'
           OR f.late_status IN ('Exc.', 'Exc.L')
    ) AS exception_count,
    STRING_AGG(f.attendance_date::text, ', ') FILTER (
        WHERE f.exception_status IN ('Exc.', 'Exc.NA')
           OR f.exit_status = 'Exc.'
           OR f.late_status IN ('Exc.', 'Exc.L')
    ) AS exception_dates
FROM final f
LEFT JOIN current_schedule_str cs ON cs.associate_number = f.associatenumber
LEFT JOIN member_workdays      mw ON mw.associatenumber = f.associatenumber
LEFT JOIN holiday_dates        hd ON hd.associatenumber = f.associatenumber
GROUP BY
    f.associatenumber,
    f.fullname,
    f.engagement,
    f.position,
    f.phone,
    cs.current_schedule,
    mw.workdays,
    hd.holiday_dates
ORDER BY f.associatenumber;
";
    $result = pg_query($con, $query);

    if (!$result) {
        echo "Query failed: " . pg_last_error($con);
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

    <!-- Include jQuery UI CSS and JavaScript -->
    <!-- <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script> -->

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

        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 10px;
            /* Space between the indicator and text */
        }

        .status-indicator.yellow {
            background-color: #FFBF00;
            /* Yellow color */
        }

        .status-indicator.green {
            background-color: #28a745;
            /* Green color */
        }

        .status-indicator.red {
            background-color: #dc3545;
            /* Red color */
        }

        .send-link {
            color: #888;
            /* Light gray color for the text */
            text-decoration: none;
            /* Remove underline */
            font-weight: normal;
            /* Normal weight for text appearance */
            cursor: pointer;
            /* Pointer cursor to indicate clickable */
            opacity: 0.6;
            /* Slightly faded for inactive state */
            transition: opacity 0.3s;
            /* Smooth transition on hover */
        }

        .send-link:hover {
            color: #555;
            /* Darker gray when hovered */
            opacity: 1;
            /* Full opacity on hover */
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
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    Record count:&nbsp;<?php echo $associateNumberCount ?>
                                    <!-- <p>To customize the view result, please select a filter value.</p> -->
                                </div>
                                <form action="" method="GET" class="row g-2 align-items-center">
                                    <div class="row">
                                        <!-- <?php if ($role == 'Admin') { ?>
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

                                            <div class="col-md-3">
                                                <select class="form-select" id="teacher_id_viva" name="teacher_id_viva[]"
                                                    multiple>
                                                    <option disabled hidden>Select Teacher's ID</option>
                                                    <?php foreach ($teachers as $teacher) { ?>
                                                        <option value="<?php echo $teacher['associatenumber']; ?>" <?php echo (isset($_GET['teacher_id_viva']) && in_array($teacher['associatenumber'], $_GET['teacher_id_viva'])) ? 'selected' : ''; ?>>
                                                            <?php echo $teacher['associatenumber'] . ' - ' . $teacher['fullname']; ?>
                                                        </option>
                                                    <?php } ?>
                                                </select>
                                                <small class="form-text text-muted">Teacher ID</small>
                                            </div>
                                        <?php } ?> -->

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

                                        <div class="col-12 col-sm-2">
                                            <div class="form-group">
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

                                </form><?php
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
                                <!-- <?php if (!empty($month)): ?>
                                    <div class="row align-items-center">
                                        <div class="col-6">
                                            <?php
                                            $dateTime = DateTime::createFromFormat('Y-m', $month);
                                            if ($dateTime !== false):
                                            ?>
                                                You are viewing data for
                                                <span class="blink-text">
                                                    <?= $dateTime->format('F Y') ?>
                                                </span>
                                            <?php else: ?>
                                                Invalid month format
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?> -->
                                <br>
                                <br>
                                <?php if (!empty($month) && !empty($attendanceData)): ?>
                                    <div class="timesheet-header" style="text-align: center; margin-bottom: 20px;">
                                        <h1 style="margin: 0; font-size: 24px; font-weight: bold;">Monthly Timesheet</h1>
                                        <?php
                                        // Explode the month into year and month components
                                        $components = explode("-", $month);
                                        if (count($components) === 2) {
                                            $year = $components[0];
                                            $monthNumber = $components[1];
                                            $dateTime = DateTime::createFromFormat('Y-m', $month);
                                            if ($dateTime !== false) {
                                        ?>
                                                <p style="margin: 5px 0; font-size: 16px; color: #555;">
                                                    Month: <strong><?= $dateTime->format('F') ?></strong> | Year: <strong><?= $dateTime->format('Y') ?></strong>
                                                </p>
                                                <?php
                                                $firstDate = $dateTime->format('01-m-Y'); // First date of the month
                                                // Check if today's month matches the month of the provided $dateTime
                                                if ($dateTime->format('m-Y') === date('m-Y')) {
                                                    $lastDate = date('d-m-Y'); // Current date
                                                } else {
                                                    // If not in the same month, calculate last date dynamically
                                                    $lastDate = (clone $dateTime)->modify('last day of this month')->format('d-m-Y'); // Last date of the month
                                                }
                                                ?>
                                                <p style="margin: 5px 0; font-size: 16px; color: #555;">
                                                    Reporting Period: <strong><?= $firstDate ?></strong> to <strong><?= $lastDate ?></strong>
                                                </p>
                                        <?php
                                            }
                                        }
                                        ?>
                                        <hr style="border: none; border-top: 1px solid #ccc; margin: 15px 0;">
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <td></td>
                                                    <td></td>
                                                    <th colspan="3">Section A</th>
                                                    <th colspan="3">Section B</th>
                                                    <th colspan="6">Section C</th>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <th>Associate Number</th>
                                                    <th>Full Name</th>
                                                    <th>Work Schedule</th> <!-- New column -->
                                                    <th>Scheduled Workdays</th>
                                                    <th>Days Worked</th>
                                                    <!-- <th>Leave Taken</th>
                                                <th>Half day Taken</th> -->
                                                    <th>Leave Taken</th>
                                                    <th>Late Count</th>
                                                    <th>Grace entry (W) Count</th>
                                                    <th>Exception Count</th>
                                                    <th>Leave Dates</th>
                                                    <th>Half day Dates</th>
                                                    <th>Late Dates</th>
                                                    <th>Grace entry (W) Dates</th>
                                                    <th>Exception Dates</th>
                                                    <th>Holiday</th>
                                                    <th></th>
                                                </tr>

                                            </thead>
                                            <tbody>
                                                <?php
                                                // Function to generate the WhatsApp message link
                                                function getWhatsAppLink($row, $custom_message)
                                                {
                                                    // Construct the message
                                                    $message = "Dear " . $row['fullname'] . " (" . $row['associatenumber'] . "),\n\n"
                                                        . $custom_message . "\n\n"
                                                        . "--RSSI\n\n"
                                                        . "**This is a system generated message.";

                                                    // Encode the message to make it URL-safe
                                                    $encoded_message = urlencode($message);

                                                    // Generate and return the WhatsApp URL
                                                    return "https://api.whatsapp.com/send?phone=91" . $row['phone'] . "&text=" . $encoded_message;
                                                }

                                                // Define the custom message
                                                $message = "You have been marked as a timesheet defaulter in the system due to one or more of the following reasons:\n\n"
                                                    . "1) Missed punch-in or punch-out.\n"
                                                    . "2) Leave taken but not applied.\n\n"
                                                    . "Please check your timesheet and ensure the following:\n"
                                                    . "- Any missed entry/exit, late entry, or early exit is updated.\n"
                                                    . "- Any leave taken is applied appropriately.\n\n"
                                                    . "Failure to make these adjustments may result in system-enforced leave as per the leave policy.";
                                                ?>
                                                <?php foreach ($attendanceData as $row): ?>
                                                    <tr>
                                                        <td><?php echo $row['associatenumber'];
                                                            if ((($row['days_worked'] - $row['halfday_count'] / 2) + ($row['leave_count'] + ($row['halfday_count'] / 2))) < $row['work_schedule']) { // Or any other status you want to check
                                                                echo '&nbsp;<span class="status-indicator yellow"></span>';
                                                            }
                                                            ?>
                                                        </td>
                                                        <td><?php echo $row['fullname']; ?></td>
                                                        <td><?php echo $row['current_schedule'] ?? 'Default'; ?></td> <!-- New column -->
                                                        <td><?php echo $row['work_schedule'] ?></td>
                                                        <td><?php echo $row['days_worked'] - $row['halfday_count'] / 2 ?></td>
                                                        <!-- <td><?php echo $row['leave_count']; ?></td>
                                                    <td><?php echo $row['halfday_count']; ?></td> -->
                                                        <td><?php echo $row['leave_count'] + ($row['halfday_count'] / 2); ?></td>
                                                        <td><?php echo $row['late_count']; ?></td>
                                                        <td><?php echo $row['warning_count']; ?></td>
                                                        <td><?php echo $row['exception_count']; ?></td>

                                                        <td><?php echo !empty($row['leave_dates']) ? implode(', ', array_map(function ($date) {
                                                                return date('d', strtotime($date));
                                                            }, explode(', ', $row['leave_dates']))) : ''; ?></td>
                                                        <td><?php echo !empty($row['halfday_dates']) ? implode(', ', array_map(function ($date) {
                                                                return date('d', strtotime($date));
                                                            }, explode(', ', $row['halfday_dates']))) : ''; ?></td>
                                                        <td><?php echo !empty($row['late_dates']) ? implode(', ', array_map(function ($date) {
                                                                return date('d', strtotime($date));
                                                            }, explode(', ', $row['late_dates']))) : ''; ?></td>

                                                        <td><?php echo !empty($row['warning_dates']) ? implode(', ', array_map(function ($date) {
                                                                return date('d', strtotime($date));
                                                            }, explode(', ', $row['warning_dates']))) : ''; ?></td>

                                                        <td><?php echo !empty($row['exception_dates']) ? implode(', ', array_map(function ($date) {
                                                                return date('d', strtotime($date));
                                                            }, explode(', ', $row['exception_dates']))) : ''; ?></td>
                                                        <td><?php echo !empty($row['holiday_dates']) ? implode(', ', array_map(function ($date) {
                                                                return date('d', strtotime($date));
                                                            }, explode(', ', $row['holiday_dates']))) : ''; ?></td>
                                                        <td>
                                                            <?php
                                                            $link = getWhatsAppLink($row, $message);
                                                            // Set the title text dynamically
                                                            $title = "Send WhatsApp message to " . $row['fullname'] . " (" . $row['associatenumber'] . ")";
                                                            // Check if the "Reminder" link should be displayed
                                                            if ((($row['days_worked'] - $row['halfday_count'] / 2) + ($row['leave_count'] + ($row['halfday_count'] / 2))) != $row['work_schedule']) {
                                                                echo '<a href="' . $link . '" target="_blank" title="' . htmlspecialchars($title) . '" class="send-link">Send</a>';
                                                            }
                                                            ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                                <?php if (empty($month)): ?>
                                    <div class="alert alert-info mt-3">
                                        <i class="bi bi-info-circle"></i> Please select a month and click Search to view attendance data.
                                    </div>
                                <?php elseif (empty($attendanceData)): ?>
                                    <div class="alert alert-warning mt-3">
                                        <i class="bi bi-exclamation-triangle"></i> No attendance data found for the selected month.
                                    </div>
                                <?php endif; ?>
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