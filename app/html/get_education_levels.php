<?php
require_once __DIR__ . "/../bootstrap.php";

header('Content-Type: application/json');

try {
    // Assuming you have an education_levels table
    // If not, you can return a static list
    $query = "SELECT id, name FROM education_levels WHERE status = 'Active' ORDER BY sort_order";
    $result = pg_query($con, $query);

    if ($result) {
        $education_levels = pg_fetch_all($result) ?: [];

        echo json_encode([
            'success' => true,
            'data' => $education_levels
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to fetch education levels'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
