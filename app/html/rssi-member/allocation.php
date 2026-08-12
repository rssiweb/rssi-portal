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

// Get filter values
$engagementFilter = isset($_GET['engagement']) ? $_GET['engagement'] : '';
$selectedTeachers = isset($_GET['teacher_id_viva']) ? $_GET['teacher_id_viva'] : [];

// Default values for start_date and end_date - set to empty
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Get the user-provided start and end months
$startMonth = isset($_GET['start_month']) ? $_GET['start_month'] : '';
$endMonth = isset($_GET['end_month']) ? $_GET['end_month'] : '';

// Fetch teacher names for selected IDs (for prepopulation)
$teachers = [];
if (!empty($selectedTeachers)) {
    $placeholders = implode(',', array_map(
        fn($i) => '$' . ($i + 1),
        array_keys($selectedTeachers)
    ));

    $teacherQuery = "
        SELECT associatenumber, fullname 
        FROM rssimyaccount_members 
        WHERE associatenumber IN ($placeholders) 
        AND filterstatus = 'Active'
    ";

    $teacherResult = pg_query_params($con, $teacherQuery, $selectedTeachers);
    if ($teacherResult) {
        while ($row = pg_fetch_assoc($teacherResult)) {
            $teachers[] = $row;
        }
        pg_free_result($teacherResult);
    }
}

// Only process data if both start and end months are selected
$showData = !empty($startMonth) && !empty($endMonth);

