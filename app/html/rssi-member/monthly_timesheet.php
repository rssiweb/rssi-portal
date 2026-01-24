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
-- Query with updated logic for calculating scheduled workdays, considering DOJ and effective date
WITH date_range AS (
    SELECT generate_series(
        '$startDate'::date,
        '$endDate'::date,
        '1 day'::interval
    ) AS attendance_date
),
holidays_excluded AS (
    SELECT 
        d.attendance_date
    FROM 
        date_range d
    LEFT JOIN 
        workday_exceptions w 
        ON d.attendance_date = w.exception_date AND w.is_workday = TRUE
    WHERE 
        d.attendance_date NOT IN (
            SELECT holiday_date 
            FROM holidays 
            WHERE is_flexi = false
        ) 
        OR w.is_workday IS NOT NULL -- Include workday exceptions even if it's a holiday
),
sunday_count AS (
    SELECT 
        COUNT(*) AS total_sundays
    FROM 
        date_range
    WHERE 
        DATE_PART('dow', attendance_date) = 0 -- Sundays only
),
employee_workdays AS (
    SELECT 
        m.associatenumber,
        COUNT(h.attendance_date) AS workdays_employee
    FROM 
        holidays_excluded h
    INNER JOIN 
        rssimyaccount_members m
        ON h.attendance_date BETWEEN 
            GREATEST(DATE_TRUNC('month', h.attendance_date), m.doj) -- From the later of the month's start or the associate's DOJ
            AND 
            LEAST(
                CASE 
                    -- If today is in the same month as attendance_date, use today
                    WHEN DATE_TRUNC('month', h.attendance_date) = DATE_TRUNC('month', CURRENT_DATE) THEN CURRENT_DATE
                    ELSE DATE_TRUNC('month', h.attendance_date) + INTERVAL '1 month - 1 day' -- Use month's end otherwise
                END,
                COALESCE(m.effectivedate, DATE_TRUNC('month', h.attendance_date) + INTERVAL '1 month - 1 day')
            ) -- To the earlier of today's date (if in the same month) or the associate's effective date
    LEFT JOIN 
        workday_exceptions w
        ON h.attendance_date = w.exception_date AND w.is_workday = TRUE
    LEFT JOIN LATERAL (
        SELECT s.workdays
        FROM associate_schedule s
        WHERE s.associate_number = m.associatenumber
        AND s.start_date <= h.attendance_date
        ORDER BY s.start_date DESC
        LIMIT 1
    ) sched ON true
    WHERE 
        -- If schedule exists, check if day is in workdays
        (sched.workdays IS NOT NULL AND 
         CASE 
             WHEN DATE_PART('dow', h.attendance_date) = 0 THEN sched.workdays LIKE '%Sun%'
             WHEN DATE_PART('dow', h.attendance_date) = 1 THEN sched.workdays LIKE '%Mon%'
             WHEN DATE_PART('dow', h.attendance_date) = 2 THEN sched.workdays LIKE '%Tue%'
             WHEN DATE_PART('dow', h.attendance_date) = 3 THEN sched.workdays LIKE '%Wed%'
             WHEN DATE_PART('dow', h.attendance_date) = 4 THEN sched.workdays LIKE '%Thu%'
             WHEN DATE_PART('dow', h.attendance_date) = 5 THEN sched.workdays LIKE '%Fri%'
             WHEN DATE_PART('dow', h.attendance_date) = 6 THEN sched.workdays LIKE '%Sat%'
         END)
        OR
        -- If no schedule exists, use default logic
        (sched.workdays IS NULL AND 
         (DATE_PART('dow', h.attendance_date) != 0 OR w.is_workday IS NOT NULL)) -- Exclude Sundays unless they are marked as workdays
    GROUP BY 
        m.associatenumber
),
others_workdays AS (
    SELECT 
        m.associatenumber,
        COUNT(h.attendance_date) AS workdays_others
    FROM 
        holidays_excluded h
    INNER JOIN 
        rssimyaccount_members m
        ON h.attendance_date BETWEEN 
            GREATEST(DATE_TRUNC('month', h.attendance_date), m.doj) -- From the later of the month's start or the associate's DOJ
            AND 
            LEAST(
                CASE 
                    -- If today is in the same month as attendance_date, use today
                    WHEN DATE_TRUNC('month', h.attendance_date) = DATE_TRUNC('month', CURRENT_DATE) THEN CURRENT_DATE
                    ELSE DATE_TRUNC('month', h.attendance_date) + INTERVAL '1 month - 1 day' -- Use month's end otherwise
                END,
                COALESCE(m.effectivedate, DATE_TRUNC('month', h.attendance_date) + INTERVAL '1 month - 1 day')
            ) -- To the earlier of today's date (if in the same month) or the associate's effectivedate
    LEFT JOIN 
        workday_exceptions w
        ON h.attendance_date = w.exception_date AND w.is_workday = TRUE
    LEFT JOIN LATERAL (
        SELECT s.workdays
        FROM associate_schedule s
        WHERE s.associate_number = m.associatenumber
        AND s.start_date <= h.attendance_date
        ORDER BY s.start_date DESC
        LIMIT 1
    ) sched ON true
    WHERE 
        -- If schedule exists, check if day is in workdays
        (sched.workdays IS NOT NULL AND 
         CASE 
             WHEN DATE_PART('dow', h.attendance_date) = 0 THEN sched.workdays LIKE '%Sun%'
             WHEN DATE_PART('dow', h.attendance_date) = 1 THEN sched.workdays LIKE '%Mon%'
             WHEN DATE_PART('dow', h.attendance_date) = 2 THEN sched.workdays LIKE '%Tue%'
             WHEN DATE_PART('dow', h.attendance_date) = 3 THEN sched.workdays LIKE '%Wed%'
             WHEN DATE_PART('dow', h.attendance_date) = 4 THEN sched.workdays LIKE '%Thu%'
             WHEN DATE_PART('dow', h.attendance_date) = 5 THEN sched.workdays LIKE '%Fri%'
             WHEN DATE_PART('dow', h.attendance_date) = 6 THEN sched.workdays LIKE '%Sat%'
         END)
        OR
        -- If no schedule exists, use default logic
        (sched.workdays IS NULL AND 
         (DATE_PART('dow', h.attendance_date) BETWEEN 1 AND 4 OR w.is_workday IS NOT NULL)) -- Monday to Thursday
    GROUP BY 
        m.associatenumber
),
holiday_dates AS (
    SELECT 
        m.associatenumber,
        STRING_AGG(h.holiday_date::text, ', ') AS holiday_dates
    FROM 
        holidays h
    INNER JOIN 
        rssimyaccount_members m 
        ON h.holiday_date BETWEEN 
            GREATEST(m.doj, '$startDate'::date) 
        AND 
            LEAST(COALESCE(m.effectivedate, '$endDate'::date), '$endDate'::date)
    WHERE 
        h.is_flexi = false
    GROUP BY 
        m.associatenumber
),
DynamicSchedule AS (
    SELECT
        s.associate_number,
        s.start_date,
        s.reporting_time,
        s.exit_time,
        m.filterstatus,
        m.effectivedate,
        COALESCE(
            LEAD(s.start_date) OVER (PARTITION BY s.associate_number ORDER BY s.start_date) - INTERVAL '1 day',
            CASE
                WHEN m.effectivedate IS NOT NULL THEN m.effectivedate
                ELSE CURRENT_DATE
            END
        ) AS end_date
    FROM associate_schedule s
    INNER JOIN rssimyaccount_members m
        ON s.associate_number = m.associatenumber
    ORDER BY s.associate_number, s.start_date, s.timestamp DESC
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
        m.position,
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

        ds.reporting_time,
        ds.exit_time,

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
                        ), p.punch_in)::time) > EXTRACT(EPOCH FROM ds.reporting_time) + 600 THEN 'L'
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
                        ), p.punch_in)::time) > EXTRACT(EPOCH FROM ds.reporting_time)
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
                            ), p.punch_in)::time) <= EXTRACT(EPOCH FROM ds.reporting_time) + 600 THEN 'W'
                    -- If it's on time (or earlier), status should be NULL (not late)
                    ELSE NULL
                END
            -- For regular punch-ins, apply standard lateness logic
            WHEN p.punch_in IS NOT NULL THEN
                CASE
                    WHEN ds.reporting_time IS NULL THEN 'NA'
                    WHEN EXTRACT(EPOCH FROM p.punch_in::time) > EXTRACT(EPOCH FROM ds.reporting_time + INTERVAL '1 minute')
                        AND EXTRACT(EPOCH FROM p.punch_in::time) <= EXTRACT(EPOCH FROM ds.reporting_time + INTERVAL '1 minute') + 600 THEN 'W'
                    WHEN EXTRACT(EPOCH FROM p.punch_in::time) > EXTRACT(EPOCH FROM ds.reporting_time) + 600 THEN 'L'
                    ELSE NULL
                END
            ELSE NULL
        END AS late_status,

        -- Exit status logic remains unchanged
        CASE
            WHEN EXISTS (
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
    -- Show 'Exc.' if approved exception exists
    WHEN EXISTS (
        SELECT 1
        FROM exception_requests e
        WHERE e.submitted_by = m.associatenumber
        AND e.status = 'Approved'
        AND e.exception_type = 'entry'
        AND e.sub_exception_type = 'missed-entry'
        AND d.attendance_date = DATE(e.start_date_time)
    ) THEN 
        -- Check if ds.reporting_time is NULL, then add 'NA'
        CASE 
            WHEN ds.reporting_time IS NULL THEN 'Exc.NA'
            ELSE 'Exc.'
        END
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
        DynamicSchedule ds
        ON m.associatenumber = ds.associate_number
        AND d.attendance_date BETWEEN ds.start_date AND ds.end_date
    WHERE
        (
            (m.filterstatus = 'Active') OR
            (m.filterstatus = 'Inactive' AND DATE_TRUNC('month', m.effectivedate)::DATE >= DATE_TRUNC('month', TO_DATE('$month', 'YYYY-MM'))::DATE)
        )
        AND DATE_TRUNC('month', m.doj)::DATE <= DATE_TRUNC('month', TO_DATE('$month', 'YYYY-MM'))::DATE
        -- $idCondition
        -- $teacherCondition
        " . ($role !== 'Admin' ? "AND m.associatenumber = '$associatenumber'" : "") . "
)
SELECT 
    m.associatenumber,
    m.fullname,
    m.engagement,
    m.position,
    m.phone,
    (
        SELECT s.workdays 
        FROM associate_schedule s 
        WHERE s.associate_number = m.associatenumber
        AND s.start_date <= '$endDate'::date
        ORDER BY s.start_date DESC
        LIMIT 1
    ) AS current_schedule,
    CASE 
    WHEN m.engagement = 'Employee' THEN 
        (SELECT workdays_employee 
         FROM employee_workdays 
         WHERE employee_workdays.associatenumber = m.associatenumber)
    WHEN m.engagement = 'Member' THEN 0
    ELSE 
        (SELECT workdays_others  
         FROM others_workdays  
         WHERE others_workdays.associatenumber = m.associatenumber)
