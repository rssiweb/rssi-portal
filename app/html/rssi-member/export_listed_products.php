<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
if (!isLoggedIn("aid")) {
    http_response_code(401);
    exit;
}

$from     = trim($_GET['from']   ?? '');
$to       = trim($_GET['to']     ?? '');
$status   = trim($_GET['status'] ?? '');
$q        = trim($_GET['q']      ?? '');
$soldFrom = trim($_GET['sold_from'] ?? '');
$soldTo   = trim($_GET['sold_to']   ?? '');
$vendorId = isset($_GET['vendor_id']) && $_GET['vendor_id'] !== ''
    ? (int)$_GET['vendor_id'] : null;

$where = [];
$params = [];
if ($from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
    $params[] = $from;
    $where[] = "pl.listed_at >= $" . count($params) . "::date";
}
if ($to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    $params[] = $to;
    $where[] = "pl.listed_at < ($" . count($params) . "::date + INTERVAL '1 day')";
}
if ($status === 'available')      $where[] = "pl.qty_sold = 0";
elseif ($status === 'partial')    $where[] = "pl.qty_sold > 0 AND pl.qty_sold < pl.quantity";
elseif ($status === 'sold')       $where[] = "pl.qty_sold >= pl.quantity";
if ($q !== '') {
    $params[] = '%' . $q . '%';
    $n = count($params);
    $where[] = "(pl.serial_number ILIKE $n OR pl.item_name ILIKE $n)";
}
if ($vendorId === 0)      $where[] = "pl.current_vendor_id IS NULL";
elseif ($vendorId !== null) {
    $params[] = $vendorId;
    $where[] = "pl.current_vendor_id = $" . count($params);
}

$soldFromOk = ($soldFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $soldFrom));
$soldToOk   = ($soldTo   !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $soldTo));
if ($soldFromOk || $soldToOk) {
    $where[] = "pl.sold_at IS NOT NULL";
    if ($soldFromOk) {
        $params[] = $soldFrom;
        $where[] = "pl.sold_at >= $" . count($params) . "::date";
    }
    if ($soldToOk) {
        $params[] = $soldTo;
        $where[] = "pl.sold_at < ($" . count($params) . "::date + INTERVAL '1 day')";
    }
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT pl.serial_number, pl.item_name, pl.category, pl.metal, pl.purity,
               pl.cost_price, pl.min_sell_price, pl.selling_price, pl.discount,
               pl.quantity, pl.qty_sold, v.name AS vendor, pl.status, pl.listed_at
          FROM product_listings pl
     LEFT JOIN vendors v ON v.id = pl.current_vendor_id
          $whereSql
      ORDER BY pl.listed_at DESC, pl.id DESC";

$res = pg_query_params($con, $sql, $params);
if (!$res) {
    http_response_code(500);
    exit(pg_last_error($con));
}

$fname = 'listed_products_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $fname . '"');

$out = fopen('php://output', 'w');
// UTF-8 BOM so Excel opens it with correct encoding
fwrite($out, "\xEF\xBB\xBF");

fputcsv($out, [
    'Serial',
    'Item',
    'Category',
    'Metal',
    'Purity',
    'Cost',
    'Min Sell',
    'Sell',
    'Discount',
    'Qty',
    'Sold',
    'Sold on',
    'Vendor',
    'Status',
    'Listed'
]);

while ($r = pg_fetch_assoc($res)) {
    fputcsv($out, [
        $r['serial_number'],
        $r['item_name'],
        $r['category'],
        $r['metal'],
        $r['purity'],
        $r['cost_price'],
        $r['min_sell_price'],
        $r['selling_price'],
        $r['discount'],
        $r['quantity'],
        $r['qty_sold'],
        $r['sold_at'] ? date('Y-m-d', strtotime($r['sold_at'])) : '',
        $r['vendor'] ?? '',
        $r['status'],
        $r['listed_at'] ? date('Y-m-d', strtotime($r['listed_at'])) : '',
    ]);
}
fclose($out);
