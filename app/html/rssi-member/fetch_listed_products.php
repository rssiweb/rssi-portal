<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include(__DIR__ . "/../image_functions.php");

header('Content-Type: application/json');
if (!isLoggedIn("aid")) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

/* ---------- Read & sanitize inputs ---------- */
$from     = trim($_GET['from']   ?? '');
$to       = trim($_GET['to']     ?? '');
$status   = trim($_GET['status'] ?? '');
$q        = trim($_GET['q']      ?? '');
$vendorId = trim($_GET['vendor_id'] ?? '');
$soldFrom = trim($_GET['sold_from'] ?? '');
$soldTo   = trim($_GET['sold_to']   ?? '');
$page     = max(1, (int)($_GET['page']     ?? 1));
$pageSize = (int)($_GET['pageSize'] ?? 15);
if ($pageSize < 1 || $pageSize > 200) $pageSize = 15;
$offset   = ($page - 1) * $pageSize;

/* ---------- Build WHERE clause ---------- */
$where  = [];
$params = [];

if ($from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
    $params[] = $from;
    $where[]  = "listed_at >= $" . count($params) . "::date";
}
if ($to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    $params[] = $to;
    // inclusive end-of-day
    $where[]  = "listed_at < ($" . count($params) . "::date + INTERVAL '1 day')";
}

/* Status: derive from qty_sold vs quantity rather than trusting the stored column,
   because the stored column can drift if sales are edited. */
if (in_array($status, ['available', 'partial', 'sold'], true)) {
    if ($status === 'available') {
        $where[] = "qty_sold = 0";
    } elseif ($status === 'partial') {
        $where[] = "qty_sold > 0 AND qty_sold < quantity";
    } else { // sold
        $where[] = "qty_sold >= quantity";
    }
}

if ($q !== '') {
    $params[] = '%' . $q . '%';
    $n = count($params);
    $where[] = "(serial_number ILIKE $" . $n . " OR item_name ILIKE $" . $n . ")";
}

/* Sold-date range: only applies when sold_at is set.
   If either bound is supplied we also require pl.sold_at IS NOT NULL
   so unsold rows don't leak in with a null date. */
$soldFromOk = ($soldFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $soldFrom));
$soldToOk   = ($soldTo   !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $soldTo));
if ($soldFromOk || $soldToOk) {
    $where[] = "pl.sold_at IS NOT NULL";
    if ($soldFromOk) {
        $params[] = $soldFrom;
        $where[]  = "pl.sold_at >= $" . count($params) . "::date";
    }
    if ($soldToOk) {
        $params[] = $soldTo;
        $where[]  = "pl.sold_at < ($" . count($params) . "::date + INTERVAL '1 day')";
    }
}

/* Vendor filter:
   - empty  => All
   - "0"    => Unassigned (current_vendor_id IS NULL)
   - number => that specific vendor */
if ($vendorId !== '') {
    if ($vendorId === '0') {
        $where[] = "pl.current_vendor_id IS NULL";
    } elseif (ctype_digit($vendorId)) {
        $params[] = (int) $vendorId;
        $where[]  = "pl.current_vendor_id = $" . count($params);
    }
    // anything else (garbage) is ignored
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

/* ---------- Count total ---------- */
$countSql = "SELECT COUNT(*) AS c
               FROM product_listings pl
          LEFT JOIN vendors v ON v.id = pl.current_vendor_id
             $whereSql";
$countRes = pg_query_params($con, $countSql, $params);
if (!$countRes) {
    echo json_encode(['status' => 'error', 'message' => pg_last_error($con)]);
    exit;
}
$total = (int) pg_fetch_assoc($countRes)['c'];

/* ---------- Fetch page ---------- */
$listParams   = $params;
$listParams[] = $pageSize;
$limitIdx     = count($listParams);
$listParams[] = $offset;
$offsetIdx    = count($listParams);

$listSql = "
    SELECT pl.id, pl.serial_number, pl.item_name, pl.category, pl.metal, pl.purity,
           pl.gross_weight, pl.net_weight, pl.stone_weight, pl.cost_price,
           pl.min_sell_price, pl.selling_price, pl.making_charges, pl.discount,
           pl.quantity, pl.qty_sold, pl.image_drive_url, pl.notes, pl.listed_by,
           pl.listed_at, pl.sold_at, pl.current_vendor_id, v.name AS vendor_name
      FROM product_listings pl
 LEFT JOIN vendors v ON v.id = pl.current_vendor_id
    $whereSql
    ORDER BY pl.listed_at DESC, pl.id DESC
    LIMIT $" . $limitIdx . " OFFSET $" . $offsetIdx;

$listRes = pg_query_params($con, $listSql, $listParams);
if (!$listRes) {
    echo json_encode(['status' => 'error', 'message' => pg_last_error($con)]);
    exit;
}

/* ---------- Shape rows for the front-end ---------- */
$rows = [];
while ($r = pg_fetch_assoc($listRes)) {
    $rows[] = [
        'id'            => (int) $r['id'],
        'serial'        => $r['serial_number'],
        'item_name'     => $r['item_name'],
        'category'      => $r['category'],
        'metal'         => $r['metal'],
        'purity'        => $r['purity'],
        'gross_weight'  => $r['gross_weight'] !== null ? (float) $r['gross_weight'] : null,
        'net_weight'    => $r['net_weight']   !== null ? (float) $r['net_weight']   : null,
        'stone_weight'  => $r['stone_weight'] !== null ? (float) $r['stone_weight'] : null,
        'cost_price'    => (float) $r['cost_price'],
        'min_sell_price' => (float) $r['min_sell_price'],
        'selling_price' => (float) $r['selling_price'],
        'making_charges' => (float) $r['making_charges'],
        'discount'      => (float) $r['discount'],
        'quantity'      => (int) $r['quantity'],
        'sold'          => (int) $r['qty_sold'],
        'image_url'   => $r['image_drive_url'],
        'image_proxy' => !empty($r['image_drive_url']) ? processImageUrl($r['image_drive_url']) : null,
        'notes'         => $r['notes'],
        'listed_by'     => $r['listed_by'],
        'listed_date'   => $r['listed_at'] ? date('Y-m-d', strtotime($r['listed_at'])) : null,
        'sold_date'     => $r['sold_at']   ? date('Y-m-d', strtotime($r['sold_at']))   : null,
        'current_vendor_id' => $r['current_vendor_id'] !== null ? (int) $r['current_vendor_id'] : null,
        'vendor_name'       => $r['vendor_name'],
    ];
}

echo json_encode([
    'status'   => 'success',
    'rows'     => $rows,
    'total'    => $total,
    'page'     => $page,
    'pageSize' => $pageSize,
]);
