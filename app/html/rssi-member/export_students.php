<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

// Get ALL filter parameters exactly as they are in the main page
$status_filter = $_GET['status'] ?? 'all';
$search_term = $_GET['search'] ?? '';
$search_mode = filter_var($_GET['search_mode'] ?? false, FILTER_VALIDATE_BOOLEAN);
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$surveyor_filter = $_GET['surveyor'] ?? 'all';

// Build WHERE clause exactly as in the main file
$where_conditions = [];
$params = [];
$param_count = 0;

if ($search_mode) {
    // Search mode - only use search term
    if (!empty($search_term)) {
        $param_count++;
        $where_conditions[] = "(sd.student_name ILIKE $" . $param_count . " OR s.contact ILIKE $" . $param_count . " OR s.parent_name ILIKE $" . $param_count . " OR s.family_id ILIKE $" . $param_count . " OR s.address ILIKE $" . $param_count . " OR rm.fullname ILIKE $" . $param_count . ")";
        $params[] = "%$search_term%";
    }
} else {
    // Filter mode - use all filters
    if ($status_filter !== 'all') {
        $param_count++;
        $where_conditions[] = "sd.status = $" . $param_count;
        $params[] = $status_filter;
    }

    // Also allow search in filter mode
    if (!empty($search_term)) {
        $param_count++;
        $where_conditions[] = "(sd.student_name ILIKE $" . $param_count . " OR s.contact ILIKE $" . $param_count . " OR s.parent_name ILIKE $" . $param_count . " OR s.family_id ILIKE $" . $param_count . " OR s.address ILIKE $" . $param_count . " OR rm.fullname ILIKE $" . $param_count . ")";
        $params[] = "%$search_term%";
    }

    // Add date range filter (only in filter mode)
    if (!empty($date_from)) {
        $param_count++;
        $where_conditions[] = "DATE(s.timestamp) >= $" . $param_count;
        $params[] = $date_from;
    }

    if (!empty($date_to)) {
        $param_count++;
        $where_conditions[] = "DATE(s.timestamp) <= $" . $param_count;
        $params[] = $date_to;
    }

    // Add surveyor filter (only in filter mode)
    if ($surveyor_filter !== 'all') {
        $param_count++;
        $where_conditions[] = "s.surveyor_id = $" . $param_count;
        $params[] = $surveyor_filter;
    }
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get ALL filtered records (no LIMIT for export)
$query = "SELECT s.*, sd.id as student_id, sd.student_name, sd.age, sd.gender, sd.grade, sd.status as student_status,
                 sd.already_going_school, sd.school_type, sd.already_coaching, sd.coaching_name,
                 rm.fullname AS surveyor_name
          FROM survey_data s 
          LEFT JOIN student_data sd ON s.family_id = sd.family_id 
          LEFT JOIN rssimyaccount_members rm ON s.surveyor_id = rm.associatenumber
          $where_clause 
          ORDER BY s.timestamp DESC";

$result = pg_query_params($con, $query, $params);

if (!$result) {
    die("Error fetching data for export: " . pg_last_error($con));
}

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="survey_data_export_' . date('Y-m-d_H-i-s') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for UTF-8 to help Excel with special characters
fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

// CSV headers
$headers = array(
    'SL No',
    'Family ID',
    'Student Name',
    'Age',
    'Gender', 
    'Grade',
    'Parent Name',
    'Contact',
    'Address',
    'Surveyor Name',
    'Timestamp',
    'Student Status',
    'Earning Source',
    'CSA Services Needed',
    'Job Assistance Needed',
    'Already Going to School',
    'School Type',
    'Already Coaching',
    'Coaching Name'
);

fputcsv($output, $headers);

$counter = 1;
while ($row = pg_fetch_assoc($result)) {
    // Format earning source
    $earning_source = ($row['earning_source'] === "other") ? 
        ($row['other_earning_source_input'] ?? $row['earning_source']) : 
        $row['earning_source'];
    
    // Format CSA services needed (handle array/string)
    $csa_services = '';
    if (!empty($row['services_needed'])) {
        try {
            $services = $row['services_needed'];
            if (is_string($services) && trim($services) !== '') {
                if (strpos($services, '[') === 0) {
                    // It's a JSON string
                    $services_array = json_decode($services, true);
                    if (is_array($services_array)) {
                        $csa_services = implode(', ', $services_array);
                    } else {
                        $csa_services = $services;
                    }
                } else {
                    // It's a regular string
                    $csa_services = $services;
                }
            }
        } catch (Exception $e) {
            $csa_services = $row['services_needed'];
        }
    }
    
    $data = array(
        $counter,
        $row['family_id'] ?? 'N/A',
        $row['student_name'] ?? 'No Student Data',
        $row['age'] ?? 'N/A',
        $row['gender'] ?? 'N/A',
        $row['grade'] ?? 'N/A',
        $row['parent_name'] ?? 'N/A',
        $row['contact'] ?? 'N/A',
        $row['address'] ?? 'N/A',
        $row['surveyor_name'] ?? 'N/A',
        $row['timestamp'] ?? 'N/A',
        $row['student_status'] ?? 'No Student Data',
        $earning_source ?? 'N/A',
        $csa_services ?: 'N/A',
        $row['need_job_assistance'] ?? 'N/A',
        $row['already_going_school'] ?? 'N/A',
        $row['school_type'] ?? 'N/A',
        $row['already_coaching'] ?? 'N/A',
        $row['coaching_name'] ?? 'N/A'
    );
    
    fputcsv($output, $data);
    $counter++;
}

fclose($output);
exit;
?>