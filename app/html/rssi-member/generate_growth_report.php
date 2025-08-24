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
$metric = isset($_POST['metric']) ? pg_escape_string($con, $_POST['metric']) : 'height';
$from_date = isset($_POST['from_date']) ? pg_escape_string($con, $_POST['from_date']) : date('Y-m-d', strtotime('-1 year'));
$to_date = isset($_POST['to_date']) ? pg_escape_string($con, $_POST['to_date']) : date('Y-m-d');
$compare_years = isset($_POST['compare_academic_years']);
$current_script = isset($_POST['current_script']) ? pg_escape_string($con, $_POST['current_script']) : '';

// Build base query
if ($current_script === 'community_care') {
    $query = "SELECT 
                p.id AS student_id,
                p.name AS studentname,
                p.contact_number AS contact,
                'N/A' AS class,
                sh.record_date,
                sh.height_cm,
                sh.weight_kg,
                sh.bmi
              FROM student_health_records sh
              JOIN public_health_records p ON sh.student_id = p.id::varchar
              WHERE sh.record_date BETWEEN '$from_date' AND '$to_date'";
} else {
    $query = "SELECT 
                s.student_id,
                s.studentname,
                s.class,
                s.contact,
                sh.record_date,
                sh.height_cm,
                sh.weight_kg,
                sh.bmi
              FROM student_health_records sh
              JOIN rssimyprofile_student s ON sh.student_id = s.student_id
              WHERE s.filterstatus = 'Active'
                AND sh.record_date BETWEEN '$from_date' AND '$to_date'";

    // Only apply class filter if not community_care
    if (!empty($class)) {
        $query .= " AND s.class = '$class'";
    }
}

// Use appropriate ORDER BY clause
if ($current_script === 'community_care') {
    $query .= " ORDER BY studentname, record_date";
} else {
    $query .= " ORDER BY s.class, s.studentname, sh.record_date";
}

$result = pg_query($con, $query);

// Generate CSV report
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=growth_report_' . date('Ymd') . '.csv');

$output = fopen('php://output', 'w');

// Header row
fputcsv($output, [
    'Student ID',
    'Student Name',
    'Contact Number',
    'Class',
    'Record Date',
    'Height (cm)',
    'Weight (kg)',
    'BMI'
]);

// Data rows
while ($row = pg_fetch_assoc($result)) {
    fputcsv($output, [
        $row['student_id'],
        $row['studentname'],
        $row['contact'],
        $row['class'],
        $row['record_date'],
        $row['height_cm'],
        $row['weight_kg'],
        $row['bmi']
    ]);
}

fclose($output);
exit;
