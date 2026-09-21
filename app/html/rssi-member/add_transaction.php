<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("../../util/drive.php");

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (!isLoggedIn("aid")) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

validation();
$aid = $_SESSION['aid'] ?? ($associatenumber ?? 'unknown');

// ============================================================
//  👇 SET YOUR GOOGLE DRIVE FOLDER ID HERE
//  Open the folder in Drive → copy the ID from the URL:
//  https://drive.google.com/drive/folders/XXXXXXXXXXXX
//                                        ^^^^^^^^^^^^
// ============================================================
$driveParentFolderId = '1tlExZTumfTJRU5xQh_bcVFXxN-u96UkV';

// -------- Collect input --------
$type     = $_POST['type'] ?? '';
$amount   = floatval($_POST['amount'] ?? 0);
$category = trim($_POST['category_name'] ?? '');
$date     = $_POST['transaction_date'] ?? date('Y-m-d');
$notes    = trim($_POST['notes'] ?? '');

// -------- Validate --------
if (!in_array($type, ['earning', 'expense'], true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid type.']);
    exit;
}
if ($amount <= 0) {
    echo json_encode(['success' => false, 'error' => 'Amount must be greater than 0.']);
    exit;
}
if ($category === '') {
    echo json_encode(['success' => false, 'error' => 'Category is required.']);
    exit;
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['success' => false, 'error' => 'Invalid date.']);
    exit;
}

// -------- Balance guard (expense only) --------
if ($type === 'expense') {
    $balRes = pg_query($con, "SELECT current_balance FROM cashflow_balance");
    $balRow = pg_fetch_assoc($balRes);
    $currentBalance = floatval($balRow['current_balance'] ?? 0);
    if ($amount > $currentBalance) {
        echo json_encode([
            'success' => false,
            'error'   => 'Insufficient balance. Current balance is ₹' . number_format($currentBalance, 2)
        ]);
        exit;
    }
}

// -------- Category id --------
$catId = null;
$catRes = pg_query_params(
    $con,
    "SELECT id FROM cashflow_categories WHERE name = $1 LIMIT 1",
    [$category]
);
if ($catRow = pg_fetch_assoc($catRes)) {
    $catId = (int)$catRow['id'];
}

// -------- Upload receipt to Google Drive --------
$receiptDriveId  = null;
$receiptDriveUrl = null;
$receiptFileName = null;

if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
    $file    = $_FILES['receipt'];
    $maxSize = 5 * 1024 * 1024;                    // 5 MB
    $allowed = ['application/pdf', 'image/jpeg', 'image/png'];

    if ($file['size'] > $maxSize) {
        echo json_encode(['success' => false, 'error' => 'File too large (max 5MB).']);
        exit;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed, true)) {
        echo json_encode(['success' => false, 'error' => 'Only PDF, JPG, PNG allowed.']);
        exit;
    }

    try {
        // Build a unique, human-readable filename WITHOUT extension.
        // Your helper appends the extension itself.
        $baseName = 'cashflow_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3));

        // ==================================================================
        //  Your util/drive.php exposes:  uploadeToDrive($file, $parent, $filename)
        //  - $file     : the $_FILES['receipt'] array (not the tmp path)
        //  - $parent   : Google Drive folder ID (not a folder name)
        //  - $filename : filename WITHOUT extension (helper appends it)
        //  Returns: a Drive "view" URL string.
        // ==================================================================
        $driveUrl = uploadeToDrive($file, $driveParentFolderId, $baseName);

        if (!$driveUrl || !filter_var($driveUrl, FILTER_VALIDATE_URL)) {
            throw new Exception('Drive helper did not return a valid URL.');
        }

        $receiptDriveUrl = $driveUrl;
        $receiptFileName = $file['name'];

        // Extract the Drive file id from the URL for future reference
        if (preg_match('#/d/([a-zA-Z0-9_-]+)#', $driveUrl, $m)) {
            $receiptDriveId = $m[1];
        }
    } catch (Throwable $e) {
        error_log('Cashflow Drive upload failed: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error'   => 'Receipt upload to Google Drive failed: ' . $e->getMessage()
        ]);
        exit;
    }
}

// -------- Insert --------
$sql = "INSERT INTO cashflow_transactions
        (transaction_date, type, category_id, category_name, amount, notes,
         receipt_drive_id, receipt_drive_url, receipt_file_name, created_by)
        VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9,$10)
        RETURNING id";
$params = [
    $date,
    $type,
    $catId,
    $category,
    $amount,
    $notes,
    $receiptDriveId,
    $receiptDriveUrl,
    $receiptFileName,
    $aid
];

$res = pg_query_params($con, $sql, $params);
if (!$res) {
    echo json_encode(['success' => false, 'error' => pg_last_error($con)]);
    exit;
}
$row = pg_fetch_assoc($res);

echo json_encode(['success' => true, 'id' => (int)$row['id']]);
