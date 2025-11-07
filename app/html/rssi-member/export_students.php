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
$status_filter = $_GET['status'] ?? 'all';
$search_term = $_GET['search'] ?? '';
$search_mode = filter_var($_GET['search_mode'] ?? false, FILTER_VALIDATE_BOOLEAN);

// Build WHERE clause based on search mode
$where_conditions = [];
$params = [];
$param_count = 0;

if ($search_mode) {
    // Search mode - only use search term
    if (!empty($search_term)) {
        $param_count++;
        $where_conditions[] = "(sd.student_name ILIKE $" . $param_count . " OR s.contact ILIKE $" . $param_count . " OR s.parent_name ILIKE $" . $param_count . " OR sd.family_id ILIKE $" . $param_count . ")";
        $params[] = "%$search_term%";
    }
} else {
    // Filter mode - use status filter
    if ($status_filter !== 'all') {
        $param_count++;
        $where_conditions[] = "sd.status = $" . $param_count;
        $params[] = $status_filter;
    }

    // Also allow search in filter mode
    if (!empty($search_term)) {
        $param_count++;
        $where_conditions[] = "(sd.student_name ILIKE $" . $param_count . " OR s.contact ILIKE $" . $param_count . " OR s.parent_name ILIKE $" . $param_count . " OR sd.family_id ILIKE $" . $param_count . ")";
        $params[] = "%$search_term%";
    }
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get all records for export
$query = "SELECT sd.*, s.parent_name, s.address, s.surveyor_id, s.contact, s.earning_source, s.other_earning_source_input, 
                 s.timestamp, rm.fullname AS surveyor_name
          FROM student_data sd 
          LEFT JOIN survey_data s ON sd.family_id = s.family_id 
          LEFT JOIN rssimyaccount_members rm ON s.surveyor_id = rm.associatenumber
          $where_clause 
          ORDER BY s.timestamp DESC";

$result = pg_query_params($con, $query, $params);

if (!$result) {
    die("Error fetching data for export.");
}

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="survey_students_export_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// Start Excel content
echo "<table border='1'>";
echo "<tr>";
echo "<th>SL</th>";
echo "<th>Family ID</th>";
echo "<th>Student Name</th>";
echo "<th>Age</th>";
echo "<th>Gender</th>";
echo "<th>Grade</th>";
echo "<th>Parent Name</th>";
echo "<th>Contact</th>";
echo "<th>Address</th>";
echo "<th>Surveyor Name</th>";
echo "<th>Timestamp</th>";
echo "<th>Status</th>";
echo "<th>Earning Source</th>";
echo "</tr>";

$counter = 1;
while ($row = pg_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . $counter . "</td>";
    echo "<td>" . htmlspecialchars($row['family_id'] ?? 'N/A') . "</td>";
    echo "<td>" . htmlspecialchars($row['student_name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['age']) . "</td>";
    echo "<td>" . htmlspecialchars($row['gender']) . "</td>";
    echo "<td>" . htmlspecialchars($row['grade']) . "</td>";
    echo "<td>" . htmlspecialchars($row['parent_name'] ?? 'N/A') . "</td>";
    echo "<td>" . htmlspecialchars($row['contact'] ?? 'N/A') . "</td>";
    echo "<td>" . htmlspecialchars($row['address'] ?? 'N/A') . "</td>";
    echo "<td>" . htmlspecialchars($row['surveyor_name'] ?? 'N/A') . "</td>";
    echo "<td>" . htmlspecialchars($row['timestamp'] ?? 'N/A') . "</td>";
    echo "<td>" . htmlspecialchars($row['status']) . "</td>";
    echo "<td>" . htmlspecialchars(($row['earning_source'] === "other" ? $row['other_earning_source_input'] : $row['earning_source']) ?? 'N/A') . "</td>";
    echo "</tr>";
    $counter++;
}

echo "</table>";
exit;
?>