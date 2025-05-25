<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("../../util/drive.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

// Set headers for CSV download - must be before any output
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="beneficiaries_' . date('Y-m-d') . '.csv"');

// Create output file pointer
$output = fopen('php://output', 'w');

// CSV header
fputcsv($output, [
    'ID',
    'Name',
    'Mobile',
    'Email',
    'Date of Birth',
    'Gender',
    'Referral Source',
    'Registration Date',
    'Status'
]);

// Get parameters from URL (same as listing page)
$academicYear = isset($_GET['academic_year']) ? $_GET['academic_year'] : null;
$search = isset($_GET['search']) ? pg_escape_string($con, $_GET['search']) : '';

// Validate academic year format (same as listing page)
if ($academicYear && !preg_match('/^\d{4}-\d{4}$/', $academicYear)) {
    die("Invalid academic year format. Please use YYYY-YYYY format.");
}

// Build WHERE clause (same logic as listing page)
$academicCondition = '';
if ($academicYear) {
    list($startYear, $endYear) = explode('-', $academicYear);
    $academicYearEnd = $endYear . '-03-31';

    $academicCondition = "WHERE created_at <= '$academicYearEnd'
                          AND (
                              effectivefrom IS NULL
                              OR effectivefrom > '$academicYearEnd'
                          )";
}

$search_condition = '';
if ($search) {
    $search_condition = (empty($academicCondition) ? 'WHERE' : 'AND') .
        " (name ILIKE '%$search%' OR contact_number LIKE '%$search%' OR email ILIKE '%$search%')";
}

$where_clause = $academicCondition . $search_condition;

// Add registration completed filter if not already in conditions
if (strpos($where_clause, 'WHERE') === false) {
    $where_clause = "WHERE registration_completed = true";
} else {
    $where_clause .= " AND registration_completed = true";
}

// Export query (same filters as listing page but all records)
$export_query = "SELECT 
                    id, 
                    name, 
                    contact_number, 
                    email, 
                    date_of_birth, 
                    gender, 
                    referral_source, 
                    created_at,
                    CASE 
                        WHEN effectivefrom IS NULL THEN 'Active'
                        WHEN effectivefrom > CURRENT_DATE THEN 'Active'
                        ELSE 'Inactive'
                    END as status
                 FROM public_health_records 
                 $where_clause
                 ORDER BY created_at DESC";

$export_result = pg_query($con, $export_query);

if (!$export_result) {
    die("Query failed: " . pg_last_error($con));
}

// Output CSV rows
while ($row = pg_fetch_assoc($export_result)) {
    fputcsv($output, [
        $row['id'],
        $row['name'],
        $row['contact_number'],
        $row['email'],
        $row['date_of_birth'],
        $row['gender'],
        $row['referral_source'],
        $row['created_at'],
        $row['status']
    ]);
}

fclose($output);
exit;
