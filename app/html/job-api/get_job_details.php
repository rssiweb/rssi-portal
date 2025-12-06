<?php
require_once __DIR__ . "/../../bootstrap.php";

header('Content-Type: application/json');

try {
    $job_id = $_GET['id'] ?? 0;
    
    if (!$job_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Job ID is required'
        ]);
        exit;
    }
    
    $query = "SELECT jp.*, r.company_name, r.full_name as recruiter_name, el.name as education_level_name
              FROM job_posts jp 
              JOIN recruiters r ON jp.recruiter_id = r.id 
              LEFT JOIN education_levels el ON jp.education_levels = el.id 
              WHERE jp.id = $1 
              AND jp.status = 'approved'";
    
    $result = pg_query_params($con, $query, [$job_id]);
    
    if ($result && pg_num_rows($result) > 0) {
        $job = pg_fetch_assoc($result);
        
        echo json_encode([
            'success' => true,
            'data' => $job
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Job not found or not approved'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>