END AS work_schedule,
    h.holiday_dates, -- Corrected line
    (SELECT total_sundays FROM sunday_count) AS total_sundays,
    COUNT(*) FILTER (WHERE punch_in IS NOT NULL AND punch_out IS NOT NULL) AS days_worked,
    COUNT(*) FILTER (WHERE late_status = 'L') AS late_count,
    STRING_AGG(CASE WHEN late_status = 'L' THEN attendance_date::text ELSE NULL END, ', ') AS late_dates,
    COUNT(*) FILTER (WHERE late_status = 'W') AS warning_count,
    STRING_AGG(CASE WHEN late_status = 'W' THEN attendance_date::text ELSE NULL END, ', ') AS warning_dates,
    COUNT(*) FILTER (WHERE late_status = 'Leave') AS leave_count,
    STRING_AGG(CASE WHEN late_status = 'Leave' THEN attendance_date::text ELSE NULL END, ', ') AS leave_dates,
    COUNT(*) FILTER (WHERE late_status = 'HF') AS halfday_count,
    STRING_AGG(CASE WHEN late_status = 'HF' THEN attendance_date::text ELSE NULL END, ', ') AS halfday_dates,
    COUNT(*) FILTER (WHERE 
        exception_status ILIKE '%Exc%' OR 
        exit_status ILIKE '%Exc%' OR 
        late_status ILIKE '%Exc%') AS exception_count,
    STRING_AGG(CASE WHEN exception_status ILIKE '%Exc%' OR 
        exit_status ILIKE '%Exc%' OR 
        late_status ILIKE '%Exc%' THEN attendance_date::text ELSE NULL END, ', ') AS exception_dates
