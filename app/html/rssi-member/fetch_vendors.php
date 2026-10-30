<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

header('Content-Type: application/json');
if (!isLoggedIn("aid")) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

/* ---------- Read & normalize inputs ---------- */
$q      = trim($_GET['q']      ?? '');
$active = trim($_GET['active'] ?? '');

/* ---------- Build WHERE clause ---------- */
$where  = [];
$params = [];

if ($q !== '') {
    $params[] = '%' . $q . '%';
    $n = count($params);
    $where[] = "(name ILIKE $" . $n . " OR contact ILIKE $" . $n . ")";
}

// Only accept '1' or '0'. Anything else (including empty) means "no filter".
if ($active === '1' || $active === '0') {
    // Bind as a string and cast to boolean inside SQL — bulletproof against
    // driver quirks where a PHP bool might get serialized incorrectly.
    $params[] = $active;
    $where[]  = "active = $" . count($params) . "::boolean";
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

/* ---------- Query ---------- */
$sql = "SELECT id, name, contact, active,
               to_char(created_at, 'YYYY-MM-DD') AS created_date
          FROM vendors
          $whereSql
      ORDER BY active DESC, name ASC";

$res = pg_query_params($con, $sql, $params);
if (!$res) {
    echo json_encode([
        'status'  => 'error',
        'message' => pg_last_error($con),
        'debug'   => [
            'q'      => $q,
            'active' => $active,
            'params' => $params,
            'sql'    => $sql
        ]
    ]);
    exit;
}

/* ---------- Shape output ---------- */
$out = [];
while ($r = pg_fetch_assoc($res)) {
    $out[] = [
        'id'           => (int) $r['id'],
        'name'         => $r['name'],
        'contact'      => $r['contact'],
        'active'       => $r['active'] === 't',
        'created_date' => $r['created_date'],
    ];
}

echo json_encode(['status' => 'success', 'vendors' => $out]);
