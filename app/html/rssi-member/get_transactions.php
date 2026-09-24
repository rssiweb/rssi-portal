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
$pageSize = min(50, max(5, (int)($_GET['page_size'] ?? 10)));

// -------- Build WHERE (columns prefixed with ct.) --------
$where  = [];
$params = [];
$i = 1;

if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
    $where[]  = "ct.transaction_date >= $" . $i++;
    $params[] = $from;
}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    $where[]  = "ct.transaction_date <= $" . $i++;
    $params[] = $to;
}
if ($category !== '') {
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

// Server-side 90-day enforcement
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
$countSql = "SELECT COUNT(*) AS total
             FROM cashflow_transactions ct
             LEFT JOIN cashflow_categories cc ON cc.id = ct.category_id
             $whereSql";

$countRes = pg_query_params($con, $countSql, $params);
if (!$countRes) {
    echo json_encode(['success' => false, 'error' => pg_last_error($con)]);
    exit;
}
$total = (int)(pg_fetch_assoc($countRes)['total'] ?? 0);

$totalPages = max(1, (int)ceil($total / $pageSize));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $pageSize;

// -------- Fetch page --------
$sql = "SELECT 
            ct.id,
            ct.transaction_date,
            ct.type,
            ct.category_id,
            cc.name AS category_name,
            ct.amount,
            ct.notes,
            ct.receipt_drive_url,
            ct.created_by,
            ct.created_at
        FROM cashflow_transactions ct
        LEFT JOIN cashflow_categories cc ON cc.id = ct.category_id
        $whereSql
        ORDER BY ct.transaction_date DESC, ct.id DESC
        LIMIT $" . $i++ . " OFFSET $" . $i++;

$pageParams = array_merge($params, [$pageSize, $offset]);

$res = pg_query_params($con, $sql, $pageParams);
if (!$res) {
    echo json_encode(['success' => false, 'error' => pg_last_error($con)]);
    exit;
}
$rows = pg_fetch_all($res) ?: [];

// ---------------------------------------------------------------------------
// Post-processing: resolve names for creator and buyer
// ---------------------------------------------------------------------------

// --- Collect IDs to look up ---
$creatorIds = [];
$buyerIds   = [];

foreach ($rows as $row) {
    if (!empty($row['created_by'])) {
        $creatorIds[] = $row['created_by'];
    }

    if (
        !empty($row['notes']) &&
        preg_match('/Buyer:\s*([A-Za-z0-9_\-]+)/', $row['notes'], $m)
    ) {
        $buyerIds[] = $m[1];
    }
}

$creatorIds = array_values(array_unique(array_filter($creatorIds)));
$buyerIds   = array_values(array_unique(array_filter($buyerIds)));

// --- Helper: bulk resolve a set of IDs against a table ---
function resolveNames($con, $table, $idColumn, $nameColumn, $ids)
{
    if (empty($ids)) return [];

    $placeholders = [];
    $params       = [];
    $n = 1;

    foreach ($ids as $id) {
        $placeholders[] = '$' . $n++;
        $params[]       = $id;
    }

    $sql = "SELECT $idColumn AS id, $nameColumn AS name
            FROM $table
            WHERE $idColumn IN (" . implode(',', $placeholders) . ")";

    $res = pg_query_params($con, $sql, $params);
    $map = [];
    if ($res) {
        while ($r = pg_fetch_assoc($res)) {
            $map[$r['id']] = $r['name'];
        }
    }
    return $map;
}

// --- Lookup maps ---
$creatorNameMap = resolveNames($con, 'rssimyaccount_members', 'associatenumber', 'fullname', $creatorIds);

$buyerNameMap = [];
if (!empty($buyerIds)) {
    $studentMap = resolveNames($con, 'rssimyprofile_student', 'student_id', 'studentname', $buyerIds);
    $memberMap  = resolveNames($con, 'rssimyaccount_members', 'associatenumber', 'fullname', $buyerIds);

    foreach ($buyerIds as $id) {
        // Prefer student name, fall back to member name, else keep the raw id
        $buyerNameMap[$id] = $studentMap[$id] ?? $memberMap[$id] ?? $id;
    }
}

// --- Apply to rows ---
foreach ($rows as &$row) {

    // Creator: just the name (fallback to raw id if unresolved)
    $creatorId   = $row['created_by'];
    $creatorName = $creatorNameMap[$creatorId] ?? '';
    $row['created_by_name'] = $creatorName ?: $creatorId;

    // Replace "Buyer: <ID>" with just "Buyer: <Name>"
    if (
        !empty($row['notes']) &&
        preg_match('/Buyer:\s*([A-Za-z0-9_\-]+)/', $row['notes'], $m)
    ) {

        $buyerId   = $m[1];
        $buyerName = $buyerNameMap[$buyerId] ?? '';

        if ($buyerName && $buyerName !== $buyerId) {
            $row['notes'] = preg_replace(
                '/Buyer:\s*' . preg_quote($buyerId, '/') . '\b/',
                'Buyer: ' . $buyerName,
                $row['notes'],
                1
            );
        }
    }
}
unset($row);

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
