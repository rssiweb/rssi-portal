<?php
// vendor_search.php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['vendor_name'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Vendor name required']);
    exit;
}

$vendorName = pg_escape_string($con, $_GET['vendor_name']);
$query = "SELECT * FROM third_party_vendors 
          WHERE LOWER(vendor_name) LIKE LOWER('%$vendorName%')
          AND is_active = true
          ORDER BY usage_count DESC, last_used_date DESC
          LIMIT 20";

$result = pg_query($con, $query);
if (!$result) {
    echo json_encode(['error' => 'Database query failed']);
    exit;
}

$vendors = [];
while ($row = pg_fetch_assoc($result)) {
    $vendors[] = [
        'vendor_id' => $row['vendor_id'],
        'name' => $row['vendor_name'],
        'contact' => $row['contact_number'],
        'email' => $row['email'],
        'address' => $row['address'],
        'gst' => $row['gst_number'],
        'bank_account' => $row['bank_account_no'],
        'bank_name' => $row['bank_name'],
        'ifsc' => $row['ifsc_code']
    ];
}

header('Content-Type: application/json');
echo json_encode($vendors);
