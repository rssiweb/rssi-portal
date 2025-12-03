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
        
        // If no table exists, return default options
        if (empty($education_levels)) {
            $education_levels = [
                ['id' => '1', 'name' => '10th Pass'],
                ['id' => '2', 'name' => '12th Pass'],
                ['id' => '3', 'name' => 'Diploma'],
                ['id' => '4', 'name' => 'Graduate'],
                ['id' => '5', 'name' => 'Post Graduate'],
                ['id' => '6', 'name' => 'Doctorate']
            ];
        }
        
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
?>