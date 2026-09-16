<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['results' => []]);
    exit;
}

validation();
header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');

$sql    = "SELECT unit_id, unit_name FROM stock_item_unit";
$params = [];

if ($q !== '') {
    $sql .= " WHERE unit_name ILIKE \$1";
    $params[] = '%' . $q . '%';
}
$sql .= " ORDER BY unit_name LIMIT 30";

$res  = $params
    ? pg_query_params($con, $sql, $params)
    : pg_query($con, $sql);

$rows = $res ? (pg_fetch_all($res) ?: []) : [];

$results = array_map(fn($r) => [
    'id'   => $r['unit_id'],
    'text' => $r['unit_name'],
], $rows);

echo json_encode(['results' => $results]);
