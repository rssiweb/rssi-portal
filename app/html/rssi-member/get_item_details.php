<?php
// Assuming you have established a PostgreSQL database connection
require_once __DIR__ . "/../../bootstrap.php";

// Retrieve the item details based on the item code received
$itemCode = $_POST['itemCode'];

$query = "SELECT * FROM stock_item WHERE item_code = $itemCode";
$result = pg_query($connection, $query);

if ($row = pg_fetch_assoc($result)) {
    // Send the item details as a JSON response
    echo json_encode($row);
} else {
    // Send an error message if the item code is not found
    echo json_encode(['error' => 'Item not found']);
}
?>