FROM 
    attendance_data ad
JOIN 
    rssimyaccount_members m
    ON ad.associatenumber = m.associatenumber
LEFT JOIN 
    holiday_dates h
    ON ad.associatenumber = h.associatenumber -- Correcting the join condition
WHERE 
    -- mode = 'Offline'
    (m.engagement IN ('Employee', 'Intern') OR m.position IN ('Intern'))
    AND grade!='D'
    AND DATE_TRUNC('month', m.doj) <= DATE_TRUNC('month', '$startDate'::date)
GROUP BY 
    m.associatenumber, m.fullname, m.engagement, m.position, h.holiday_dates
ORDER BY 
    m.associatenumber;
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
    <?php include 'includes/meta.php' ?>

    

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

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

                                        <div class="col-12 col-sm-2">
                                            <div class="form-group">
                                                <input type="month" name="get_month" id="get_month" class="form-control"
                                                    placeholder="Month"
                                                    value="<?php echo $getMonth = isset($_GET['get_month']) ? htmlspecialchars($_GET['get_month']) : date('Y-m'); ?>">
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
                                <!-- <div class="row align-items-center">
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
                                </div> -->
                                <br>
                                <br>
                                <div class="timesheet-header" style="text-align: center; margin-bottom: 20px;">
                                    <h1 style="margin: 0; font-size: 24px; font-weight: bold;">Monthly Timesheet</h1>
                                    <p style="margin: 5px 0; font-size: 16px; color: #555;">
                                        Month: <strong><?= $dateTime->format('F') ?></strong> | Year: <strong><?= $dateTime->format('Y') ?></strong>
                                    </p>
                                    <?php
                                    $dateTime = new DateTime('01-' . $dateTime->format('m-Y')); // Start of the current month
                                    $firstDate = $dateTime->format('01-m-Y'); // First date of the month
                                    // Check if today's month matches the month of the provided $dateTime
                                    if ($dateTime->format('m-Y') === date('m-Y')) {
                                        $lastDate = date('d-m-Y'); // Current date
                                    } else {
                                        // If not in the same month, calculate last date dynamically
                                        $lastDate = $dateTime->modify('last day of this month')->format('d-m-Y'); // Last date of the month
                                    }
                                    ?>
                                    <p style="margin: 5px 0; font-size: 16px; color: #555;">
                                        Reporting Period: <strong><?= $firstDate ?></strong> to <strong><?= $lastDate ?></strong>
                                    </p>
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