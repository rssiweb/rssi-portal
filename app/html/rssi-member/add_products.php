<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("../../util/drive.php");

header('Content-Type: application/json');

if (!isLoggedIn("aid")) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$aid  = $_SESSION['aid'];
$rows = $_POST['rows'] ?? [];

if (!is_array($rows) || !count($rows)) {
    echo json_encode(['success' => false, 'error' => 'No rows received']);
    exit;
}

// Drive folder for product images (put your own folder ID here)
$driveParentFolderId = '1HqvVEOZgKLetrHGEUaeS7SRlhlA0cYyp';

$inserted = 0;

pg_query($con, "BEGIN");

try {
    foreach ($rows as $idx => $r) {
        $rowLabel = 'Row ' . ($idx + 1);
        $serial = trim($r['serial_number'] ?? '');
        $name   = trim($r['item_name'] ?? '');
        $cost   = (float)($r['cost_price'] ?? 0);
        $make   = (float)($r['making_charges'] ?? 0);
        $sell   = (float)($r['selling_price'] ?? 0);
        $disc   = (float)($r['discount'] ?? 0);
        $qty    = max(1, (int)($r['quantity'] ?? 1));

        if (!$serial || !$name) continue;

        // break-even (min_sell_price) = (cost+making)/qty
        $minSell = ($cost + $make) / $qty;

        // ---- Optional image upload ----
        $imageUrl = null;
        $err = $_FILES['rows']['error'][$idx]['image'] ?? UPLOAD_ERR_NO_FILE;

        if ($err === UPLOAD_ERR_OK) {
            $file = [
                'name'     => $_FILES['rows']['name'][$idx]['image'],
                'type'     => $_FILES['rows']['type'][$idx]['image'],
                'tmp_name' => $_FILES['rows']['tmp_name'][$idx]['image'],
                'error'    => $err,
                'size'     => $_FILES['rows']['size'][$idx]['image'],
            ];

            if ($file['size'] > 5 * 1024 * 1024) {
                throw new Exception("$rowLabel: image too large (max 5MB).");
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                throw new Exception("$rowLabel: only JPG, PNG or WebP images are allowed.");
            }

            $safeSerial = preg_replace('/[^A-Za-z0-9_-]/', '', $serial);
            $baseName   = 'product_' . $safeSerial . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3));

            $imageUrl = uploadeToDrive($file, $driveParentFolderId, $baseName);

            if (!$imageUrl || !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                throw new Exception("$rowLabel: Drive upload failed.");
            }
        } elseif ($err !== UPLOAD_ERR_NO_FILE) {
            throw new Exception("$rowLabel: image upload error (code $err).");
        }

        $sql = "INSERT INTO product_listings
                (serial_number, item_name, category, cost_price, min_sell_price, selling_price,
                 making_charges, discount, quantity, image_drive_url, notes, listed_by)
                VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12) RETURNING id";
        $res = pg_query_params($con, $sql, [
            $serial,
            $name,
            $r['category'] ?? null,
            $cost,
            $minSell,
            $sell,
            $make,
            $disc,
            $qty,
            $imageUrl,
            $r['notes'] ?? null,
            $aid
        ]);
        if (!$res) throw new Exception($rowLabel . ': ' . pg_last_error($con));
        $inserted++;
    }

    pg_query($con, "COMMIT");
    echo json_encode(['success' => true, 'inserted' => $inserted]);
} catch (Throwable $e) {
    pg_query($con, "ROLLBACK");
    error_log('add_products failed: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
