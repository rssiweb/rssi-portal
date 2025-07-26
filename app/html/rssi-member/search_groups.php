<?php
require_once __DIR__ . "/../../bootstrap.php";

header('Content-Type: application/json');

$searchTerm = isset($_GET['q']) ? trim($_GET['q']) : '';

$query = "SELECT 
    g.group_id as id, 
    g.group_name as text,
    g.description
FROM stock_item_groups g
WHERE 1=1";

if (!empty($searchTerm)) {
    $query .= " AND (g.group_name ILIKE '%" . pg_escape_string($con, $searchTerm) . "%' OR g.description ILIKE '%" . pg_escape_string($con, $searchTerm) . "%')";
}

$query .= " ORDER BY g.group_name";

$result = pg_query($con, $query);
$groups = [];

while ($row = pg_fetch_assoc($result)) {
    $groups[] = [
        'id' => $row['id'],
        'text' => $row['text'],
        'description' => $row['description']
    ];
}

echo json_encode(['results' => $groups]);