<?php
require_once __DIR__ . "/../../bootstrap.php";

header('Content-Type: application/json');

$asset_id = $_GET['asset_id'] ?? '';

if (!$asset_id) {
    echo json_encode(['error' => 'Asset ID required']);
    exit;
}

$safe_asset_id = pg_escape_string($con, $asset_id);

$query = "SELECT 
            gps.*,
            tm.fullname as tagged_to_name,
            tm.phone as tagged_to_phone,
            tm.email as tagged_to_email,
            im.fullname as issued_by_name,
            im.phone as issued_by_phone,
            COUNT(v.id) as verification_count,
            MAX(v.verification_date) as last_verified_on
          FROM gps
          LEFT JOIN rssimyaccount_members as tm ON gps.taggedto = tm.associatenumber
          LEFT JOIN rssimyaccount_members as im ON gps.collectedby = im.associatenumber
          LEFT JOIN gps_verifications as v ON gps.itemid = v.asset_id
          WHERE gps.itemid = '$safe_asset_id'
          GROUP BY gps.itemid, tm.fullname, tm.phone, tm.email, im.fullname, im.phone";

$result = pg_query($con, $query);

if (!$result || pg_num_rows($result) === 0) {
    echo json_encode(['error' => 'Asset not found']);
    exit;
}

$asset = pg_fetch_assoc($result);

// Format dates
if ($asset['last_verified_on']) {
    $asset['last_verified_on'] = date('d/m/Y H:i', strtotime($asset['last_verified_on']));
}

echo json_encode($asset);
?>