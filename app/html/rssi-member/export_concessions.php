<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    die("Unauthorized access");
}

// Get export parameters
$tab = $_POST['tab'] ?? 'billable';
$filters = $_POST['filters'] ?? [];

// Reuse your existing query building logic with filters
$query = "SELECT sc.*, 
                 s.studentname, 
                 s.class, 
                 u.fullname as created_by_name,
                 TO_CHAR(sc.effective_from, 'Mon YYYY') as formatted_from_date,
                 CASE WHEN sc.effective_until IS NULL THEN 'Indefinite'
                      ELSE TO_CHAR(sc.effective_until, 'Mon YYYY') 
                 END as formatted_until_date
          FROM student_concessions sc
          JOIN rssimyprofile_student s ON sc.student_id = s.student_id
          JOIN rssimyaccount_members u ON sc.created_by = u.associatenumber";

// Add WHERE clauses based on filters
$whereClauses = [];
$params = [];

// Apply tab filter
if ($tab === 'billable') {
    $whereClauses[] = "sc.concession_category != 'non_billable'";
} else {
    $whereClauses[] = "sc.concession_category = 'non_billable'";
}

// Apply other filters (same as in your main page)
if (!empty($filters['student_id'])) {
    $whereClauses[] = "sc.student_id = ANY($" . (count($params) + 1) . ")";
    $params[] = toPgArray($filters['student_id'], $con);
}

if (!empty($filters['concession_category'])) {
    $whereClauses[] = "sc.concession_category = ANY($" . (count($params) + 1) . ")";
    $params[] = toPgArray($filters['concession_category'], $con);
}

// Handle academic year filter
$yearConditions = [];
if (!empty($filters['academic_year'])) {
    foreach ($filters['academic_year'] as $year) {
        if ($year === 'Indefinite') {
            $yearConditions[] = "sc.effective_until IS NULL";
        } else {
            $startYear = substr($year, 0, 4);
            $endYear = substr($year, 5, 4);
            $yearConditions[] = "(sc.effective_from >= '$startYear-04-01' AND sc.effective_from < '$endYear-04-01')";
        }
    }
} else {
    // Default to current academic year when no filter is set
    $currentYear = date('Y');
    $currentMonth = date('m');
    $academicYear = ($currentMonth >= 4) ? $currentYear . '-' . ($currentYear + 1) : ($currentYear - 1) . '-' . $currentYear;
    $startYear = substr($academicYear, 0, 4);
    $endYear = substr($academicYear, 5, 4);
    $yearConditions[] = "(sc.effective_from >= '$startYear-04-01' AND sc.effective_from < '$endYear-04-01')";
}

if (!empty($yearConditions)) {
    $whereClauses[] = "(" . implode(" OR ", $yearConditions) . ")";
}

if (!empty($whereClauses)) {
    $query .= " WHERE " . implode(" AND ", $whereClauses);
}

$query .= " ORDER BY sc.created_at DESC";

// Execute query
$result = pg_query_params($con, $query, $params);
$data = pg_fetch_all($result);

// Generate CSV
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="concessions_' . $tab . '_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

// CSV header
$headers = [
    'Student ID',
    'Student Name',
    'Class',
    'Category',
    'Amount',
    'Academic Year',
    'Effective From',
    'Effective Until',
    'Reason',
    'Created By',
    'Created At'
];
fputcsv($output, $headers);

// CSV data rows
foreach ($data as $row) {
    $csvRow = [
        $row['student_id'],
        $row['studentname'],
        $row['class'],
        ucwords(str_replace('_', ' ', $row['concession_category'])),
        number_format($row['concession_amount'], 2), // Amount without ₹ symbol
        getAcademicYear($row['effective_from']),
        $row['formatted_from_date'],
        $row['formatted_until_date'],
        $row['reason'],
        $row['created_by_name'],
        date('d M Y H:i', strtotime($row['created_at']))
    ];
    fputcsv($output, $csvRow);
}

fclose($output);
exit;

// Reuse your existing functions
function getAcademicYear($date) {
    if (!$date) return 'Indefinite';
    $date = new DateTime($date);
    $year = $date->format('Y');
    $month = $date->format('m');

    if ($month >= 4) {
        return $year . '-' . ($year + 1);
    } else {
        return ($year - 1) . '-' . $year;
    }
}

function toPgArray($array, $con) {
    return '{' . implode(',', array_map(fn($v) => pg_escape_string($con, $v), $array)) . '}';
}