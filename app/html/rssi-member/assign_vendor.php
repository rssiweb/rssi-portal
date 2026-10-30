<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

header('Content-Type: application/json');
if (!isLoggedIn("aid")) { echo json_encode(['status'=>'error','message'=>'Not authenticated']); exit; }

$aid  = $_SESSION['aid'];
$body = json_decode(file_get_contents('php://input'), true) ?: [];
$ids  = array_values(array_filter(array_map('intval', $body['ids'] ?? [])));
$vid  = (int)($body['vendor_id'] ?? 0);

if (!count($ids)) { echo json_encode(['status'=>'error','message'=>'No items selected']); exit; }

pg_query($con, "BEGIN");
try {
    if ($vid > 0) {
        // Validate vendor exists
        $v = pg_query_params($con, "SELECT id FROM vendors WHERE id=$1 AND active", [$vid]);
        if (!$v || pg_num_rows($v) === 0) throw new Exception('Vendor not found or inactive');
    }

    foreach ($ids as $id) {
        // Close any open assignment on this product (return it first)
        pg_query_params($con,
            "UPDATE product_assignments
                SET returned_at = NOW(), return_note = 'auto-closed on reassignment'
              WHERE product_id = $1 AND returned_at IS NULL",
            [$id]);

        if ($vid > 0) {
            // How many units are still unsold? That's what we hand the vendor.
            $q = pg_query_params($con,
                "SELECT quantity - qty_sold AS avail FROM product_listings WHERE id=$1", [$id]);
            if (!$q || pg_num_rows($q) === 0) throw new Exception("Product $id not found");
            $avail = (int) pg_fetch_assoc($q)['avail'];
            if ($avail <= 0) continue; // nothing to assign

            pg_query_params($con,
                "INSERT INTO product_assignments (product_id, vendor_id, qty, assigned_by)
                 VALUES ($1,$2,$3,$4)",
                [$id, $vid, $avail, $aid]);

            pg_query_params($con,
                "UPDATE product_listings SET current_vendor_id=$1, updated_at=NOW() WHERE id=$2",
                [$vid, $id]);
        } else {
            pg_query_params($con,
                "UPDATE product_listings SET current_vendor_id=NULL, updated_at=NOW() WHERE id=$1",
                [$id]);
        }
    }
    pg_query($con, "COMMIT");
    echo json_encode(['status'=>'success','processed'=>count($ids)]);
} catch (Exception $e) {
    pg_query($con, "ROLLBACK");
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}