<?php
header('Content-Type: application/json');

// Prevent timezone drift between display page and this endpoint
date_default_timezone_set('Asia/Kolkata');

require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

/**
 * Build a 12-digit unpredictable reference number from id + created_at.
 * (Must be BYTE-IDENTICAL to the function in the display page.)
 *
 * - Last 6 digits of created_at's microseconds → first 6 chars
 * - Reversed id, padded to 7 digits → next 6 chars
 */
function build_ref_number($id, $created_at)
{
    $parts = explode('.', (string)$created_at);
    $micro = isset($parts[1]) ? preg_replace('/\D/', '', $parts[1]) : '';
    $micro = str_pad(substr($micro, 0, 6), 6, '0', STR_PAD_LEFT);

    $rev = str_pad(strrev((string)(int)$id), 7, '0', STR_PAD_LEFT);

    return substr($micro . $rev, 0, 12);
}

// Accept only a 12-digit ref
$raw = isset($_GET['ref']) ? trim($_GET['ref']) : '';

if ($raw === '' || !ctype_digit($raw) || strlen($raw) !== 12) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid reference. Please enter the 12-digit reference number.'
    ]);
    exit;
}

// ---------------------------------------------------------------------------
// Scan recent rows to find a match (ref is not reversible by arithmetic)
// ---------------------------------------------------------------------------
$res = pg_query(
    $con,
    "SELECT * FROM parent_admissions ORDER BY id DESC LIMIT 10000"
);

if (!$res) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$match = null;
while ($r = pg_fetch_assoc($res)) {
    if (build_ref_number($r['id'], $r['created_at']) === $raw) {
        $match = $r;
        break;
    }
}

if (!$match) {
    echo json_encode([
        'success' => false,
        'message' => 'Reference does not match any record. Please check the number.'
    ]);
    exit;
}

$match['ref_number'] = $raw;
echo json_encode(['success' => true, 'data' => $match]);
