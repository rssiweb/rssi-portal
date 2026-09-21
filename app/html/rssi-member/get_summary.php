<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (!isLoggedIn("aid")) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$from     = $_GET['from']      ?? '';
$to       = $_GET['to']        ?? '';
$category = $_GET['category']  ?? '';
$type     = $_GET['type']      ?? '';
$q        = trim($_GET['q']    ?? '');

// Same 90-day guard used elsewhere
define('MAX_RANGE_DAYS', 90);
$hasFrom = preg_match('/^\d{4}-\d{2}-\d{2}$/', $from);
$hasTo   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $to);

if (!$hasFrom || !$hasTo) {
    echo json_encode(['success' => false, 'error' => 'Please select a From and To date.']);
    exit;
}

$days = (int)floor((strtotime($to) - strtotime($from)) / 86400) + 1;
if ($days < 1) {
    echo json_encode(['success' => false, 'error' => 'Invalid date range.']);
    exit;
}
if ($days > MAX_RANGE_DAYS) {
    echo json_encode(['success' => false, 'error' => 'Date range cannot exceed ' . MAX_RANGE_DAYS . ' days.']);
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

// -------- Totals --------
$totSql = "SELECT
    COALESCE(SUM(CASE WHEN type='earning' THEN amount ELSE 0 END), 0) AS earnings,
    COALESCE(SUM(CASE WHEN type='expense' THEN amount ELSE 0 END), 0) AS expenses
    FROM cashflow_transactions
    $whereSql";
$totRes = pg_query_params($con, $totSql, $params);
$tot    = pg_fetch_assoc($totRes) ?: ['earnings' => 0, 'expenses' => 0];

$earnings = (float)$tot['earnings'];
$expenses = (float)$tot['expenses'];
$net      = $earnings - $expenses;

// -------- Category breakdown --------
$catSql = "SELECT type, category_name, SUM(amount) AS total
           FROM cashflow_transactions
           $whereSql
           GROUP BY type, category_name
           ORDER BY type, total DESC";
$catRes = pg_query_params($con, $catSql, $params);
$catRows = pg_fetch_all($catRes) ?: [];

$earnCats = [];
$expCats  = [];
foreach ($catRows as $r) {
    if ($r['type'] === 'earning') {
        $earnCats[] = ['category' => $r['category_name'], 'total' => (float)$r['total']];
    } else {
        $expCats[]  = ['category' => $r['category_name'], 'total' => (float)$r['total']];
    }
}

echo json_encode([
    'success'    => true,
    'totals'     => [
        'earnings' => $earnings,
        'expenses' => $expenses,
        'net'      => $net
    ],
    'categories' => [
        'earnings' => $earnCats,
        'expenses' => $expCats
    ],
    'meta' => [
        'from' => $from,
        'to'   => $to,
        'days' => $days
    ]
]);
