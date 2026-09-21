<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    header("Location: ../../index.php");
    exit;
}

$from     = $_GET['from']      ?? '';
$to       = $_GET['to']        ?? '';
$category = $_GET['category']  ?? '';
$type     = $_GET['type']      ?? '';
$q        = trim($_GET['q']    ?? '');

// Server-side 90-day enforcement (client can be bypassed)
define('MAX_RANGE_DAYS', 90);
$hasFrom = preg_match('/^\d{4}-\d{2}-\d{2}$/', $from);
$hasTo   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $to);

// Require both dates for export — no full-dump allowed
if (!$hasFrom || !$hasTo) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error'   => 'Please select a From and To date before exporting (max 90 days).'
    ]);
    exit;
}

$days = (int)floor((strtotime($to) - strtotime($from)) / 86400) + 1;
if ($days < 1) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid date range.']);
    exit;
}
if ($days > MAX_RANGE_DAYS) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error'   => 'Date range cannot exceed ' . MAX_RANGE_DAYS . ' days.'
    ]);
    exit;
}

// -------- Build WHERE --------
$where  = [];
$params = [];
$i = 1;

$where[]  = "transaction_date >= $" . $i++;
$params[] = $from;

$where[]  = "transaction_date <= $" . $i++;
$params[] = $to;

if ($category !== '') {
    $where[]  = "category_name = $" . $i++;
    $params[] = $category;
}
if (in_array($type, ['earning', 'expense'], true)) {
    $where[]  = "type = $" . $i++;
    $params[] = $type;
}
if ($q !== '') {
    $where[]  = "(notes ILIKE $" . $i . " OR category_name ILIKE $" . $i . " OR CAST(amount AS TEXT) ILIKE $" . $i . ")";
    $params[] = '%' . $q . '%';
    $i++;
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

// -------- Fetch --------
$sql = "SELECT transaction_date, type, category_name, notes, amount,
               receipt_drive_url, created_by
        FROM cashflow_transactions
        $whereSql
        ORDER BY transaction_date ASC, id ASC";
$res  = pg_query_params($con, $sql, $params);
$rows = pg_fetch_all($res) ?: [];

// -------- Stream CSV --------
$filename = 'cashflow_export_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fputs($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel

// Header
fputcsv($out, ['Date', 'Type', 'Category', 'Notes', 'Amount', 'Receipt (Drive)', 'Created By']);

// Rows
$earn = 0;
$exp  = 0;
foreach ($rows as $r) {
    fputcsv($out, [
        $r['transaction_date'],
        $r['type'],
        $r['category_name'],
        $r['notes'],
        number_format((float)$r['amount'], 2, '.', ''),
        $r['receipt_drive_url'] ?? '',
        $r['created_by']
    ]);
    if ($r['type'] === 'earning') $earn += (float)$r['amount'];
    else                          $exp  += (float)$r['amount'];
}

// Summary
fputcsv($out, []);
fputcsv($out, ['SUMMARY']);
fputcsv($out, ['Total Earnings', number_format($earn, 2, '.', '')]);
fputcsv($out, ['Total Expenses', number_format($exp, 2, '.', '')]);
fputcsv($out, ['Net Balance',    number_format($earn - $exp, 2, '.', '')]);

// Filter info so the reader knows what they're looking at
fputcsv($out, []);
fputcsv($out, ['Filters applied']);
fputcsv($out, ['From date',  $from]);
fputcsv($out, ['To date',    $to]);
fputcsv($out, ['Category',   $category !== '' ? $category : 'All']);
fputcsv($out, ['Type',       $type !== ''     ? $type     : 'All']);
fputcsv($out, ['Search',     $q !== ''        ? $q        : '—']);
fputcsv($out, ['Range days', $days]);
fputcsv($out, ['Row count',  count($rows)]);

fclose($out);
exit;
