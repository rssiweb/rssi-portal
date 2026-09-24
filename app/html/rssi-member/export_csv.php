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
/**
 * Extract the item name from an eMart Purchase note.
 * Pattern: "eMart Purchase - <ITEM NAME> | Buyer: <whoever>"
 * Returns null if the note is not an eMart Purchase.
 */
function extractEmartItemName($notes)
{
    if (empty($notes)) return null;

    // Case-insensitive match on "eMart Purchase - " prefix
    if (!preg_match('/^eMart Purchase\s*-\s*(.+?)\s*\|/i', $notes, $m)) {
        // Fallback: if there's no "|", grab everything after the dash
        if (preg_match('/^eMart Purchase\s*-\s*(.+)$/i', $notes, $m2)) {
            return trim($m2[1]);
        }
        return null;
    }

    return trim($m[1]);
}
// -------- Build WHERE --------
// Columns are prefixed with ct. because we now JOIN cashflow_categories
$where  = [];
$params = [];
$i = 1;

$where[]  = "ct.transaction_date >= $" . $i++;
$params[] = $from;

$where[]  = "ct.transaction_date <= $" . $i++;
$params[] = $to;

if ($category !== '') {
    // Filter by the joined category name
    $where[]  = "cc.name = $" . $i++;
    $params[] = $category;
}
if (in_array($type, ['earning', 'expense'], true)) {
    $where[]  = "ct.type = $" . $i++;
    $params[] = $type;
}
if ($q !== '') {
    $where[]  = "(
                    ct.notes ILIKE $" . $i . "
                 OR cc.name  ILIKE $" . $i . "
                 OR CAST(ct.amount AS TEXT) ILIKE $" . $i . "
                 )";
    $params[] = '%' . $q . '%';
    $i++;
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

// -------- Fetch (join to cashflow_categories for the name) --------
$sql = "SELECT 
            ct.transaction_date,
            ct.type,
            ct.category_id,
            cc.name AS category_name,
            ct.notes,
            ct.amount,
            ct.receipt_drive_url,
            ct.created_by,
            m.fullname AS created_by_name
        FROM cashflow_transactions ct
        LEFT JOIN cashflow_categories cc  ON cc.id = ct.category_id
        LEFT JOIN rssimyaccount_members m ON m.associatenumber = ct.created_by
        $whereSql
        ORDER BY ct.transaction_date DESC, ct.id DESC";

$res  = pg_query_params($con, $sql, $params);
$rows = pg_fetch_all($res) ?: [];

// -------- Resolve buyer IDs in notes to names --------
// 1) Collect all buyer IDs referenced in notes
$buyerIds = [];
foreach ($rows as $r) {
    if (
        !empty($r['notes']) &&
        preg_match('/Buyer:\s*([A-Za-z0-9_\-]+)/', $r['notes'], $m)
    ) {
        $buyerIds[] = $m[1];
    }
}
$buyerIds = array_values(array_unique(array_filter($buyerIds)));

// 2) Look them up in students first, then members
$buyerNameMap = [];
if (!empty($buyerIds)) {
    $placeholders = [];
    $lookupParams = [];
    $n = 1;
    foreach ($buyerIds as $id) {
        $placeholders[] = '$' . $n++;
        $lookupParams[] = $id;
    }
    $inList = implode(',', $placeholders);

    // Students
    $sRes = @pg_query_params(
        $con,
        "SELECT student_id AS id, studentname AS name
         FROM rssimyprofile_student
         WHERE student_id IN ($inList)",
        $lookupParams
    );
    if ($sRes) {
        while ($row = pg_fetch_assoc($sRes)) {
            $buyerNameMap[$row['id']] = $row['name'];
        }
    }

    // Members (fallback for IDs not found in students)
    $mRes = @pg_query_params(
        $con,
        "SELECT associatenumber AS id, fullname AS name
         FROM rssimyaccount_members
         WHERE associatenumber IN ($inList)",
        $lookupParams
    );
    if ($mRes) {
        while ($row = pg_fetch_assoc($mRes)) {
            if (!isset($buyerNameMap[$row['id']])) {
                $buyerNameMap[$row['id']] = $row['name'];
            }
        }
    }
}

// 3) Rewrite each row's notes, replacing "Buyer: <ID>" with "Buyer: <Name>"
foreach ($rows as &$r) {
    if (
        !empty($r['notes']) &&
        preg_match('/Buyer:\s*([A-Za-z0-9_\-]+)/', $r['notes'], $m)
    ) {

        $buyerId   = $m[1];
        $buyerName = $buyerNameMap[$buyerId] ?? '';

        if ($buyerName && $buyerName !== $buyerId) {
            $r['notes'] = preg_replace(
                '/Buyer:\s*' . preg_quote($buyerId, '/') . '\b/',
                'Buyer: ' . $buyerName,
                $r['notes'],
                1
            );
        }
        // If unresolved, leave the raw ID in place
    }
}
unset($r);

// -------- Stream CSV --------
$filename = 'cashflow_export_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fputs($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel

// Header
fputcsv($out, ['Date', 'Type', 'Category', 'Item Name', 'Notes', 'Amount', 'Receipt (Drive)', 'Created By']);

// Rows — expenses are shown with a negative sign, earnings positive
$earn = 0;
$exp  = 0;
foreach ($rows as $r) {

    $rawAmount = (float)$r['amount'];

    // Sign the amount for the CSV: earnings stay positive, expenses become negative
    $signedAmount = ($r['type'] === 'expense')
        ? -$rawAmount
        :  $rawAmount;

    // Extract the item name from the notes for eMart Purchases, else leave blank
    $itemName = extractEmartItemName($r['notes']);

    fputcsv($out, [
        $r['transaction_date'],
        $r['type'],
        $r['category_name'] ?? '',
        $itemName ?? '',                     // <-- new column
        $r['notes'],
        number_format($signedAmount, 2, '.', ''),
        $r['receipt_drive_url'] ?? '',
        $r['created_by_name'] ?: $r['created_by']
    ]);

    // Keep summary totals positive internally so the "Total Expenses" line is a positive number
    if ($r['type'] === 'earning') {
        $earn += $rawAmount;
    } else {
        $exp  += $rawAmount;
    }
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
