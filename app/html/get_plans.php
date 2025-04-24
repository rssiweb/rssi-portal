<?php
require_once __DIR__ . '/../bootstrap.php';
include(__DIR__ . "/../util/login_util.php");
header('Content-Type: application/json');

// Get division from request
$division = $_GET['division'] ?? '';

if (!in_array($division, ['kalpana', 'rssi'])) {
    die(json_encode([]));
}

// Using pg_query_params for prepared statements with your existing connection
$result = pg_query_params($con, 
    "SELECT name FROM plans WHERE division = $1 AND is_active = TRUE ORDER BY name", 
    [$division]
);

if (!$result) {
    die(json_encode(['error' => 'Database query failed']));
}

$plans = [];
while ($row = pg_fetch_assoc($result)) {
    $plans[] = $row;
}

echo json_encode($plans);
?>