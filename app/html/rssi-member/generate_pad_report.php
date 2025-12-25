<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

// Get report parameters
$class = isset($_POST['class']) ? pg_escape_string($con, $_POST['class']) : '';
$report_type = isset($_POST['report_type']) ? pg_escape_string($con, $_POST['report_type']) : 'monthly';
$from_date = isset($_POST['from_date']) ? pg_escape_string($con, $_POST['from_date']) : date('Y-m-d', strtotime('-1 year'));
$to_date = isset($_POST['to_date']) ? pg_escape_string($con, $_POST['to_date']) : date('Y-m-d');
$current_script = isset($_POST['current_script']) ? pg_escape_string($con, $_POST['current_script']) : '';

if ($current_script == 'community_care') {
    $table = 'public_health_records';
    $alias = 's';
    $id_col = "CAST($alias.id AS varchar)";
    $name_col = "$alias.name";
    $has_class = false;
    $has_filterstatus = false;
} else {
    $table = 'rssimyprofile_student';
    $alias = 's';
    $id_col = "$alias.student_id";
    $name_col = "$alias.studentname";
    $has_class = true;
    $has_filterstatus = true;
}

switch ($report_type) {
    case 'yearly':
        $query = "SELECT 
                    EXTRACT(YEAR FROM pd.date) as year,
                    COUNT(*) as distributions,
                    SUM(pd.quantity_distributed) as total_pads,
                    COUNT(DISTINCT pd.distributed_to) as students_served
                  FROM stock_out pd
                  JOIN $table $alias ON pd.distributed_to = $id_col
                  WHERE $alias.gender = 'Female'
                  AND pd.item_distributed = 149
                  AND pd.date BETWEEN '$from_date' AND '$to_date'";
        if ($has_filterstatus) {
            $query .= " AND $alias.filterstatus = 'Active'";
        }
        if ($has_class && !empty($class)) {
            $query .= " AND $alias.class = '$class'";
        }
        $query .= " GROUP BY year
                    ORDER BY year";
        break;

    case 'student':
        $query = "SELECT 
                $id_col AS student_id,
                $name_col AS studentname,";
        if ($has_class) {
            $query .= " $alias.class,";
        } else {
            // If no class column, show 'N/A' as class
            $query .= " 'N/A' AS class,";
        }
        $query .= "
                COUNT(*) as distributions,
                SUM(pd.quantity_distributed) as total_pads,
                MIN(pd.date) as first_distribution,
                MAX(pd.date) as last_distribution
              FROM stock_out pd
              JOIN $table $alias ON pd.distributed_to = $id_col
              WHERE $alias.gender = 'Female'
              AND pd.item_distributed = 149
              AND pd.date BETWEEN '$from_date' AND '$to_date'";
        if ($has_filterstatus) {
            $query .= " AND $alias.filterstatus = 'Active'";
        }
        if ($has_class && !empty($class)) {
            $query .= " AND $alias.class = '$class'";
        }
        $query .= " GROUP BY $id_col, $name_col";
        if ($has_class) {
            $query .= ", $alias.class";
        }
        $query .= " ORDER BY ";
        if ($has_class) {
            $query .= "$alias.class, ";
        }
        $query .= "$name_col";
        break;

    case 'monthly':
    default:
        $query = "SELECT 
                    EXTRACT(YEAR FROM pd.date) as year,
                    EXTRACT(MONTH FROM pd.date) as month,
                    COUNT(*) as distributions,
                    SUM(pd.quantity_distributed) as total_pads,
                    COUNT(DISTINCT pd.distributed_to) as students_served
                  FROM stock_out pd
                  JOIN $table $alias ON pd.distributed_to = $id_col
                  WHERE $alias.gender = 'Female'
                  AND pd.item_distributed = 149
                  AND pd.date BETWEEN '$from_date' AND '$to_date'";
        if ($has_filterstatus) {
            $query .= " AND $alias.filterstatus = 'Active'";
        }
        if ($has_class && !empty($class)) {
            $query .= " AND $alias.class = '$class'";
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
