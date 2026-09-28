<?php
require_once __DIR__ . '/../bootstrap.php';
include(__DIR__ . "/../util/login_util.php");
header('Content-Type: application/json');

// Which context is the caller requesting?
//   ?for=admission  → only locations accepting admissions (default behavior)
//   ?for=job        → only locations accepting job applications
$for = $_GET['for'] ?? 'admission';

$where = "WHERE is_active = TRUE";
$params = [];

if ($for === 'job') {
    $where .= " AND accepting_job_applications = TRUE";
} else {
    // Default / legacy behavior
    $where .= " AND accepting_admissions = TRUE";
}

$sql = "SELECT id, name
        FROM office_locations
        $where
        ORDER BY name";

$result = pg_query_params($con, $sql, $params);

if (!$result) {
    error_log("fetch_locations.php query failed: " . pg_last_error($con));
    echo json_encode([]);
    exit;
}

$locations = [];
while ($row = pg_fetch_assoc($result)) {
    $locations[] = $row;
}

echo json_encode($locations);
