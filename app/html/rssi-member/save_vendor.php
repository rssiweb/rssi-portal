<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("../../util/drive.php");

header('Content-Type: application/json');

function fail(string $msg): void
{
    echo json_encode(['status' => 'error', 'message' => $msg]);
    exit;
}

if (!isLoggedIn("aid")) fail('Not authenticated');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Invalid request');

// Google Drive folder for all vendor documents and photos
$driveParentFolderId = '18zPwwObf-Hp0WJNlJ7yVhg5qTCQ_23Yz';

$id     = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? 'save';

/* ---------- Quick activate / deactivate (list page toggle) ---------- */
if ($action === 'toggle') {
    if ($id <= 0) fail('Invalid vendor');
    $active = ($_POST['active'] ?? '1') === '1';
    $res = pg_query_params(
        $con,
        "UPDATE vendors SET active = $1, updated_at = now() WHERE id = $2",
        [$active ? 't' : 'f', $id]
    );
    if (!$res) fail('Could not update status');
    if (pg_affected_rows($res) === 0) fail('Vendor not found');
    echo json_encode(['status' => 'success']);
    exit;
}

/* ---------- Allowed values ---------- */
$vendorTypes   = ['Stationery', 'Books', 'Uniform / Clothing', 'Electronics', 'Furniture', 'Food / Catering', 'Printing', 'Services', 'Other'];
$businessTypes = ['Proprietorship', 'Partnership', 'LLP', 'Private Limited', 'Individual / Unregistered', 'Other'];
$idTypes       = ['Aadhaar', 'PAN', 'Voter ID', 'Driving Licence', 'Passport'];
$payTerms      = ['Advance', 'Cash on delivery', 'Net 15', 'Net 30'];

/* ---------- Text fields: column => [label, max length, required] ---------- */
$spec = [
    'name'              => ['Business name', 150, true],
    'contact_person'    => ['Contact person', 150, true],
    'vendor_type'       => ['Vendor category', 50, true],
    'business_type'     => ['Business type', 50, false],
    'phone'             => ['Phone', 20, true],
    'alt_phone'         => ['Alternate phone', 20, false],
    'email'             => ['Email', 150, false],
    'gstin'             => ['GSTIN', 15, false],
    'pan'               => ['PAN', 10, false],
    'payment_terms'     => ['Payment terms', 30, false],
    'notes'             => ['Notes', 2000, false],
    'res_address'       => ['Residential address', 500, false],
    'res_city'          => ['Residential city', 80, false],
    'res_state'         => ['Residential state', 60, false],
    'res_pincode'       => ['Residential pincode', 6, false],
    'biz_address'       => ['Business address', 500, true],
    'biz_city'          => ['Business city', 80, true],
    'biz_state'         => ['Business state', 60, true],
    'biz_pincode'       => ['Business pincode', 6, true],
    'id_proof_type'     => ['ID proof type', 30, false],
    'id_proof_number'   => ['ID proof number', 30, false],
    'bank_account_name' => ['Account holder name', 150, false],
    'bank_name'         => ['Bank name', 100, false],
    'bank_account_no'   => ['Account number', 18, false],
    'bank_ifsc'         => ['IFSC', 11, false],
    'upi_id'            => ['UPI ID', 80, false],
];

$data = [];
foreach ($spec as $col => [$label, $max, $required]) {
    $v = trim((string)($_POST[$col] ?? ''));
    if ($v === '') {
        if ($required) fail("$label is required");
        $data[$col] = null;
        continue;
    }
    if (mb_strlen($v) > $max) fail("$label is too long (max $max characters)");
    $data[$col] = $v;
}

/* ---------- Format validation / normalisation ---------- */
function normPhone(string $label, ?string $v): ?string
{
    if ($v === null) return null;
    $d = preg_replace('/\D/', '', $v);
    if (strlen($d) === 12 && strpos($d, '91') === 0) $d = substr($d, 2);
    if (strlen($d) === 11 && $d[0] === '0') $d = substr($d, 1);
    if (!preg_match('/^[6-9]\d{9}$/', $d)) fail("$label must be a valid 10-digit mobile number");
    return $d;
}
$data['phone']     = normPhone('Phone', $data['phone']);
$data['alt_phone'] = normPhone('Alternate phone', $data['alt_phone']);

if ($data['email'] !== null) {
    $data['email'] = strtolower($data['email']);
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) fail('Email is not valid');
}

foreach (['res_pincode' => 'Residential pincode', 'biz_pincode' => 'Business pincode'] as $c => $l) {
    if ($data[$c] !== null && !preg_match('/^\d{6}$/', $data[$c])) fail("$l must be 6 digits");
}

