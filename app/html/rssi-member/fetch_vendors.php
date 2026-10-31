<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

header('Content-Type: application/json');
if (!isLoggedIn("aid")) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

/* ---------- Read & normalize inputs ---------- */
$q      = trim($_GET['q']      ?? '');
$active = trim($_GET['active'] ?? '');

/* ---------- Build WHERE clause ---------- */
$where  = [];
$params = [];

if ($q !== '') {
    $params[] = '%' . $q . '%';
    $n = '$' . count($params);
    $where[] = "(name ILIKE $n OR contact_person ILIKE $n OR phone ILIKE $n
                 OR email ILIKE $n OR biz_city ILIKE $n OR gstin ILIKE $n
                 OR vendor_type ILIKE $n OR contact ILIKE $n)";
}

// Only accept '1' or '0'. Anything else means "no filter".
if ($active === '1' || $active === '0') {
    $params[] = $active;
    $where[]  = "active = $" . count($params) . "::boolean";
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

/* ---------- Query ---------- */
$sql = "SELECT id, name, contact_person, vendor_type, business_type,
               phone, alt_phone, email, gstin, pan, payment_terms, notes,
               res_address, res_city, res_state, res_pincode,
               biz_address, biz_city, biz_state, biz_pincode,
               id_proof_type, id_proof_number,
               photo_file, shop_photo_file, id_proof_file, business_doc_file,
               bank_account_name, bank_name, bank_account_no, bank_ifsc, upi_id,
               active,
               to_char(created_at, 'YYYY-MM-DD') AS created_date
          FROM vendors
          $whereSql
      ORDER BY active DESC, name ASC";

$res = pg_query_params($con, $sql, $params);
if (!$res) {
    error_log('fetch_vendors: ' . pg_last_error($con));
    echo json_encode(['status' => 'error', 'message' => 'Could not load vendors']);
    exit;
}

/* ---------- Shape output ---------- */
$textCols = [
    'name',
    'contact_person',
    'vendor_type',
    'business_type',
    'phone',
    'alt_phone',
    'email',
    'gstin',
    'pan',
    'payment_terms',
    'notes',
    'res_address',
    'res_city',
    'res_state',
    'res_pincode',
    'biz_address',
    'biz_city',
    'biz_state',
    'biz_pincode',
    'id_proof_type',
    'id_proof_number',
    'bank_account_name',
    'bank_name',
    'bank_account_no',
    'bank_ifsc',
    'upi_id',
];
$fileKeys = [
    'photo'        => 'photo_file',
    'shop_photo'   => 'shop_photo_file',
    'id_proof'     => 'id_proof_file',
    'business_doc' => 'business_doc_file',
];

$out = [];
while ($r = pg_fetch_assoc($res)) {
    $row = [
        'id'           => (int) $r['id'],
        'active'       => $r['active'] === 't',
        'created_date' => $r['created_date'],
    ];
    foreach ($textCols as $c) $row[$c] = $r[$c];

    // Drive URLs are stored directly in these columns.
    $files = [];
    foreach ($fileKeys as $key => $col) {
        $files[$key] = $r[$col] ?: null;
    }
    $row['files']        = $files;
    $row['kyc_complete'] = !empty($r['id_proof_number']) && !empty($r['id_proof_file']);

    $out[] = $row;
}

echo json_encode(['status' => 'success', 'vendors' => $out]);
