<?php
require_once __DIR__ . "/../../bootstrap.php";

header('Content-Type: application/json');

// Get parameters
$forStockManagement = isset($_GET['for_stock_management']) && $_GET['for_stock_management'] == 'true';
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build the base query
$query = "SELECT
    i.item_id,
    i.item_name,
    i.image_url,
    i.description,
    u.unit_id,
    u.unit_name,
    p.unit_quantity,
    COALESCE((SELECT SUM(quantity_received) FROM stock_add WHERE item_id = i.item_id AND unit_id = u.unit_id), 0) -
    COALESCE((SELECT SUM(quantity_distributed) FROM stock_out WHERE item_distributed = i.item_id AND unit = u.unit_id), 0) AS in_stock,
    p.price_per_unit,
    p.discount_percentage,
    p.original_price,
    i.rating,
    i.review_count,
    i.is_featured
FROM 
    stock_item i
LEFT JOIN stock_add sa ON i.item_id = sa.item_id
LEFT JOIN stock_out so ON i.item_id = so.item_distributed
JOIN stock_item_unit u ON u.unit_id = sa.unit_id OR u.unit_id = so.unit
LEFT JOIN stock_item_price p ON p.item_id = i.item_id 
    AND p.unit_id = u.unit_id
    AND CURRENT_DATE BETWEEN p.effective_start_date AND COALESCE(p.effective_end_date, CURRENT_DATE)
WHERE 1=1";

// Apply access scope filter
if ($forStockManagement) {
    $query .= " AND (i.access_scope IS NULL OR i.access_scope != 'public')";
} else {
    $query .= " AND i.access_scope = 'public'";
}

// Add search condition
if (!empty($searchTerm)) {
    $query .= " AND (i.item_name ILIKE '%" . pg_escape_string($con, $searchTerm) . "%' OR i.description ILIKE '%" . pg_escape_string($con, $searchTerm) . "%')";
}

$query .= " GROUP BY 
    i.item_id, i.item_name, i.description, i.rating, i.review_count, i.is_featured,
    u.unit_id, u.unit_name, p.price_per_unit, p.unit_quantity, p.discount_percentage, p.original_price
ORDER BY 
    i.is_featured DESC, i.item_name";

// Only apply pagination for non-stock management cases
if (!$forStockManagement) {
    // Get total count for pagination
    $countQuery = "SELECT COUNT(DISTINCT i.item_id) as total FROM stock_item i 
                   LEFT JOIN stock_add sa ON i.item_id = sa.item_id
                   LEFT JOIN stock_out so ON i.item_id = so.item_distributed
                   JOIN stock_item_unit u ON u.unit_id = sa.unit_id OR u.unit_id = so.unit
                   WHERE 1=1";
    
    if ($forStockManagement) {
        $countQuery .= " AND (i.access_scope IS NULL OR i.access_scope != 'public')";
    } else {
        $countQuery .= " AND i.access_scope = 'public'";
    }

    if (!empty($searchTerm)) {
        $countQuery .= " AND (i.item_name ILIKE '%" . pg_escape_string($con, $searchTerm) . "%' OR i.description ILIKE '%" . pg_escape_string($con, $searchTerm) . "%')";
    }

    $countResult = pg_query($con, $countQuery);
    $totalItems = pg_fetch_assoc($countResult)['total'];
    $itemsPerPage = isset($_GET['itemsPerPage']) ? max(5, min(100, intval($_GET['itemsPerPage']))) : 5;
    $currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $totalPages = ceil($totalItems / $itemsPerPage);
    $query .= " LIMIT $itemsPerPage OFFSET " . (($currentPage - 1) * $itemsPerPage);
}

$result = pg_query($con, $query);

$products = [];
if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $products[] = [
            'id' => (int)$row['item_id'],
            'name' => $row['item_name'],
            'price' => (float)$row['price_per_unit'],
            'original_price' => isset($row['original_price']) ? (float)$row['original_price'] : (float)$row['price_per_unit'],
            'image' => $row['image_url'],
            'description' => $row['description'] ?? '',
            'unit_name' => $row['unit_name'],
            'unit_id' => $row['unit_id'],
            'unit_quantity' => $row['unit_quantity'] ?? 1,
            'in_stock' => $row['in_stock'],
            'soldOut' => $row['in_stock'] <= 0,
            'discount_percentage' => (float)$row['discount_percentage'] ?? 0,
            'rating' => (float)$row['rating'] ?? 0,
            'review_count' => (int)$row['review_count'] ?? 0,
            'is_featured' => $row['is_featured'] ?? false
        ];
    }
}

// Return different response formats based on context
if ($forStockManagement) {
    echo json_encode([
        'results' => $products
    ]);
} else {
    echo json_encode([
        'products' => $products,
        'totalPages' => $totalPages,
        'currentPage' => $currentPage,
        'totalItems' => $totalItems
    ]);
}