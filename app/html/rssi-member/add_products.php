<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

header('Content-Type: application/json');
if (!isLoggedIn("aid")) { echo json_encode(['success'=>false,'error'=>'Not authenticated']); exit; }

$aid = $_SESSION['aid'];
$rows = $_POST['rows'] ?? [];

if (!is_array($rows) || !count($rows)) {
    echo json_encode(['success'=>false,'error'=>'No rows received']); exit;
}

// Reuse the same Google Drive upload helper you use for cashflow receipts
include("../../util/drive.php");

$inserted = 0;
$errors   = [];

pg_query($con, "BEGIN");

try {
    foreach ($rows as $idx => $r) {
        $serial = trim($r['serial_number'] ?? '');
        $name   = trim($r['item_name'] ?? '');
        $cost   = (float)($r['cost_price'] ?? 0);
        $make   = (float)($r['making_charges'] ?? 0);
        $sell   = (float)($r['selling_price'] ?? 0);
        $disc   = (float)($r['discount'] ?? 0);
        $qty    = max(1, (int)($r['quantity'] ?? 1));

        if (!$serial || !$name) continue;

        // break-even (min_sell_price) = (cost+making)/qty
        $minSell = $qty > 0 ? ($cost + $make) / $qty : 0;

        // Handle image upload (optional) — reuse your existing Drive helper
        $imageUrl = null;
        if (!empty($_FILES['rows']['name'][$idx]['image'])) {
            $imageUrl = upload_product_image_to_drive(
                $_FILES['rows']['tmp_name'][$idx]['image'],
                $_FILES['rows']['name'][$idx]['image'],
                $_FILES['rows']['type'][$idx]['image']
            );
        }

        $sql = "INSERT INTO product_listings
                (serial_number, item_name, category, cost_price, min_sell_price, selling_price,
                 making_charges, discount, quantity, image_drive_url, notes, listed_by)
                VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12) RETURNING id";
        $res = pg_query_params($con, $sql, [
            $serial, $name, $r['category'] ?? null,
            $cost, $minSell, $sell, $make, $disc, $qty,
            $imageUrl, $r['notes'] ?? null, $aid
        ]);
        if (!$res) throw new Exception(pg_last_error($con));
        $inserted++;
    }
    pg_query($con, "COMMIT");
    echo json_encode(['success'=>true, 'inserted'=>$inserted]);
} catch (Exception $e) {
    pg_query($con, "ROLLBACK");
    echo json_encode(['success'=>false, 'error'=>$e->getMessage()]);
}