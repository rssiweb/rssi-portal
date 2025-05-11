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
$report_type = isset($_POST['report_type']) ? pg_escape_string($con, $_POST['report_type']) : 'monthly';
$from_date = isset($_POST['from_date']) ? pg_escape_string($con, $_POST['from_date']) : date('Y-m-d', strtotime('-1 year'));
$to_date = isset($_POST['to_date']) ? pg_escape_string($con, $_POST['to_date']) : date('Y-m-d');

// Build query based on report type
switch ($report_type) {
    case 'yearly':
        $query = "SELECT 
                    EXTRACT(YEAR FROM pd.distribution_date) as year,
                    COUNT(*) as distributions,
                    SUM(pd.quantity) as total_pads,
                    COUNT(DISTINCT pd.student_id) as students_served
                  FROM sanitary_pad_distribution pd
                  JOIN rssimyprofile_student s ON pd.student_id = s.student_id
                  WHERE s.gender = 'Female'
                  AND s.filterstatus='Active'
                  AND pd.distribution_date BETWEEN '$from_date' AND '$to_date'";
        if (!empty($class)) {
            $query .= " AND s.class = '$class'";
        }
        $query .= " GROUP BY year
                  ORDER BY year";
        break;
    
    case 'student':
        $query = "SELECT 
                    s.student_id,
                    s.studentname,
                    s.class,
                    COUNT(*) as distributions,
                    SUM(pd.quantity) as total_pads,
                    MIN(pd.distribution_date) as first_distribution,
                    MAX(pd.distribution_date) as last_distribution
                  FROM sanitary_pad_distribution pd
                  JOIN rssimyprofile_student s ON pd.student_id = s.student_id
                  WHERE s.gender = 'Female'
                  AND s.filterstatus='Active'
                  AND pd.distribution_date BETWEEN '$from_date' AND '$to_date'";
        if (!empty($class)) {
            $query .= " AND s.class = '$class'";
        }
        $query .= " GROUP BY s.student_id, s.studentname, s.class
                  ORDER BY s.class, s.studentname";
        break;
    
    case 'monthly':
    default:
        $query = "SELECT 
                    EXTRACT(YEAR FROM pd.distribution_date) as year,
                    EXTRACT(MONTH FROM pd.distribution_date) as month,
                    COUNT(*) as distributions,
                    SUM(pd.quantity) as total_pads,
                    COUNT(DISTINCT pd.student_id) as students_served
                  FROM sanitary_pad_distribution pd
                  JOIN rssimyprofile_student s ON pd.student_id = s.student_id
                  WHERE s.gender = 'Female'
                  AND s.filterstatus='Active'
                  AND pd.distribution_date BETWEEN '$from_date' AND '$to_date'";
        if (!empty($class)) {
            $query .= " AND s.class = '$class'";
        }
        $query .= " GROUP BY year, month
                  ORDER BY year, month";
        break;
}

$result = pg_query($con, $query);

// Generate CSV report
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=pad_report_' . $report_type . '_' . date('Ymd') . '.csv');

$output = fopen('php://output', 'w');

// Header row
if ($report_type == 'student') {
    fputcsv($output, [
        'Student ID', 
        'Student Name', 
        'Class', 
        'Distributions', 
        'Total Pads', 
        'First Distribution', 
        'Last Distribution'
    ]);
} elseif ($report_type == 'yearly') {
    fputcsv($output, [
        'Year', 
        'Distributions', 
        'Total Pads', 
        'Students Served'
    ]);
} else {
    fputcsv($output, [
        'Year', 
        'Month', 
        'Distributions', 
        'Total Pads', 
        'Students Served'
    ]);
}

// Data rows
while ($row = pg_fetch_assoc($result)) {
    if ($report_type == 'monthly') {
        $row['month'] = date('F', mktime(0, 0, 0, $row['month'], 1));
    }
    fputcsv($output, $row);
}

fclose($output);
exit;
?>