<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

header('Content-Type: application/json');
if (!isLoggedIn("aid")) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

$aid     = $_SESSION['aid'];
$id      = (int)($_POST['id'] ?? 0);
$qty     = (int)($_POST['qty_sold'] ?? 0);
$price   = (float)($_POST['sell_price'] ?? 0);
$disc    = (float)($_POST['discount'] ?? 0);
$buyer   = trim($_POST['buyer_name'] ?? '');
$contact = trim($_POST['buyer_contact'] ?? '');

if ($id <= 0 || $qty <= 0 || $price <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit;
}

pg_query($con, "BEGIN");
try {
    // Lock the row so concurrent sales can't oversell.
    $lock = pg_query_params(
        $con,
        "SELECT id, quantity, qty_sold, status FROM product_listings WHERE id = $1 FOR UPDATE",
        [$id]
    );
    if (!$lock || pg_num_rows($lock) === 0) throw new Exception('Product not found');
    $p = pg_fetch_assoc($lock);

    $remaining = (int)$p['quantity'] - (int)$p['qty_sold'];
    if ($remaining <= 0) throw new Exception('Already sold out');
    if ($qty > $remaining) throw new Exception("Only $remaining unit(s) available");

    $ins = pg_query_params(
        $con,
        "INSERT INTO product_sales (product_id, qty_sold, sell_price, discount,
                                    buyer_name, buyer_contact, sold_by)
         VALUES ($1,$2,$3,$4,$5,$6,$7)",
        [$id, $qty, $price, $disc, $buyer ?: null, $contact ?: null, $aid]
    );
    if (!$ins) throw new Exception(pg_last_error($con));

    $newSold  = (int)$p['qty_sold'] + $qty;
    $newState = $newSold >= (int)$p['quantity'] ? 'sold'
        : ($newSold > 0 ? 'partial' : 'available');

    // Avoid reusing $2 in a CASE comparison — Postgres can't infer a single
    // type for a param that is both assigned to varchar and compared to text.
    $soldAtSql = ($newState === 'sold') ? 'NOW()' : 'sold_at';

    $upd = pg_query_params(
        $con,
        "UPDATE product_listings
            SET qty_sold   = $1,
                status     = $2,
                sold_at    = $soldAtSql,
                updated_at = NOW()
          WHERE id = $3",
        [$newSold, $newState, $id]
    );
    if (!$upd) throw new Exception(pg_last_error($con));

    pg_query($con, "COMMIT");
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    pg_query($con, "ROLLBACK");
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
