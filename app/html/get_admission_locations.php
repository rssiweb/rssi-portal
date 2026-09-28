<?php
require_once __DIR__ . '/../bootstrap.php';
include(__DIR__ . "/../util/login_util.php");
header('Content-Type: application/json');

// Using pg_query_params for prepared statements with your existing connection
$result = pg_query_params(
    $con,
    "SELECT id, name
     FROM office_locations
     WHERE is_active = TRUE
       AND accepting_admissions = TRUE
     ORDER BY name",
    []
);

if (!$result) {
    die(json_encode(['error' => 'Database query failed']));
}

$locations = [];
while ($row = pg_fetch_assoc($result)) {
    $locations[] = $row;
}

echo json_encode($locations);
