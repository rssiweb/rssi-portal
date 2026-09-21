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

// -------- Balance computed inline from cashflow_transactions --------
$result = pg_query($con, "
    SELECT
        COALESCE(SUM(CASE WHEN type = 'earning' THEN amount ELSE 0 END), 0)  AS total_earnings,
        COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0)  AS total_expenses,
        COALESCE(SUM(CASE WHEN type = 'earning' THEN amount ELSE -amount END), 0) AS current_balance
    FROM cashflow_transactions
");

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
        'total_earnings'  => (float)($row['total_earnings']  ?? 0),
        'total_expenses'  => (float)($row['total_expenses']  ?? 0),
        'current_balance' => (float)($row['current_balance'] ?? 0),
    ]
]);
