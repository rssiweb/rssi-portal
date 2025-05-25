<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

// Get report parameters
$class = isset($_POST['class']) ? pg_escape_string($con, $_POST['class']) : '';
$report_type = isset($_POST['report_type']) ? pg_escape_string($con, $_POST['report_type']) : 'summary';
$from_date = isset($_POST['from_date']) ? pg_escape_string($con, $_POST['from_date']) : date('Y-m-d', strtotime('-1 year'));
$to_date = isset($_POST['to_date']) ? pg_escape_string($con, $_POST['to_date']) : date('Y-m-d');
$current_script = isset($_POST['current_script']) ? pg_escape_string($con, $_POST['current_script']) : '';

// Build query based on report type
switch ($report_type) {
    case 'detailed':
        if ($current_script === 'community_care') {
            $query = "SELECT 
                        p.id AS student_id,
                        p.name AS studentname,
                        'N/A' AS class,
                        pr.record_date,
                        pr.cycle_start_date,
                        pr.cycle_end_date,
                        pr.symptoms,
                        pr.notes
                      FROM student_period_records pr
                      JOIN public_health_records p ON pr.student_id = p.id::varchar
                      WHERE pr.cycle_start_date BETWEEN '$from_date' AND '$to_date'";
        } else {
            $query = "SELECT 
                        s.student_id,
                        s.studentname,
                        s.class,
                        pr.record_date,
                        pr.cycle_start_date,
                        pr.cycle_end_date,
                        pr.symptoms,
                        pr.notes
                      FROM student_period_records pr
                      JOIN rssimyprofile_student s ON pr.student_id = s.student_id
                      WHERE s.gender = 'Female'
                      AND s.filterstatus='Active'
                      AND pr.cycle_start_date BETWEEN '$from_date' AND '$to_date'";
            if (!empty($class)) {
                $query .= " AND s.class = '$class'";
            }
        }
        $query .= " ORDER BY studentname, pr.cycle_start_date";
        break;

    case 'irregularities':
        if ($current_script === 'community_care') {
            $query = "WITH cycle_diffs AS (
                        SELECT 
                            pr.student_id,
                            pr.cycle_start_date,
                            DATE_PART('day', pr.cycle_start_date::timestamp - 
                                LAG(pr.cycle_start_date::timestamp) OVER (
                                    PARTITION BY pr.student_id 
                                    ORDER BY pr.cycle_start_date
                                )
                            ) as days_since_last_cycle
                        FROM student_period_records pr
                        JOIN public_health_records p ON pr.student_id = p.id::varchar
                        WHERE pr.cycle_start_date BETWEEN '$from_date' AND '$to_date'
                      )
                      SELECT 
                          p.id AS student_id,
                          p.name AS studentname,
                          'N/A' AS class,
                          COUNT(*) as cycle_count,
                          MIN(cd.cycle_start_date) as first_record,
                          MAX(cd.cycle_start_date) as last_record,
                          AVG(cd.days_since_last_cycle) as avg_cycle_length
                      FROM cycle_diffs cd
                      JOIN public_health_records p ON cd.student_id = p.id::varchar
                      WHERE cd.days_since_last_cycle IS NOT NULL
                      GROUP BY p.id, p.name
                      HAVING COUNT(*) > 1
                      ORDER BY avg_cycle_length DESC";
        } else {
            $query = "WITH cycle_diffs AS (
                        SELECT 
                            pr.student_id,
                            pr.cycle_start_date,
                            DATE_PART('day', pr.cycle_start_date::timestamp - 
                                LAG(pr.cycle_start_date::timestamp) OVER (
                                    PARTITION BY pr.student_id 
                                    ORDER BY pr.cycle_start_date
                                )
                            ) as days_since_last_cycle
                        FROM student_period_records pr
                        JOIN rssimyprofile_student s ON pr.student_id = s.student_id
                        WHERE s.gender = 'Female'
                        AND s.filterstatus='Active'
                        AND pr.cycle_start_date BETWEEN '$from_date' AND '$to_date'
                      )
                      SELECT 
                          s.student_id,
                          s.studentname,
                          s.class,
                          COUNT(*) as cycle_count,
                          MIN(cd.cycle_start_date) as first_record,
                          MAX(cd.cycle_start_date) as last_record,
                          AVG(cd.days_since_last_cycle) as avg_cycle_length
                      FROM cycle_diffs cd
                      JOIN rssimyprofile_student s ON cd.student_id = s.student_id
                      WHERE cd.days_since_last_cycle IS NOT NULL
                      GROUP BY s.student_id, s.studentname, s.class
                      HAVING COUNT(*) > 1
                      ORDER BY avg_cycle_length DESC";
        }
        break;

    case 'summary':
    default:
        if ($current_script === 'community_care') {
            $query = "SELECT 
                        'N/A' AS class,
                        COUNT(DISTINCT p.id) as student_count,
                        COUNT(*) as record_count,
                        MIN(pr.cycle_start_date) as earliest_date,
                        MAX(pr.cycle_start_date) as latest_date
                      FROM student_period_records pr
                      JOIN public_health_records p ON pr.student_id = p.id::varchar
                      WHERE pr.cycle_start_date BETWEEN '$from_date' AND '$to_date'";
        } else {
            $query = "SELECT 
                        s.class,
                        COUNT(DISTINCT s.student_id) as student_count,
                        COUNT(*) as record_count,
                        MIN(pr.cycle_start_date) as earliest_date,
                        MAX(pr.cycle_start_date) as latest_date
                      FROM student_period_records pr
                      JOIN rssimyprofile_student s ON pr.student_id = s.student_id
                      WHERE s.gender = 'Female'
                      AND s.filterstatus='Active'
                      AND pr.cycle_start_date BETWEEN '$from_date' AND '$to_date'";
            if (!empty($class)) {
                $query .= " AND s.class = '$class'";
            }
            $query .= " GROUP BY s.class
                        ORDER BY s.class";
        }
        break;
}

$result = pg_query($con, $query);
if (!$result) {
    die("Query failed: " . pg_last_error($con));
}

// Generate CSV report
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=period_report_' . $report_type . '_' . date('Ymd') . '.csv');

$output = fopen('php://output', 'w');

// Header row
if ($report_type == 'detailed') {
    fputcsv($output, [
        'Student ID',
        'Student Name',
        'Class',
        'Record Date',
        'Cycle Start',
        'Cycle End',
        'Symptoms',
        'Notes'
    ]);
} elseif ($report_type == 'irregularities') {
    fputcsv($output, [
        'Student ID',
        'Student Name',
        'Class',
        'Cycle Count',
        'First Record',
        'Last Record',
        'Avg Cycle Length (days)'
    ]);
} else {
    fputcsv($output, [
        'Class',
        'Students',
        'Records',
        'Earliest Date',
        'Latest Date'
    ]);
}

// Data rows
if (pg_num_rows($result) > 0) {
    while ($row = pg_fetch_assoc($result)) {
        fputcsv($output, $row);
    }
} else {
    fputcsv($output, ['No records found for the selected criteria']);
}

fclose($output);
exit;
