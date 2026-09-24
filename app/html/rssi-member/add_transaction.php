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

$driveParentFolderId = '1tlExZTumfTJRU5xQh_bcVFXxN-u96UkV';

// -------- Collect rows --------
$rows = $_POST['rows'] ?? [];
if (!is_array($rows) || count($rows) === 0) {
    echo json_encode(['success' => false, 'error' => 'No rows submitted.']);
    exit;
}
if (count($rows) > 50) {
    echo json_encode(['success' => false, 'error' => 'Too many rows at once (max 50).']);
    exit;
}

// -------- Current DB balance --------
$balRes = pg_query($con, "
    SELECT COALESCE(SUM(CASE WHEN type = 'earning' THEN amount ELSE -amount END), 0) AS current_balance
    FROM cashflow_transactions
");
if (!$balRes) {
    echo json_encode(['success' => false, 'error' => pg_last_error($con)]);
    exit;
}
$runningBalance = floatval(pg_fetch_assoc($balRes)['current_balance'] ?? 0);

// -------- Phase 1a: validate every row (no per-row balance check) --------
$validated = [];
$netDelta  = 0;

foreach ($rows as $idx => $row) {
    $type     = $row['type'] ?? '';
    $amount   = floatval($row['amount'] ?? 0);
    $category = trim($row['category_name'] ?? '');
    $date     = $row['transaction_date'] ?? '';
    $notes    = trim($row['notes'] ?? '');
    $rowLabel = 'Row ' . ($idx + 1);

    if (!in_array($type, ['earning', 'expense'], true)) {
        echo json_encode(['success' => false, 'error' => $rowLabel . ': invalid type.']);
        exit;
    }
    if ($amount <= 0) {
        echo json_encode(['success' => false, 'error' => $rowLabel . ': amount must be greater than 0.']);
        exit;
    }
    if ($category === '') {
        echo json_encode(['success' => false, 'error' => $rowLabel . ': category is required.']);
        exit;
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        echo json_encode(['success' => false, 'error' => $rowLabel . ': invalid date.']);
        exit;
    }

    $catRes = pg_query_params(
        $con,
        "SELECT id, type FROM cashflow_categories WHERE name = $1 LIMIT 1",
        [$category]
    );
    $catRow  = pg_fetch_assoc($catRes);
    $catId   = $catRow ? (int)$catRow['id'] : null;
    $catType = $catRow['type'] ?? 'both';

    if ($catType !== 'both' && $catType !== $type) {
        echo json_encode([
            'success' => false,
            'error'   => $rowLabel . ': category "' . $category . '" is not valid for a ' . $type . ' transaction.'
        ]);
        exit;
    }

    $netDelta += ($type === 'earning' ? $amount : -$amount);

    $validated[$idx] = [
        'type' => $type,
        'amount' => $amount,
        'category_id' => $catId,
        'category_name' => $category,
        'transaction_date' => $date,
        'notes' => $notes
    ];
}

// -------- Phase 1b: single balance check on the batch total --------
$finalBalance = $runningBalance + $netDelta;

if ($finalBalance < 0) {
    echo json_encode([
        'success' => false,
        'error'   => 'This batch would leave the balance below zero. ' .
            'Current: ₹' . number_format($runningBalance, 2) . ', ' .
            'after this batch: ₹' . number_format($finalBalance, 2)
    ]);
    exit;
}

// -------- Phase 2: upload receipts --------
foreach ($validated as $idx => &$v) {
    $v['receipt_drive_url'] = null;

    if (!isset($_FILES['rows']['name'][$idx]['receipt'])) continue;
    $fileErr = $_FILES['rows']['error'][$idx]['receipt'] ?? UPLOAD_ERR_NO_FILE;
    if ($fileErr !== UPLOAD_ERR_OK) continue;

    $file = [
        'name'     => $_FILES['rows']['name'][$idx]['receipt'],
        'type'     => $_FILES['rows']['type'][$idx]['receipt'],
        'tmp_name' => $_FILES['rows']['tmp_name'][$idx]['receipt'],
        'error'    => $fileErr,
        'size'     => $_FILES['rows']['size'][$idx]['receipt']
    ];

    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'error' => 'Row ' . ($idx + 1) . ': file too large (max 5MB).']);
        exit;
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'], true)) {
        echo json_encode(['success' => false, 'error' => 'Row ' . ($idx + 1) . ': only PDF, JPG, PNG allowed.']);
        exit;
    }

    try {
        $baseName = 'cashflow_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3));
        $driveUrl = uploadeToDrive($file, $driveParentFolderId, $baseName);

        if (!$driveUrl || !filter_var($driveUrl, FILTER_VALIDATE_URL)) {
            throw new Exception('Drive helper did not return a valid URL.');
        }
        $v['receipt_drive_url'] = $driveUrl;
    } catch (Throwable $e) {
        error_log('Cashflow Drive upload failed: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error'   => 'Row ' . ($idx + 1) . ': Drive upload failed — ' . $e->getMessage()
        ]);
        exit;
    }
}
unset($v);

// -------- Phase 3: insert all in one transaction --------
pg_query($con, "BEGIN");
$inserted = 0;
foreach ($validated as $v) {
    $sql = "INSERT INTO cashflow_transactions
            (transaction_date, type, category_id, category_name, amount, notes, receipt_drive_url, created_by)
            VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9,$10)";
    $params = [
        $v['transaction_date'],
        $v['type'],
        $v['category_id'],
        $v['category_name'],
        $v['amount'],
        $v['notes'],
        $v['receipt_drive_url'],
        $aid
    ];
    $res = pg_query_params($con, $sql, $params);
    if (!$res) {
        pg_query($con, "ROLLBACK");
        echo json_encode(['success' => false, 'error' => 'DB insert failed: ' . pg_last_error($con) . ' (nothing was saved)']);
        exit;
    }
    $inserted++;
}
pg_query($con, "COMMIT");

echo json_encode(['success' => true, 'inserted' => $inserted]);
