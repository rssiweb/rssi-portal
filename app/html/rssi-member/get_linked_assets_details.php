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

// Fetch linked assets with details
$query = "
    SELECT 
        g.itemid,
        g.itemname,
        g.asset_status,
        g.taggedto,
        m.fullname as taggedto_name
    FROM asset_links al
    JOIN gps g ON al.linked_asset_itemid = g.itemid
    LEFT JOIN rssimyaccount_members m ON g.taggedto = m.associatenumber
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