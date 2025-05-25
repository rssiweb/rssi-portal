<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

header('Content-Type: application/json');

$searchTerm = isset($_GET['q']) ? trim($_GET['q']) : '';
$gender = isset($_GET['gender']) ? trim($_GET['gender']) : null;

// Debugging output
error_log("Received request with searchTerm: '$searchTerm' and gender: '$gender'");

// Validate gender if provided
if ($gender && !in_array($gender, ['Male', 'Female', 'Other'])) {
    error_log("Invalid gender parameter: $gender");
    echo json_encode(['results' => []]);
    exit;
}

$query = "SELECT id, name, contact_number 
          FROM public_health_records 
          WHERE registration_completed = true AND name ILIKE $1";

$params = ["%{$searchTerm}%"];

if ($gender) {
    $query .= " AND gender = $2";
    $params[] = $gender;
    error_log("Applying gender filter: $gender");
}

$query .= " ORDER BY name LIMIT 10";

error_log("Final query: $query");
error_log("Query parameters: " . print_r($params, true));

$result = pg_query_params($con, $query, $params);

if (!$result) {
    $error = pg_last_error($con);
    error_log("Database query failed: $error");
    echo json_encode(['results' => []]);
    exit;
}

$students = [];
while ($row = pg_fetch_assoc($result)) {
    $students[] = [
        'id' => $row['id'],
        'text' => $row['name'] . ' (' . $row['contact_number'] . ')'
    ];
}

error_log("Returning " . count($students) . " students");
echo json_encode(['results' => $students]);
?>