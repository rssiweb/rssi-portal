<?php
require_once __DIR__ . "/../../bootstrap.php";

header('Content-Type: application/json');

$groupId = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;

if ($groupId <= 0) {
    echo json_encode([]);
    exit;
}

$query = "SELECT 
    i.item_id,
    i.item_name,
    gi.quantity,
    u.unit_id,
    u.unit_name,
    COALESCE((SELECT SUM(quantity_received) FROM stock_add WHERE item_id = i.item_id AND unit_id = u.unit_id), 0) -
    COALESCE((SELECT SUM(quantity_distributed) FROM stock_out WHERE item_distributed = i.item_id AND unit = u.unit_id), 0) AS in_stock
FROM stock_item_group_items gi
JOIN stock_item i ON gi.item_id = i.item_id
JOIN stock_item_unit u ON gi.unit_id = u.unit_id
WHERE gi.group_id = $groupId";

$result = pg_query($con, $query);
$items = [];

while ($row = pg_fetch_assoc($result)) {
    $items[] = [
        'item_id' => $row['item_id'],
        'item_name' => $row['item_name'],
        'quantity' => $row['quantity'],
        'unit_id' => $row['unit_id'],
        'unit_name' => $row['unit_name'],
        'in_stock' => $row['in_stock']
    ];
}

echo json_encode($items);