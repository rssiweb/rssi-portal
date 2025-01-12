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
            m.fullname,
            d.attendance_date,
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
                ) THEN 'HF'
                WHEN p.punch_in IS NOT NULL THEN
                    CASE
                        WHEN ds.reporting_time IS NULL THEN 'NA'
                        WHEN EXTRACT(EPOCH FROM p.punch_in::time) > EXTRACT(EPOCH FROM ds.reporting_time) + 600 THEN 'L'
                        WHEN EXTRACT(EPOCH FROM p.punch_in::time) > EXTRACT(EPOCH FROM ds.reporting_time) THEN 'W'
                        ELSE NULL
                    END
                ELSE NULL
            END AS status
        FROM
            date_range d
        CROSS JOIN rssimyaccount_members m
        LEFT JOIN PunchInOut p
            ON m.associatenumber = p.user_id AND p.punch_date = d.attendance_date
        LEFT JOIN DynamicSchedule ds
            ON m.associatenumber = ds.associate_number
            AND d.attendance_date BETWEEN ds.start_date AND ds.end_date
        WHERE
            m.filterstatus = 'Active'
            OR (m.filterstatus = 'Inactive' AND DATE_TRUNC('month', m.effectivedate)::DATE >= DATE_TRUNC('month', TO_DATE('$month', 'YYYY-MM'))::DATE)
    )
    SELECT
        associatenumber,
        fullname,
        COUNT(*) FILTER (WHERE status = 'L') AS late_count,
        STRING_AGG(CASE WHEN status = 'L' THEN attendance_date::text ELSE NULL END, ', ') AS late_dates,
        COUNT(*) FILTER (WHERE status = 'W') AS warning_count,
        STRING_AGG(CASE WHEN status = 'W' THEN attendance_date::text ELSE NULL END, ', ') AS warning_dates,
        COUNT(*) FILTER (WHERE status = 'Leave') AS leave_count,
        STRING_AGG(CASE WHEN status = 'Leave' THEN attendance_date::text ELSE NULL END, ', ') AS leave_dates,
        COUNT(*) FILTER (WHERE status = 'HF') AS halfday_count,
        STRING_AGG(CASE WHEN status = 'HF' THEN attendance_date::text ELSE NULL END, ', ') AS halfday_dates
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
    </tr>
    </thead>";

    while ($row = pg_fetch_assoc($result)) {
        // Format dates as dd/mm
        $late_dates_formatted = !empty($row['late_dates']) ? implode(', ', array_map(function($date) {
            return date('d', strtotime($date));
        }, explode(', ', $row['late_dates']))) : '';

        $warning_dates_formatted = !empty($row['warning_dates']) ? implode(', ', array_map(function($date) {
            return date('d', strtotime($date));
        }, explode(', ', $row['warning_dates']))) : '';

        $leave_dates_formatted = !empty($row['leave_dates']) ? implode(', ', array_map(function($date) {
            return date('d', strtotime($date));
        }, explode(', ', $row['leave_dates']))) : '';

        $halfday_dates_formatted = !empty($row['halfday_dates']) ? implode(', ', array_map(function($date) {
            return date('d', strtotime($date));
        }, explode(', ', $row['halfday_dates']))) : '';

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
        echo "</tr>";
    }
    echo "</table>";

    // Close the connection
    pg_close($con);
    ?>
</div>

</body>
</html>
