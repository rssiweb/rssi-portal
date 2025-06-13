<?php
require_once __DIR__ . '/../bootstrap.php';
include(__DIR__ . "/../util/login_util.php");
header('Content-Type: application/json');

try {
    // Get division from query parameter
    $division = $_GET['division'] ?? '';

    // Validate input
    if (empty($division)) {
        throw new Exception('Division parameter is required');
    }

    // Prepare and execute query
    $query = "SELECT value, class_name FROM school_classes WHERE division = $1 ORDER BY id";
    $result = pg_query_params($con, $query, [$division]);

    // Check for query errors
    if (!$result) {
        throw new Exception('Database query failed: ' . pg_last_error($con));
    }

    // Fetch results
    $classes = pg_fetch_all($result) ?: [];

    // Return JSON response
    echo json_encode([
        'success' => true,
        'data' => $classes
    ]);
} catch (Exception $e) {
    // Return error as JSON
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
