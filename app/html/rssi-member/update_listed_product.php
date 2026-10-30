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
$sell    = isset($_POST['selling_price']) ? (float)$_POST['selling_price'] : null;
$disc    = isset($_POST['discount'])      ? (float)$_POST['discount']      : 0;
$qty     = isset($_POST['quantity'])      ? (int)$_POST['quantity']        : 0;
$notes   = trim($_POST['notes'] ?? '');

if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid product id']);
    exit;
}
if ($sell === null || $sell < 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid selling price']);
    exit;
}
if ($disc < 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid discount']);
    exit;
}
if ($qty <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Quantity must be at least 1']);
    exit;
}

pg_query($con, "BEGIN");
try {
    // Lock the row — we need qty_sold to validate the new quantity.
    $lock = pg_query_params(
        $con,
        "SELECT id, quantity, qty_sold FROM product_listings WHERE id = $1 FOR UPDATE",
        [$id]
    );
    if (!$lock || pg_num_rows($lock) === 0) throw new Exception('Product not found');
    $p = pg_fetch_assoc($lock);

    // Can't reduce quantity below what's already been sold.
    if ($qty < (int)$p['qty_sold']) {
        throw new Exception("Quantity can't be less than already-sold units (" . (int)$p['qty_sold'] . ")");
    }

    $newState = (int)$p['qty_sold'] >= $qty ? 'sold'
        : ((int)$p['qty_sold'] > 0 ? 'partial' : 'available');

    // NOTE: we must not touch sold_at. If the row was already 'sold' and the user
    // bumps quantity back up (making it available again), sold_at should stay as
    // historical record of the *first* full-sale moment. The next real sale will
    // overwrite it via mark_product_sold.php.

    $upd = pg_query_params(
        $con,
        "UPDATE product_listings
            SET selling_price = $1,
                discount      = $2,
                quantity      = $3,
                notes         = $4,
                status        = $5,
                updated_at    = NOW()
          WHERE id = $6",
        [$sell, $disc, $qty, $notes !== '' ? $notes : null, $newState, $id]
    );
    if (!$upd) throw new Exception(pg_last_error($con));

    pg_query($con, "COMMIT");
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    pg_query($con, "ROLLBACK");
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
