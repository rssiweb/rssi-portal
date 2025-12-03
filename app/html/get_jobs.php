<?php
require_once __DIR__ . "/../bootstrap.php";

header('Content-Type: application/json');

try {
    // Get filter parameters
    $search = $_GET['search'] ?? '';
    $job_type = $_GET['job_type'] ?? '';
    $sort_by = $_GET['sort_by'] ?? 'newest';
    
    // Build base query
    $query = "SELECT jp.*, r.company_name 
              FROM job_posts jp 
              JOIN recruiters r ON jp.recruiter_id = r.id 
              WHERE jp.status = 'approved' 
              AND jp.apply_by >= CURRENT_DATE";
    
    $params = [];
    
    // Add search filter
    if (!empty($search)) {
        $query .= " AND (jp.job_title ILIKE $1 OR r.company_name ILIKE $1 OR jp.location ILIKE $1)";
        $params[] = "%$search%";
    }
    
    // Add job type filter
    if (!empty($job_type)) {
        $param_index = count($params) + 1;
        $query .= " AND jp.job_type = $" . $param_index;
        $params[] = $job_type;
    }
    
    // Add sorting
    switch ($sort_by) {
        case 'apply_by':
            $query .= " ORDER BY jp.apply_by ASC";
            break;
        case 'salary_high':
            $query .= " ORDER BY jp.salary DESC NULLS LAST";
            break;
        case 'salary_low':
            $query .= " ORDER BY jp.salary ASC NULLS LAST";
            break;
        case 'newest':
        default:
            $query .= " ORDER BY jp.created_at DESC";
            break;
    }
    
    // Execute query
    if (!empty($params)) {
        $result = pg_query_params($con, $query, $params);
    } else {
        $result = pg_query($con, $query);
    }
    
    if ($result) {
        $jobs = pg_fetch_all($result) ?: [];
        
        echo json_encode([
            'success' => true,
            'data' => $jobs,
            'count' => count($jobs)
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . pg_last_error($con)
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>