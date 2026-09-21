<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

header('Content-Type: application/json');

if (!isLoggedIn("aid")) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// -------- Inputs --------
$from     = $_GET['from']      ?? '';
$to       = $_GET['to']        ?? '';
$category = $_GET['category']  ?? '';
$type     = $_GET['type']      ?? '';
$q        = trim($_GET['q']    ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$pageSize = min(50, max(5, (int)($_GET['page_size'] ?? 10)));   // 5..50, default 10

// -------- Build WHERE --------
$where  = [];
$params = [];
$i = 1;

if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
    $where[]  = "transaction_date >= $" . $i++;
    $params[] = $from;
}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    $where[]  = "transaction_date <= $" . $i++;
    $params[] = $to;
}
if ($category !== '') {
    $where[]  = "category_name = $" . $i++;
    $params[] = $category;
}
if (in_array($type, ['earning', 'expense'], true)) {
    $where[]  = "type = $" . $i++;
    $params[] = $type;
}
if ($q !== '') {
    // search notes / category / amount (as text)
    $where[]  = "(notes ILIKE $" . $i . " OR category_name ILIKE $" . $i . " OR CAST(amount AS TEXT) ILIKE $" . $i . ")";
    $params[] = '%' . $q . '%';
    $i++;
}

// Server-side 90-day enforcement (client can be bypassed)
define('MAX_RANGE_DAYS', 90);
$hasFrom = preg_match('/^\d{4}-\d{2}-\d{2}$/', $from);
$hasTo   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $to);

if ($hasFrom && $hasTo) {
    $days = (int)floor((strtotime($to) - strtotime($from)) / 86400) + 1;
    if ($days < 1) {
        echo json_encode(['success' => false, 'error' => 'Invalid date range.']);
        exit;
    }
    if ($days > MAX_RANGE_DAYS) {
        echo json_encode([
            'success' => false,
            'error'   => 'Date range cannot exceed ' . MAX_RANGE_DAYS . ' days.'
        ]);
        exit;
    }
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// -------- Count --------
$countSql = "SELECT COUNT(*) AS total FROM cashflow_transactions $whereSql";
$countRes = pg_query_params($con, $countSql, $params);
$total    = (int)(pg_fetch_assoc($countRes)['total'] ?? 0);

$totalPages = max(1, (int)ceil($total / $pageSize));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $pageSize;

// -------- Fetch page --------
$sql = "SELECT id, transaction_date, type, category_name, amount, notes,
               receipt_drive_url, receipt_file_name, created_by, created_at
        FROM cashflow_transactions
        $whereSql
        ORDER BY transaction_date DESC, id DESC
        LIMIT $" . $i++ . " OFFSET $" . $i++;
$pageParams = array_merge($params, [$pageSize, $offset]);

$res  = pg_query_params($con, $sql, $pageParams);
if (!$res) {
    echo json_encode(['success' => false, 'error' => pg_last_error($con)]);
    exit;
}
$rows = pg_fetch_all($res) ?: [];

echo json_encode([
    'success'    => true,
    'data'       => $rows,
    'pagination' => [
        'page'        => $page,
        'page_size'   => $pageSize,
        'total'       => $total,
        'total_pages' => $totalPages
    ]
]);
