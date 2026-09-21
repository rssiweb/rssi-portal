<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

header('Content-Type: application/json');

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (!isLoggedIn("aid")) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$result = pg_query($con, "SELECT * FROM cashflow_balance");
if (!$result) {
    echo json_encode(['success' => false, 'error' => pg_last_error($con)]);
    exit;
}
$row = pg_fetch_assoc($result) ?: [
    'total_earnings'  => 0,
    'total_expenses'  => 0,
    'current_balance' => 0
];

echo json_encode([
    'success' => true,
    'balance' => [
        'total_earnings'  => (float)$row['total_earnings'],
        'total_expenses'  => (float)$row['total_expenses'],
        'current_balance' => (float)$row['current_balance'],
    ]
]);
