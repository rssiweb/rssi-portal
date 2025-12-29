<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

header('Content-Type: application/json');

if (!isLoggedIn("aid")) {
    echo json_encode([]);
    exit;
}

$exclude = $_GET['exclude'] ?? null;

// Fetch assets for linking (excluding current asset if specified)
$query = "SELECT itemid, itemname FROM gps WHERE asset_status = 'Active'";
if ($exclude) {
    $query .= " AND itemid != '" . pg_escape_string($con, $exclude) . "'";
}
$query .= " ORDER BY itemname";

$result = pg_query($con, $query);

$assets = [];
while ($row = pg_fetch_assoc($result)) {
    $assets[] = $row;
}

echo json_encode($assets);
?>