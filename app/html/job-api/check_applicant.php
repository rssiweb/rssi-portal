<?php
require_once __DIR__ . "/../../bootstrap.php";

header('Content-Type: application/json');

try {
    $phone = $_POST['phone'] ?? '';
    $job_id = $_POST['job_id'] ?? 0;
    
    if (empty($phone)) {
        echo json_encode([
            'success' => false,
            'message' => 'Phone number is required'
        ]);
        exit;
    }
    
    // Check if applicant exists
    $query = "SELECT id, name, email, status 
              FROM job_seeker_data 
              WHERE contact = $1 
              AND status = 'Active' 
              LIMIT 1";
    
    $result = pg_query_params($con, $query, [$phone]);
    
    if ($result && pg_num_rows($result) > 0) {
        $applicant = pg_fetch_assoc($result);
        
        // Check if already applied for this job
        $check_application = "SELECT id FROM job_applications 
                              WHERE job_seeker_id = $1 
                              AND job_id = $2 
                              LIMIT 1";
        $app_result = pg_query_params($con, $check_application, [$applicant['id'], $job_id]);
        
        $already_applied = ($app_result && pg_num_rows($app_result) > 0);
        
        if ($already_applied) {
            echo json_encode([
                'success' => false,
                'message' => 'You have already applied for this job'
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'exists' => true,
                'applicant_id' => $applicant['id'],
                'name' => $applicant['name'],
                'email' => $applicant['email']
            ]);
        }
    } else {
        echo json_encode([
            'success' => true,
            'exists' => false
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>