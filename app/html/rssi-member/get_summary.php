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

// -------- Build WHERE (columns prefixed with ct.) --------
$where  = [];
$params = [];
$i = 1;

$where[]  = "ct.transaction_date >= $" . $i++;
$params[] = $from;

$where[]  = "ct.transaction_date <= $" . $i++;
$params[] = $to;

if ($category !== '') {
    // Filter on the joined category name
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

// -------- Totals --------
$totSql = "SELECT
    COALESCE(SUM(CASE WHEN ct.type='earning' THEN ct.amount ELSE 0 END), 0) AS earnings,
    COALESCE(SUM(CASE WHEN ct.type='expense' THEN ct.amount ELSE 0 END), 0) AS expenses
    FROM cashflow_transactions ct
    LEFT JOIN cashflow_categories cc ON cc.id = ct.category_id
    $whereSql";

$totRes = pg_query_params($con, $totSql, $params);
if (!$totRes) {
    echo json_encode(['success' => false, 'error' => pg_last_error($con)]);
    exit;
}
$tot = pg_fetch_assoc($totRes) ?: ['earnings' => 0, 'expenses' => 0];

$earnings = (float)$tot['earnings'];
$expenses = (float)$tot['expenses'];
$net      = $earnings - $expenses;

// -------- Category breakdown --------
$catSql = "SELECT 
                ct.type,
                cc.name AS category_name,
                SUM(ct.amount) AS total
           FROM cashflow_transactions ct
           LEFT JOIN cashflow_categories cc ON cc.id = ct.category_id
           $whereSql
           GROUP BY ct.type, cc.name
           ORDER BY ct.type, total DESC";

$catRes = pg_query_params($con, $catSql, $params);
if (!$catRes) {
    echo json_encode(['success' => false, 'error' => pg_last_error($con)]);
    exit;
}
$catRows = pg_fetch_all($catRes) ?: [];

$earnCats = [];
$expCats  = [];
foreach ($catRows as $r) {
    // Handle orphaned category_id (null name) — label them clearly
    $catName = $r['category_name'] ?? 'Uncategorized';

    if ($r['type'] === 'earning') {
        $earnCats[] = ['category' => $catName, 'total' => (float)$r['total']];
    } else {
        $expCats[]  = ['category' => $catName, 'total' => (float)$r['total']];
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
