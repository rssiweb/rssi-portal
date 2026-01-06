<?php
require_once __DIR__ . "/../../bootstrap.php";

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    // Fetch categories sorted by name
    $sql = "SELECT id, name, slug, description 
            FROM blog_categories 
            ORDER BY name";

    $result = pg_query($con, $sql);

    if (!$result) {
        throw new Exception('Database query failed: ' . pg_last_error($con));
    }

    $categories = [];
    while ($row = pg_fetch_assoc($result)) {
        $categories[] = $row;
    }

    echo json_encode([
        'success' => true,
        'categories' => $categories,
        'count' => count($categories)
    ]);
} catch (Exception $e) {
    error_log('Categories fetch error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load categories: ' . $e->getMessage(),
        'categories' => []
    ]);
}