if ($data['gstin'] !== null) {
    $data['gstin'] = strtoupper($data['gstin']);
    if (!preg_match('/^\d{2}[A-Z]{5}\d{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/', $data['gstin'])) fail('GSTIN is not valid');
}
if ($data['pan'] !== null) {
    $data['pan'] = strtoupper($data['pan']);
    if (!preg_match('/^[A-Z]{5}\d{4}[A-Z]$/', $data['pan'])) fail('PAN is not valid');
}
if ($data['bank_ifsc'] !== null) {
    $data['bank_ifsc'] = strtoupper($data['bank_ifsc']);
    if (!preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', $data['bank_ifsc'])) fail('IFSC is not valid');
}
if ($data['bank_account_no'] !== null && !preg_match('/^\d{9,18}$/', $data['bank_account_no'])) {
    fail('Account number must be 9 to 18 digits');
}
if ($data['upi_id'] !== null && !preg_match('/^[\w.\-]{2,}@[A-Za-z]{2,}$/', $data['upi_id'])) {
    fail('UPI ID is not valid');
}

/* Enum fields */
if (!in_array($data['vendor_type'], $vendorTypes, true)) fail('Choose a valid vendor category');
if ($data['business_type'] !== null && !in_array($data['business_type'], $businessTypes, true)) fail('Choose a valid business type');
if ($data['id_proof_type'] !== null && !in_array($data['id_proof_type'], $idTypes, true)) fail('Choose a valid ID proof type');
if ($data['payment_terms'] !== null && !in_array($data['payment_terms'], $payTerms, true)) fail('Choose valid payment terms');

/*
 * ID proof is optional (type and number). When a number is given it is stored
 * as entered (Aadhaar as 12 digits, others upper-cased). Records saved earlier
 * with a masked Aadhaar (XXXXXXXX1234) can still be edited: the masked value is
 * left untouched unless a full number is entered.
 */
$keepIdNumber = false;
$idType = $data['id_proof_type'];
$idNum  = $data['id_proof_number'];
if ($idNum !== null) {
    if ($idType === 'Aadhaar') {
        if ($id > 0 && preg_match('/^X{8}\d{4}$/i', $idNum)) {
            $keepIdNumber = true;
        } else {
            $digits = preg_replace('/\D/', '', $idNum);
            if (strlen($digits) !== 12) fail('Aadhaar number must be 12 digits');
            $data['id_proof_number'] = $digits;
        }
    } else {
        if (!preg_match('/^[A-Za-z0-9\/\- ]{4,20}$/', $idNum)) fail('ID proof number is not valid');
        $data['id_proof_number'] = strtoupper($idNum);
    }
}
if ($keepIdNumber) unset($data['id_proof_number']);

$data['active'] = (($_POST['active'] ?? '1') === '1') ? 't' : 'f';

/* ---------- Pre-checks (done before anything is uploaded to Drive) ---------- */
if ($id > 0) {
    $r = pg_query_params($con, "SELECT 1 FROM vendors WHERE id = $1", [$id]);
    if (!$r || pg_num_rows($r) === 0) fail('Vendor not found');
}
$dup = pg_query_params($con, "SELECT 1 FROM vendors WHERE name = $1 AND id <> $2 LIMIT 1", [$data['name'], $id]);
if ($dup && pg_num_rows($dup) > 0) fail('A vendor with this business name already exists');

/* ---------- Validate files: column => [form field, images only] ---------- */
$fileCols = [
    'photo_file'        => ['photo', true],
    'shop_photo_file'   => ['shop_photo', true],
    'id_proof_file'     => ['id_proof', false],
    'business_doc_file' => ['business_doc', false],
];
$imgTypes = ['image/jpeg', 'image/png', 'image/webp'];
$docTypes = array_merge($imgTypes, ['application/pdf']);
$pending  = [];

foreach ($fileCols as $col => [$key, $imagesOnly]) {
    if (!isset($_FILES[$key]) || $_FILES[$key]['error'] === UPLOAD_ERR_NO_FILE) continue;
    $file  = $_FILES[$key];
    $label = str_replace('_', ' ', $key);

    if ($file['error'] !== UPLOAD_ERR_OK) fail("Upload failed for $label (code {$file['error']})");
    if ($file['size'] > 5 * 1024 * 1024) fail("$label: file too large (max 5 MB)");

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $imagesOnly ? $imgTypes : $docTypes, true)) {
        fail("$label: " . ($imagesOnly ? 'only JPG, PNG or WebP images are allowed' : 'only JPG, PNG, WebP or PDF files are allowed'));
    }
    $pending[$col] = [$key, $file];
}

/* ---------- Upload to Google Drive, then write to the database ---------- */
pg_query($con, "BEGIN");

try {
    $slug = substr(trim(preg_replace('/[^A-Za-z0-9]+/', '_', $data['name']), '_'), 0, 30) ?: 'vendor';

    foreach ($pending as $col => [$key, $file]) {
        $baseName = 'vendor_' . $slug . '_' . $key . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3));
        $url = uploadeToDrive($file, $driveParentFolderId, $baseName);

        if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception(str_replace('_', ' ', $key) . ': Drive upload failed.');
        }
        $data[$col] = $url;
    }

    $cols   = array_keys($data);
    $values = array_values($data);

    if ($id > 0) {
        $sets = [];
        foreach ($cols as $i => $c) $sets[] = "$c = $" . ($i + 1);
        $values[] = $id;
        $res = pg_query_params(
            $con,
            "UPDATE vendors SET " . implode(', ', $sets) . ", updated_at = now() WHERE id = $" . count($values),
            $values
        );
        if (!$res) throw new Exception(pg_last_error($con));
        if (pg_affected_rows($res) === 0) throw new Exception('Vendor not found');
    } else {
        $ph = [];
        foreach ($cols as $i => $c) $ph[] = '$' . ($i + 1);
        $res = pg_query_params(
            $con,
            "INSERT INTO vendors (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $ph) . ") RETURNING id",
            $values
        );
        if (!$res) throw new Exception(pg_last_error($con));
    }

    pg_query($con, "COMMIT");
    echo json_encode(['status' => 'success']);
} catch (Throwable $e) {
    pg_query($con, "ROLLBACK");
    $msg = $e->getMessage();
    error_log('save_vendor failed: ' . $msg);

    if (strpos($msg, 'vendors_name_key') !== false) {
        $msg = 'A vendor with this business name already exists';
    } elseif (strpos($msg, 'Drive upload failed') === false && $msg !== 'Vendor not found') {
        $msg = 'Could not save vendor. Please try again.';
    }
    echo json_encode(['status' => 'error', 'message' => $msg]);
}
