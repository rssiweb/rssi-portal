<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

header('Content-Type: application/json');

if (!isLoggedIn("aid")) {
    echo json_encode([]);
    exit;
}

$asset_id = $_GET['asset_id'] ?? null;

if (!$asset_id) {
    echo json_encode([]);
    exit;
}

// Fetch linked assets
$query = "
    SELECT al.linked_asset_itemid, g.itemname, g.asset_status
    FROM asset_links al
    JOIN gps g ON al.linked_asset_itemid = g.itemid
    WHERE al.asset_itemid = '" . pg_escape_string($con, $asset_id) . "'
    AND al.is_active = TRUE
    ORDER BY g.itemname";

$result = pg_query($con, $query);

$linked_assets = [];
while ($row = pg_fetch_assoc($result)) {
    $linked_assets[] = $row;
}

echo json_encode($linked_assets);
?>