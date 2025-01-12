<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}
validation();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Table</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
</head>

<body>

    <div class="container mt-4">
        <form method="GET">
            <div class="mb-3">
                <label for="get_month">Select Month</label>
                <input type="month" id="get_month" name="get_month" value="<?php echo isset($_GET['get_month']) ? $_GET['get_month'] : date('Y-m'); ?>">
                <button type="submit">Filter</button>
            </div>

        </form>

        <?php
        // Get the selected month or use the current month
        $month = isset($_GET['get_month']) ? $_GET['get_month'] : date('Y-m');

        // Calculate the start and end dates of the month
        $startDate = date("Y-m-01", strtotime($month));
        $endDate = date("Y-m-t", strtotime($month));

        // SQL Query to calculate counts and concatenate dates
        $query = "
WITH date_range AS (
    SELECT generate_series(
        '$startDate'::date,
        '$endDate'::date,
        '1 day'::interval
    ) AS attendance_date
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
        " . ($role !== 'Admin' ? "AND m.associatenumber = '$associatenumber'" : "") . "
)
SELECT
    associatenumber,
    fullname,
    COUNT(*) FILTER (WHERE late_status = 'L') AS late_count,
    STRING_AGG(CASE WHEN late_status = 'L' THEN attendance_date::text ELSE NULL END, ', ') AS late_dates,
    COUNT(*) FILTER (WHERE late_status = 'W') AS warning_count,
    STRING_AGG(CASE WHEN late_status = 'W' THEN attendance_date::text ELSE NULL END, ', ') AS warning_dates,
    COUNT(*) FILTER (WHERE late_status = 'Leave') AS leave_count,
    STRING_AGG(CASE WHEN late_status = 'Leave' THEN attendance_date::text ELSE NULL END, ', ') AS leave_dates,
    COUNT(*) FILTER (WHERE late_status = 'HF') AS halfday_count,
    STRING_AGG(CASE WHEN late_status = 'HF' THEN attendance_date::text ELSE NULL END, ', ') AS halfday_dates,
    COUNT(*) FILTER (WHERE exception_status = 'Exc.' OR exit_status = 'Exc.') AS exception_count,
    STRING_AGG(CASE WHEN exception_status = 'Exc.' OR exit_status = 'Exc.' THEN attendance_date::text ELSE NULL END, ', ') AS exception_dates
FROM attendance_data
GROUP BY associatenumber, fullname
ORDER BY associatenumber;
";
        $result = pg_query($con, $query);

        if (!$result) {
            echo "Query failed: " . pg_last_error($con);
            exit();
        }

        // Display the results
        echo "<table class='table-bordered'>";
        echo "<thead>
    <tr>
        <th>Associate Number</th>
        <th>Full Name</th>
        <th>Late Count</th>
        <th>Late Dates</th>
        <th>Warning Count</th>
        <th>Warning Dates</th>
        <th>Leave Count</th>
        <th>Leave Dates</th>
        <th>Halfday Count</th>
        <th>Halfday Dates</th>
        <th>Exc Count</th>
        <th>Exc Dates</th>
    </tr>
    </thead>";

        while ($row = pg_fetch_assoc($result)) {
            // Format dates as dd/mm
            $late_dates_formatted = !empty($row['late_dates']) ? implode(', ', array_map(function ($date) {
                return date('d', strtotime($date));
            }, explode(', ', $row['late_dates']))) : '';

            $warning_dates_formatted = !empty($row['warning_dates']) ? implode(', ', array_map(function ($date) {
                return date('d', strtotime($date));
            }, explode(', ', $row['warning_dates']))) : '';

            $leave_dates_formatted = !empty($row['leave_dates']) ? implode(', ', array_map(function ($date) {
                return date('d', strtotime($date));
            }, explode(', ', $row['leave_dates']))) : '';

            $halfday_dates_formatted = !empty($row['halfday_dates']) ? implode(', ', array_map(function ($date) {
                return date('d', strtotime($date));
            }, explode(', ', $row['halfday_dates']))) : '';
            $exc_dates_formatted = !empty($row['exception_dates']) ? implode(', ', array_map(function ($date) {
                return date('d', strtotime($date));
            }, explode(', ', $row['exception_dates']))) : '';

            echo "<tr>";
            echo "<td>" . $row['associatenumber'] . "</td>";
            echo "<td>" . $row['fullname'] . "</td>";
            echo "<td>" . $row['late_count'] . "</td>";
            echo "<td>" . $late_dates_formatted . "</td>";
            echo "<td>" . $row['warning_count'] . "</td>";
            echo "<td>" . $warning_dates_formatted . "</td>";
            echo "<td>" . $row['leave_count'] . "</td>";
            echo "<td>" . $leave_dates_formatted . "</td>";
            echo "<td>" . $row['halfday_count'] . "</td>";
            echo "<td>" . $halfday_dates_formatted . "</td>";
            echo "<td>" . $row['exception_count'] . "</td>";
            echo "<td>" . $exc_dates_formatted . "</td>";
            echo "</tr>";
        }
        echo "</table>";

        // Close the connection
        pg_close($con);
        ?>
    </div>

</body>

</html>