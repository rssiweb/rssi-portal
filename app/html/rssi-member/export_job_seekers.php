<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}
validation();

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'active';
$education_filter = isset($_GET['education']) ? $_GET['education'] : '';
$search_term = isset($_GET['search']) ? pg_escape_string($con, $_GET['search']) : '';
$search_mode = isset($_GET['search_mode']) ? filter_var($_GET['search_mode'], FILTER_VALIDATE_BOOLEAN) : false;

// Build WHERE clause
$where_conditions = [];
$params = [];
$param_count = 0;

if ($search_mode) {
    // Search mode - only use search term
    if (!empty($search_term)) {
        $param_count++;
        $where_conditions[] = "(js.name ILIKE $" . $param_count . " OR js.contact ILIKE $" . $param_count . " OR js.skills ILIKE $" . $param_count . " OR js.preferences ILIKE $" . $param_count . " OR js.email ILIKE $" . $param_count . ")";
        $params[] = "%$search_term%";
    }
} else {
    // Filter mode - use status and education filters
    if ($status_filter === 'active') {
        $where_conditions[] = "js.status = 'Active'";
    } elseif ($status_filter === 'inactive') {
        $where_conditions[] = "js.status = 'Inactive'";
    } elseif ($status_filter === 'all') {
        // Show all statuses - no condition needed
    }

    if (!empty($education_filter)) {
        $param_count++;
        $where_conditions[] = "js.education = $" . $param_count;
        $params[] = $education_filter;
    }

    // Also allow search in filter mode
    if (!empty($search_term)) {
        $param_count++;
        $where_conditions[] = "(js.name ILIKE $" . $param_count . " OR js.contact ILIKE $" . $param_count . " OR js.skills ILIKE $" . $param_count . " OR js.preferences ILIKE $" . $param_count . " OR js.email ILIKE $" . $param_count . ")";
        $params[] = "%$search_term%";
    }
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get job seekers data with education name
$query = "SELECT js.*, el.name as education_name, s.parent_name, 
                 COALESCE(js.address1, s.address) AS address, 
                 COALESCE(js.created_by, s.surveyor_id) AS surveyor_id 
          FROM job_seeker_data js 
          LEFT JOIN education_levels el ON js.education = el.id
          LEFT JOIN survey_data s ON js.family_id = s.family_id 
          $where_clause 
          ORDER BY js.created_at DESC";

$result = $params ? pg_query_params($con, $query, $params) : pg_query($con, $query);

// Set headers for Excel file download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="job_seekers_' . date('Y-m-d') . '.xls"');

// Excel file content
echo "Job Seekers List\n\n";
echo "Generated on: " . date('Y-m-d H:i:s') . "\n";

// Add filter information
echo "Filters Applied:\n";
echo "Status: " . ($status_filter === 'active' ? 'Active' : ($status_filter === 'inactive' ? 'Inactive' : ($status_filter === 'all' ? 'All' : $status_filter))) . "\n";
echo "Education: " . ($education_filter ?: 'All') . "\n";
if ($search_mode) {
    echo "Search Mode: Enabled\n";
    echo "Search Term: " . ($search_term ?: 'None') . "\n";
} else if ($search_term) {
    echo "Search Term: " . $search_term . "\n";
}
echo "\n";

// Updated headers with new fields
echo "ID\tName\tDate of Birth\tAge\tEmail\tContact\tEducation\tSkills\tPreferences\tAddress\tSurveyor ID\tStatus\tRemarks\tCreated Date\n";

while ($row = pg_fetch_assoc($result)) {
    $id = $row['id'];
    $name = str_replace(["\t", "\n", "\r"], ' ', $row['name']);

    // Calculate age from DOB
    $age = '--';
    if ($row['dob']) {
        $dob = new DateTime($row['dob']);
        $today = new DateTime();
        $age = $today->diff($dob)->y;
    }

    $dob = $row['dob'] ?: '';
    $email = str_replace(["\t", "\n", "\r"], ' ', $row['email'] ?? '');
    $contact = $row['contact'];
    $education = str_replace(["\t", "\n", "\r"], ' ', $row['education_name'] ?? ($row['education'] ?? ''));
    $skills = str_replace(["\t", "\n", "\r"], ' ', $row['skills'] ?? '');
    $preferences = str_replace(["\t", "\n", "\r"], ' ', $row['preferences'] ?? '');
    $address = str_replace(["\t", "\n", "\r"], ' ', $row['address'] ?? '');
    $surveyor_id = $row['surveyor_id'] ?? '';
    $status = $row['status'];
    $remarks = str_replace(["\t", "\n", "\r"], ' ', $row['remarks'] ?? '');
    $created_date = $row['created_at'];

    echo "$id\t$name\t$dob\t$age\t$email\t$contact\t$education\t$skills\t$preferences\t$address\t$surveyor_id\t$status\t$remarks\t$created_date\n";
}
exit;
