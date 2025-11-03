<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

// Check user role and permissions
if ($role != 'Admin' && $role != 'SuperAdmin') {
    echo "<script>alert('You are not authorized to access this page.'); window.location.href='home.php';</script>";
    exit;
}

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
        $where_conditions[] = "(name ILIKE $" . $param_count . " OR js.contact ILIKE $" . $param_count . " OR skills ILIKE $" . $param_count . " OR preferences ILIKE $" . $param_count . ")";
        $params[] = "%$search_term%";
    }
} else {
    // Filter mode - use status and education filters
    if ($status_filter === 'active') {
        $where_conditions[] = "status = 'Active'";
    } elseif ($status_filter === 'inactive') {
        $where_conditions[] = "status = 'Inactive'";
    }

    if (!empty($education_filter)) {
        $param_count++;
        $where_conditions[] = "education = $" . $param_count;
        $params[] = $education_filter;
    }

    // Also allow search in filter mode
    if (!empty($search_term)) {
        $param_count++;
        $where_conditions[] = "(name ILIKE $" . $param_count . " OR js.contact ILIKE $" . $param_count . " OR skills ILIKE $" . $param_count . " OR preferences ILIKE $" . $param_count . ")";
        $params[] = "%$search_term%";
    }
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get job seekers data
$query = "SELECT js.*, s.parent_name, s.address, s.surveyor_id 
          FROM job_seeker_data js 
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
echo "Status: " . ($status_filter === 'active' ? 'Active' : ($status_filter === 'inactive' ? 'Inactive' : 'All')) . "\n";
echo "Education: " . ($education_filter ?: 'All') . "\n";
if ($search_mode) {
    echo "Search Mode: Enabled\n";
    echo "Search Term: " . ($search_term ?: 'None') . "\n";
} else if ($search_term) {
    echo "Search Term: " . $search_term . "\n";
}
echo "\n";

echo "ID\tName\tAge\tContact\tEducation\tSkills\tPreferences\tParent/Guardian\tAddress\tSurveyor ID\tStatus\tRemarks\tCreated Date\n";

while ($row = pg_fetch_assoc($result)) {
    $id = $row['id'];
    $name = str_replace(["\t", "\n", "\r"], ' ', $row['name']);
    $age = $row['age'];
    $contact = $row['contact'];
    $education = $row['education'];
    $skills = str_replace(["\t", "\n", "\r"], ' ', $row['skills'] ?? '');
    $preferences = str_replace(["\t", "\n", "\r"], ' ', $row['preferences'] ?? '');
    $parent_name = str_replace(["\t", "\n", "\r"], ' ', $row['parent_name']);
    $address = str_replace(["\t", "\n", "\r"], ' ', $row['address']);
    $surveyor_id = $row['surveyor_id'];
    $status = $row['status'];
    $remarks = str_replace(["\t", "\n", "\r"], ' ', $row['remarks'] ?? '');
    $created_date = $row['created_at'];
    
    echo "$id\t$name\t$age\t$contact\t$education\t$skills\t$preferences\t$parent_name\t$address\t$surveyor_id\t$status\t$remarks\t$created_date\n";
}
exit;
?>