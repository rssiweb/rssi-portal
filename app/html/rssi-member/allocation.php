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
?>
<?php
// Get the current date
$currentMonth = date('m');
$currentYear = date('Y');

// Default values for start_date and end_date
$startDate = isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-01'); // First day of the current month
$endDate = isset($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-t');       // Last day of the current month

// Get the user-provided or default month
$month = isset($_POST['month']) ? $_POST['month'] : date('Y-m');

// Check if the user provided a start and end month
$startMonth = isset($_POST['start_month']) ? $_POST['start_month'] : null;
$endMonth = isset($_POST['end_month']) ? $_POST['end_month'] : null;

// Define the quarter ranges
if (!$startMonth || !$endMonth) {
    if ($currentMonth >= 4 && $currentMonth <= 7) {
        // Quarter 1: April to July
        $startMonth = "$currentYear-04";
        $endMonth = "$currentYear-07";
    } elseif ($currentMonth >= 8 && $currentMonth <= 11) {
        // Quarter 2: August to November
        $startMonth = "$currentYear-08";
        $endMonth = "$currentYear-11";
    } else {
        // Quarter 3: December to March (spanning two years)
        $startMonth = $currentMonth >= 12 ? "$currentYear-12" : ($currentYear - 1) . "-12";
        $endMonth = "$currentYear-03";
    }

    // Adjust endMonth if it's in the future
    $endMonthTimestamp = strtotime($endMonth . '-01');
    $currentMonthTimestamp = strtotime(date('Y-m-01'));
    if ($endMonthTimestamp > $currentMonthTimestamp) {
        $endMonth = date('Y-m');
    }

    // Set the start and end dates based on the adjusted months
    $startDate = $startMonth . '-01';
    $endDate = date('Y-m-t', strtotime($endMonth . '-01'));

    // Also update the month variables to match the adjusted dates
    $startMonth = date('Y-m', strtotime($startDate));
    $endMonth = date('Y-m', strtotime($endDate));
}

//echo "Start Month: $startMonth, End Month: $endMonth<br>";

// Extract year and month for SQL query
list($startYear, $startMonthNum) = explode('-', $startMonth);
list($endYear, $endMonthNum) = explode('-', $endMonth);

// Generate the date range dynamically in SQL
$query = "
    WITH date_range AS (
        SELECT generate_series(
            DATE '$startYear-$startMonthNum-01',
            DATE '$endYear-$endMonthNum-01' + INTERVAL '1 month' - INTERVAL '1 day',
            INTERVAL '1 day'
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
        COUNT(h.attendance_date) AS workdays_employee,
        MIN(h.attendance_date) AS start_date,
        MAX(h.attendance_date) AS end_date
    FROM 
        holidays_excluded h
    INNER JOIN 
        rssimyaccount_members m
        ON h.attendance_date BETWEEN 
            GREATEST(DATE_TRUNC('month', h.attendance_date), m.doj)
            AND 
            LEAST(
                CASE 
                    WHEN DATE_TRUNC('month', h.attendance_date) = DATE_TRUNC('month', CURRENT_DATE) THEN CURRENT_DATE
                    ELSE DATE_TRUNC('month', h.attendance_date) + INTERVAL '1 month - 1 day'
                END,
                COALESCE(m.effectivedate, DATE_TRUNC('month', h.attendance_date) + INTERVAL '1 month - 1 day')
            )
    LEFT JOIN LATERAL (
        SELECT s.workdays, s.start_date AS schedule_start
        FROM associate_schedule s
        WHERE s.associate_number = m.associatenumber
        AND s.start_date <= h.attendance_date
        ORDER BY s.start_date DESC
        LIMIT 1
    ) sched ON true
    LEFT JOIN 
        workday_exceptions w
        ON h.attendance_date = w.exception_date AND w.is_workday = TRUE
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
        -- If no schedule exists, use default logic (Mon-Fri for employees)
        (sched.workdays IS NULL AND 
         (DATE_PART('dow', h.attendance_date) BETWEEN 1 AND 5 OR w.is_workday IS NOT NULL))
    GROUP BY 
        m.associatenumber
),
others_workdays AS (
    SELECT 
        m.associatenumber,
        COUNT(h.attendance_date) AS workdays_others,
        MIN(h.attendance_date) AS start_date,
        MAX(h.attendance_date) AS end_date
    FROM 
        holidays_excluded h
    INNER JOIN 
        rssimyaccount_members m
        ON h.attendance_date BETWEEN 
            GREATEST(DATE_TRUNC('month', h.attendance_date), m.doj)
            AND 
            LEAST(
                CASE 
                    WHEN DATE_TRUNC('month', h.attendance_date) = DATE_TRUNC('month', CURRENT_DATE) THEN CURRENT_DATE
                    ELSE DATE_TRUNC('month', h.attendance_date) + INTERVAL '1 month - 1 day'
                END,
                COALESCE(m.effectivedate, DATE_TRUNC('month', h.attendance_date) + INTERVAL '1 month - 1 day')
            )
    LEFT JOIN LATERAL (
        SELECT s.workdays, s.start_date AS schedule_start
        FROM associate_schedule s
        WHERE s.associate_number = m.associatenumber
        AND s.start_date <= h.attendance_date
        ORDER BY s.start_date DESC
        LIMIT 1
    ) sched ON true
    LEFT JOIN 
        workday_exceptions w
        ON h.attendance_date = w.exception_date AND w.is_workday = TRUE
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
        -- If no schedule exists, use default logic (Mon-Thu for others)
        (sched.workdays IS NULL AND 
         (DATE_PART('dow', h.attendance_date) BETWEEN 1 AND 4 OR w.is_workday IS NOT NULL))
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
    -- Include employees whose active range overlaps with the selected range
    DATE_TRUNC('month', TO_DATE('$startMonth', 'YYYY-MM'))::DATE <= COALESCE(DATE_TRUNC('month', m.effectivedate)::DATE, NOW())
    AND DATE_TRUNC('month', TO_DATE('$endMonth', 'YYYY-MM'))::DATE >= DATE_TRUNC('month', m.doj)::DATE
    -- Restrict to specific associate if role is not Admin
    " . ($role !== 'Admin' ? "AND m.associatenumber = '$associatenumber'" : "") . "
)
SELECT 
    m.associatenumber,
    m.fullname,
    m.engagement,
    m.phone,
    m.doj,
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
    CASE 
        WHEN m.engagement = 'Employee' THEN 
            (SELECT start_date 
             FROM employee_workdays 
             WHERE employee_workdays.associatenumber = m.associatenumber)
        WHEN m.engagement = 'Member' THEN NULL
        ELSE 
            (SELECT start_date  
             FROM others_workdays  
             WHERE others_workdays.associatenumber = m.associatenumber)
    END AS schedule_start_date,
    CASE 
        WHEN m.engagement = 'Employee' THEN 
            (SELECT end_date 
             FROM employee_workdays 
             WHERE employee_workdays.associatenumber = m.associatenumber)
        WHEN m.engagement = 'Member' THEN NULL
        ELSE 
            (SELECT end_date  
             FROM others_workdays  
             WHERE others_workdays.associatenumber = m.associatenumber)
    END AS schedule_end_date,
    h.holiday_dates, -- Corrected line
    (SELECT total_sundays FROM sunday_count) AS total_sundays,
    COUNT(*) FILTER (WHERE punch_in IS NOT NULL AND punch_out IS NOT NULL) AS days_worked,
    COUNT(*) FILTER (WHERE late_status = 'L') AS late_count,
    COUNT(*) FILTER (WHERE late_status = 'W') AS warning_count,
    COUNT(*) FILTER (WHERE late_status = 'Leave') AS leave_count,
    COUNT(*) FILTER (WHERE late_status = 'HF') AS halfday_count,
    COUNT(*) FILTER (WHERE 
        exception_status ILIKE '%Exc%' OR 
        exit_status ILIKE '%Exc%' OR 
        late_status ILIKE '%Exc%') AS exception_count
FROM 
    attendance_data ad
JOIN 
    rssimyaccount_members m
    ON ad.associatenumber = m.associatenumber
LEFT JOIN 
    holiday_dates h
    ON ad.associatenumber = h.associatenumber -- Correcting the join condition
WHERE 
    mode = 'Offline'
    AND grade!='D'
    AND m.doj <= $1::DATE  -- Now using endDate
GROUP BY 
    m.associatenumber, m.fullname, m.engagement, h.holiday_dates
ORDER BY 
    m.associatenumber;
";
$result = pg_query_params($con, $query, [$endDate]);

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
                                <form action="" method="POST" class="row g-2 align-items-center" id="search_form">
                                    <div class="row">
                                        <!-- Start Month Input -->
                                        <div class="col-12 col-sm-2">
                                            <div class="form-group">
                                                <!-- <label for="start_month">Start Month</label> -->
                                                <input type="month" name="start_month" id="start_month" class="form-control"
                                                    value="<?php echo $startMonth; ?>">
                                                <small class="form-text text-muted">Select Start Month</small>
                                            </div>
                                        </div>

                                        <!-- End Month Input -->
                                        <div class="col-12 col-sm-2">
                                            <div class="form-group">
                                                <!-- <label for="end_month">End Month</label> -->
                                                <input type="month" name="end_month" id="end_month" class="form-control"
                                                    value="<?php echo $endMonth; ?>">
                                                <small class="form-text text-muted">Select End Month</small>
                                            </div>
                                        </div>

                                        <!-- Submit Button -->
                                        <div class="col-12 col-sm-2">
                                            <button type="submit" name="search_by_id" id="search_by_id" class="btn btn-primary" style="outline: none;">
                                                <i class="bi bi-search"></i> <span id="button_text">Search</span>
                                            </button>
                                        </div>
                                    </div>
                                </form>

                                <div class="table-responsive mt-5 mb-3">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <th colspan="2">Allocation Period</th>
                                                <th colspan="5">Section A</th>
                                                <th colspan="3">Section B</th>
                                            </tr>
                                            <tr>
                                                <th>Associate Number</th>
                                                <th>Full Name</th>
                                                <th>Date of Join</th>
                                                <th>Start Date</th>
                                                <th>End Date</th>
                                                <th>Scheduled Workdays</th>
                                                <th>Days Worked</th>
                                                <th>Leave Taken</th>
                                                <th colspan="2">Allocation Index</th>
                                                <th>Late Count</th>
                                                <th>Grace entry (W) Count</th>
                                                <th>Exception Count</th>
                                            </tr>

                                        </thead>
                                        <tbody>
                                            <?php foreach ($attendanceData as $row): ?>
                                                <tr>
                                                    <td><?php echo $row['associatenumber']; ?></td>
                                                    <td><?php echo $row['fullname']; ?></td>
                                                    <td>
                                                        <?php
                                                        echo isset($row['doj']) && !empty($row['doj'])
                                                            ? date('d/m/Y', strtotime($row['doj']))
                                                            : '';
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        echo isset($row['schedule_start_date']) && !empty($row['schedule_start_date'])
                                                            ? date('d/m/Y', strtotime($row['schedule_start_date']))
                                                            : '';
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        echo isset($row['schedule_end_date']) && !empty($row['schedule_end_date'])
                                                            ? (date('Y-m-d', strtotime($row['schedule_end_date'])) === date('Y-m-d')
                                                                ? ''
                                                                : date('d/m/Y', strtotime($row['schedule_end_date'])))
                                                            : '';
                                                        ?>
                                                    </td>
                                                    <td><?php echo $row['work_schedule'] ?></td>
                                                    <td><?php echo $row['days_worked'] - $row['halfday_count'] / 2 ?></td>
                                                    <td><?php echo $row['leave_count'] + ($row['halfday_count'] / 2); ?></td>
                                                    <?php
                                                    $percentage = 0;

                                                    if ($row['work_schedule'] > 0) {
                                                        $percentage = (($row['days_worked'] - $row['halfday_count'] / 2) / $row['work_schedule']) * 100;
                                                    }
                                                    ?>
                                                    <td><?php echo number_format($percentage, 2) . '%'; ?></td>
                                                    <td><?php if ($percentage !== null): ?>
                                                            <meter id="disk_c" value="<?= strtok($percentage, '%') ?>" min="0" max="100"></meter>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo $row['late_count']; ?></td>
                                                    <td><?php echo $row['warning_count']; ?></td>
                                                    <td><?php echo $row['exception_count']; ?></td>
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
  
    <script>
        const today = new Date();
        const maxMonth = today.toISOString().slice(0, 7);

        const startMonthInput = document.getElementById('start_month');
        const endMonthInput = document.getElementById('end_month');

        // Set the max attribute for both inputs
        startMonthInput.setAttribute('max', maxMonth);
        endMonthInput.setAttribute('max', maxMonth);

        // Initialize min/max attributes if values are pre-selected
        if (startMonthInput.value) {
            endMonthInput.setAttribute('min', startMonthInput.value);
        }

        if (endMonthInput.value) {
            startMonthInput.setAttribute('max', endMonthInput.value);
        }

        // Update the min and max attributes based on selected start_month
        startMonthInput.addEventListener('change', function() {
            const selectedStartMonth = this.value;
            endMonthInput.setAttribute('min', selectedStartMonth);
        });

        // Update the min and max attributes based on selected end_month
        endMonthInput.addEventListener('change', function() {
            const selectedEndMonth = this.value;
            startMonthInput.setAttribute('max', selectedEndMonth);
        });
    </script>
    <script>
        const searchForm = document.getElementById('search_form');
        const searchButton = document.getElementById('search_by_id');
        const buttonText = document.getElementById('button_text');

        searchForm.addEventListener('submit', function() {
            // Change the button text to "Loading..."
            buttonText.textContent = 'Loading...';
            // Disable the button to prevent multiple submissions
            searchButton.setAttribute('disabled', true);
        });
    </script>
</body>

</html>