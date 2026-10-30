<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

header('Content-Type: application/json');
if (!isLoggedIn("aid")) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

$aid     = $_SESSION['aid'];
$body    = json_decode(file_get_contents('php://input'), true) ?: [];
$items   = $body['items'] ?? [];
$buyer   = trim($body['buyer_name'] ?? '');
$contact = trim($body['buyer_contact'] ?? '');

if (!is_array($items) || !count($items)) {
    echo json_encode(['status' => 'error', 'message' => 'No items']);
    exit;
}

pg_query($con, "BEGIN");
try {
    $sold = 0;
    foreach ($items as $it) {
        $id    = (int)($it['id'] ?? 0);
        $qty   = (int)($it['qty_sold'] ?? 0);
        $price = (float)($it['sell_price'] ?? 0);
        $disc  = (float)($it['discount'] ?? 0);
        if ($id <= 0 || $qty <= 0 || $price <= 0) {
            throw new Exception("Invalid row for product $id");
        }

        $lock = pg_query_params(
            $con,
            "SELECT quantity, qty_sold FROM product_listings WHERE id = $1 FOR UPDATE",
            [$id]
        );
        if (!$lock || pg_num_rows($lock) === 0) throw new Exception("Product $id not found");
        $p = pg_fetch_assoc($lock);

        $remaining = (int)$p['quantity'] - (int)$p['qty_sold'];
        if ($remaining <= 0) throw new Exception("Product $id: already sold out");
        if ($qty > $remaining) throw new Exception("Product $id: only $remaining left");

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

        // Same fix as mark_product_sold.php — don't reuse $2 in a CASE comparison,
        // otherwise Postgres fails with "inconsistent types deduced for parameter".
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

        $sold++;
    }
    pg_query($con, "COMMIT");
    echo json_encode(['status' => 'success', 'sold' => $sold]);
} catch (Exception $e) {
    pg_query($con, "ROLLBACK");
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