if ($showData) {
    // Extract year and month for SQL query
    list($startYear, $startMonthNum) = explode('-', $startMonth);
    list($endYear, $endMonthNum) = explode('-', $endMonth);

    // Set the start and end dates based on the adjusted months
    $startDate = $startMonth . '-01';
    $endDate = date('Y-m-t', strtotime($endMonth . '-01'));

    // Construct the engagement condition
    $engagementCondition = '';
    if (!empty($engagementFilter)) {
        $engagementCondition = "AND m.engagement = '" . pg_escape_string($con, $engagementFilter) . "'";
    }

    // Construct the teacher condition
    $teacherCondition = '';
    if (!empty($selectedTeachers)) {
        $escapedTeachers = array_map(function ($teacher) use ($con) {
            return pg_escape_string($con, $teacher);
        }, $selectedTeachers);
        $teacherList = implode("','", $escapedTeachers);
        $teacherCondition = "AND m.associatenumber IN ('$teacherList')";
    }

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
        -- Get the latest schedule for each date, considering end_date
        LEFT JOIN LATERAL (
            SELECT s.workday, s.start_date AS schedule_start, s.end_date AS schedule_end
            FROM associate_schedule_v2 s
            WHERE s.associate_number = m.associatenumber
            AND s.start_date <= h.attendance_date
            -- Match day of week
            AND s.workday = 
                CASE DATE_PART('dow', h.attendance_date)
                    WHEN 1 THEN 'Mon'
                    WHEN 2 THEN 'Tue'
                    WHEN 3 THEN 'Wed'
                    WHEN 4 THEN 'Thu'
                    WHEN 5 THEN 'Fri'
                    WHEN 6 THEN 'Sat'
                    WHEN 0 THEN 'Sun'
                END
            -- Only include schedules that are active on this date (no end_date OR end_date >= attendance_date)
            AND (s.end_date IS NULL OR s.end_date >= h.attendance_date)
            ORDER BY s.start_date DESC
            LIMIT 1
        ) sched ON true
        LEFT JOIN 
            workday_exceptions w
            ON h.attendance_date = w.exception_date AND w.is_workday = TRUE
        WHERE 
            (sched.workday IS NOT NULL OR w.is_workday IS NOT NULL)
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
        -- Get the latest schedule for each date, considering end_date
        LEFT JOIN LATERAL (
            SELECT s.workday, s.start_date AS schedule_start, s.end_date AS schedule_end
            FROM associate_schedule_v2 s
            WHERE s.associate_number = m.associatenumber
            AND s.start_date <= h.attendance_date
            -- Match day of week
            AND s.workday = 
                CASE DATE_PART('dow', h.attendance_date)
                    WHEN 1 THEN 'Mon'
                    WHEN 2 THEN 'Tue'
                    WHEN 3 THEN 'Wed'
                    WHEN 4 THEN 'Thu'
                    WHEN 5 THEN 'Fri'
                    WHEN 6 THEN 'Sat'
                    WHEN 0 THEN 'Sun'
                END
            -- Only include schedules that are active on this date (no end_date OR end_date >= attendance_date)
            AND (s.end_date IS NULL OR s.end_date >= h.attendance_date)
            ORDER BY s.start_date DESC
            LIMIT 1
        ) sched ON true
        LEFT JOIN 
            workday_exceptions w
            ON h.attendance_date = w.exception_date AND w.is_workday = TRUE
        WHERE 
            (sched.workday IS NOT NULL OR w.is_workday IS NOT NULL)
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
        FROM associate_schedule_v2 s
        INNER JOIN rssimyaccount_members m
            ON s.associate_number = m.associatenumber
        ORDER BY s.associate_number, s.start_date, s.created_at DESC
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
                p.punch_in
            ) AS punch_in,

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

            CASE
                WHEN p.punch_in IS NOT NULL THEN 'P'
                WHEN p.punch_in IS NULL AND d.attendance_date NOT IN (SELECT date FROM attendance) THEN NULL
                WHEN m.doj > d.attendance_date THEN NULL
                ELSE 'A'
            END AS attendance_status,

            ds.reporting_time,
            ds.exit_time,

            CASE
                WHEN EXISTS (
                    SELECT 1
                    FROM leavedb_leavedb l
                    WHERE l.applicantid = m.associatenumber
                    AND l.status = 'Approved'
                    AND l.halfday = 0
                    AND d.attendance_date BETWEEN l.fromdate AND l.todate
                ) THEN 'Leave'
                
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
                
                WHEN EXISTS (
                    SELECT 1
                    FROM leavedb_leavedb l
                    WHERE l.applicantid = m.associatenumber
                    AND l.status = 'Approved'
                    AND l.halfday = 1
                    AND d.attendance_date BETWEEN l.fromdate AND l.todate
                ) THEN 'HF'
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
                        WHEN p.punch_in IS NOT NULL AND EXTRACT(EPOCH FROM p.punch_in::time) <= EXTRACT(EPOCH FROM (
                            SELECT e.start_date_time 
                            FROM exception_requests e 
                            WHERE e.submitted_by = m.associatenumber
                            AND e.status = 'Approved'
                            AND e.exception_type = 'entry'
                            AND e.sub_exception_type = 'late-entry'
                            AND d.attendance_date = DATE(e.start_date_time)
                        )::time) THEN 'Exc.'
                        WHEN p.punch_in IS NOT NULL THEN 'Exc.L'
                        ELSE NULL
                    END
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
                        ELSE NULL
                    END
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

            CASE
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
            DATE_TRUNC('month', TO_DATE('$startMonth', 'YYYY-MM'))::DATE <= COALESCE(DATE_TRUNC('month', m.effectivedate)::DATE, NOW())
            AND DATE_TRUNC('month', TO_DATE('$endMonth', 'YYYY-MM'))::DATE >= DATE_TRUNC('month', m.doj)::DATE
            $engagementCondition
            $teacherCondition
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
        h.holiday_dates,
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
        ON ad.associatenumber = h.associatenumber
    WHERE 
        mode = 'Offline'
        AND grade!='D'
        AND m.doj <= '$endDate'::DATE
    GROUP BY 
        m.associatenumber, m.fullname, m.engagement, h.holiday_dates
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
} else {
    // Set empty data when no months are selected
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
    <link href="../assets_new/css/style.css?v=1.0.0" rel="stylesheet">

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
                multiple: true,
                width: '100%'
            });

            // Prepopulate selected values from GET
            <?php if (!empty($_GET['teacher_id_viva'])): ?>
                var selectedTeachers = <?php echo json_encode($_GET['teacher_id_viva']); ?>;
                var teacherData = <?php echo json_encode($teachers); ?>;

                // Add selected options
                $.each(teacherData, function(index, teacher) {
                    var option = new Option(teacher.associatenumber + ' - ' + teacher.fullname, teacher.associatenumber, true, true);
                    $('#teacher_id_viva').append(option).trigger('change');
                });
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

        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 10px;
        }

        .status-indicator.yellow {
            background-color: #FFBF00;
        }

        .status-indicator.green {
            background-color: #28a745;
        }

        .status-indicator.red {
            background-color: #dc3545;
        }

        .send-link {
            color: #888;
            text-decoration: none;
            font-weight: normal;
            cursor: pointer;
            opacity: 0.6;
            transition: opacity 0.3s;
        }

        .send-link:hover {
            color: #555;
            opacity: 1;
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
                                    <?php if ($showData && !empty($attendanceData)): ?>
                                        Record count:&nbsp;<?php echo $associateNumberCount ?>
                                    <?php endif; ?>
                                </div>
                                <form action="" method="GET" class="row g-2 align-items-center" id="search_form">
                                    <div class="row">
                                        <?php if ($role == 'Admin') { ?>
                                            <!-- Engagement Dropdown -->
                                            <div class="col-md-3 col-lg-2">
                                                <div class="form-group">
                                                    <select name="engagement" id="engagement" class="form-select">
                                                        <option value="">All Engagements</option>
                                                        <option value="Employee" <?php echo (isset($_GET['engagement']) && $_GET['engagement'] == 'Employee') ? 'selected' : ''; ?>>Employee</option>
                                                        <option value="Intern" <?php echo (isset($_GET['engagement']) && $_GET['engagement'] == 'Intern') ? 'selected' : ''; ?>>Intern</option>
                                                        <option value="Member" <?php echo (isset($_GET['engagement']) && $_GET['engagement'] == 'Member') ? 'selected' : ''; ?>>Member</option>
                                                        <option value="Volunteer" <?php echo (isset($_GET['engagement']) && $_GET['engagement'] == 'Volunteer') ? 'selected' : ''; ?>>Volunteer</option>
                                                    </select>
                                                    <small class="form-text text-muted">Engagement Type</small>
                                                </div>
                                            </div>

                                            <!-- Teacher ID Select2 Dropdown -->
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <select class="form-select" id="teacher_id_viva" name="teacher_id_viva[]" multiple="multiple">
                                                        <!-- Leave empty; Select2 will load options dynamically -->
                                                    </select>
                                                    <small class="form-text text-muted">Associate ID</small>
                                                </div>
                                            </div>
                                        <?php } ?>

                                        <!-- Start Month Input - Empty by default -->
                                        <div class="col-12 col-sm-2">
                                            <div class="form-group">
                                                <input type="month" name="start_month" id="start_month" class="form-control"
                                                    value="<?php echo isset($_GET['start_month']) ? htmlspecialchars($_GET['start_month']) : ''; ?>">
                                                <small class="form-text text-muted">Start Month</small>
                                            </div>
                                        </div>

                                        <!-- End Month Input - Empty by default -->
                                        <div class="col-12 col-sm-2">
                                            <div class="form-group">
                                                <input type="month" name="end_month" id="end_month" class="form-control"
                                                    value="<?php echo isset($_GET['end_month']) ? htmlspecialchars($_GET['end_month']) : ''; ?>">
                                                <small class="form-text text-muted">End Month</small>
                                            </div>
                                        </div>

                                        <!-- Submit Button -->
                                        <div class="col-12 col-sm-2">
                                            <button type="submit" name="search_by_id" id="search_by_id" class="btn btn-success" style="outline: none;">
                                                <i class="bi bi-search"></i> <span id="button_text">Search</span>
                                            </button>
                                            <!-- </div> -->

                                            <?php if ($role == 'Admin'): ?>
                                                <!-- <div class="col-12 col-sm-2"> -->
                                                <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-secondary" style="outline: none;">
                                                    <i class="bi bi-arrow-counterclockwise"></i> Clear
                                                </a>
                                        </div>
                                    <?php endif; ?>
                                    </div>
                                </form>

                                <?php if ($showData && !empty($attendanceData)): ?>
                                    <div class="row align-items-center mt-3">
                                        <div class="col-6">
                                            <?php
                                            $startDateTime = DateTime::createFromFormat('Y-m', $startMonth);
                                            $endDateTime = DateTime::createFromFormat('Y-m', $endMonth);
                                            if ($startDateTime !== false && $endDateTime !== false):
                                            ?>
                                                You are viewing data for
                                                <span class="blink-text">
                                                    <?= $startDateTime->format('M Y') ?> to <?= $endDateTime->format('M Y') ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="table-responsive mt-5 mb-3">
                                    <?php if (empty($startMonth) || empty($endMonth)): ?>
                                        <div class="alert alert-info mt-3">
                                            <h5><i class="bi bi-calendar-event"></i> Select Date Range</h5>
                                            <p>Please select both <strong>Start Month</strong> and <strong>End Month</strong> from the dropdowns above and click <strong>"Search"</strong> to view the timesheet data.</p>
                                        </div>
                                    <?php elseif ($showData && empty($attendanceData)): ?>
                                        <div class="alert alert-warning mt-3">
                                            <i class="bi bi-exclamation-triangle"></i> No timesheet data found for the selected date range.
                                        </div>
                                    <?php else: ?>
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
                                    <?php endif; ?>
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
        if (startMonthInput) {
            startMonthInput.setAttribute('max', maxMonth);
        }
        if (endMonthInput) {
            endMonthInput.setAttribute('max', maxMonth);
        }

        // Initialize min/max attributes if values are pre-selected
        if (startMonthInput && startMonthInput.value) {
            endMonthInput.setAttribute('min', startMonthInput.value);
        }

        if (endMonthInput && endMonthInput.value) {
            startMonthInput.setAttribute('max', endMonthInput.value);
        }

        // Update the min and max attributes based on selected start_month
        if (startMonthInput) {
            startMonthInput.addEventListener('change', function() {
                const selectedStartMonth = this.value;
                endMonthInput.setAttribute('min', selectedStartMonth);
            });
        }

        // Update the min and max attributes based on selected end_month
        if (endMonthInput) {
            endMonthInput.addEventListener('change', function() {
                const selectedEndMonth = this.value;
                startMonthInput.setAttribute('max', selectedEndMonth);
            });
        }
    </script>
    <script>
        const searchForm = document.getElementById('search_form');
        const searchButton = document.getElementById('search_by_id');
        const buttonText = document.getElementById('button_text');

        if (searchForm) {
            searchForm.addEventListener('submit', function() {
                // Change the button text to "Loading..."
                buttonText.textContent = 'Loading...';
                // Disable the button to prevent multiple submissions
                searchButton.setAttribute('disabled', true);
            });
        }
    </script>
</body>

</html>