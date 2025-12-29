<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

header('Content-Type: application/json');

if (!isLoggedIn("aid")) {
    echo json_encode([]);
    exit;
}

// Fetch active locations
$query = "SELECT id, name, is_active FROM office_locations WHERE is_active = TRUE ORDER BY name";
$result = pg_query($con, $query);

$locations = [];
while ($row = pg_fetch_assoc($result)) {
    $locations[] = $row;
}

echo json_encode($locations);
?>