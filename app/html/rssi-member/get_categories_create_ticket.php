<?php
require_once __DIR__ . "/../../bootstrap.php";

// Add error handling and proper headers
header('Content-Type: application/json');

try {
    if (!isset($_GET['action']) || empty($_GET['action'])) {
        echo json_encode([]);
        exit;
    }

    $action = pg_escape_string($con, $_GET['action']);

    $query = "SELECT category_name FROM ticket_categories WHERE category_type = $1 ORDER BY category_name";
    $result = pg_query_params($con, $query, array($action));

    if (!$result) {
        throw new Exception('Database query failed');
    }

    $categories = [];
    while ($row = pg_fetch_assoc($result)) {
        $categories[] = $row['category_name'];
    }

    echo json_encode($categories);
} catch (Exception $e) {
    // Log the error for debugging
    error_log("Error in get_categories.php: " . $e->getMessage());

    // Return empty array on error
    echo json_encode([]);
}
exit;