<?php
require_once __DIR__ . "/../../bootstrap.php";
include(__DIR__ . "/../image_functions.php");

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
            ol.name as current_location,
            COUNT(v.id) as verification_count,
            MAX(v.verification_date) as last_verified_on
          FROM gps
          LEFT JOIN rssimyaccount_members as tm ON gps.taggedto = tm.associatenumber
          LEFT JOIN rssimyaccount_members as im ON gps.collectedby = im.associatenumber
          LEFT JOIN gps_verifications as v ON gps.itemid = v.asset_id
          LEFT JOIN office_locations as ol ON gps.location = ol.id
          WHERE gps.itemid = '$safe_asset_id'
          GROUP BY gps.itemid, tm.fullname, tm.phone, tm.email, im.fullname, im.phone, ol.name";

$result = pg_query($con, $query);

if (!$result || pg_num_rows($result) === 0) {
    echo json_encode(['error' => 'Asset not found']);
    exit;
}

$asset = pg_fetch_assoc($result);

// Process the photo URL and extract photo ID
if (!empty($asset['asset_photo'])) {
    // Get processed photo URL
    $asset['processed_photo_url'] = processImageUrl($asset['asset_photo']);
    
    // Extract file ID for proxy
    if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $asset['asset_photo'], $matches)) {
        $asset['photo_id'] = $matches[1];
    } else {
        $asset['photo_id'] = null;
    }
} else {
    $asset['processed_photo_url'] = null;
    $asset['photo_id'] = null;
}

// Format dates for better display
if (!empty($asset['purchase_date'])) {
    $asset['purchase_date_formatted'] = date("d/m/Y", strtotime($asset['purchase_date']));
}

if (!empty($asset['last_verified_on'])) {
    $asset['last_verified_on_formatted'] = date("d/m/Y g:i a", strtotime($asset['last_verified_on']));
}

// Format dates
if ($asset['last_verified_on']) {
    $asset['last_verified_on'] = date('d/m/Y H:i', strtotime($asset['last_verified_on']));
}

echo json_encode($asset);
