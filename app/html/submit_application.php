<?php
require_once __DIR__ . "/../bootstrap.php";

header('Content-Type: application/json');

try {
    // Get form data
    $phone = $_POST['phone'] ?? '';
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $age = $_POST['age'] ?? '';
    $education = $_POST['education'] ?? '';
    $skills = $_POST['skills'] ?? '';
    $preferences = $_POST['preferences'] ?? '';
    $address = $_POST['address'] ?? '';
    $job_id = $_POST['job_id'] ?? 0;
    
    // Validate required fields
    if (empty($phone) || empty($name) || empty($age) || empty($education) || empty($skills) || empty($job_id)) {
        echo json_encode([
            'success' => false,
            'message' => 'All required fields must be filled'
        ]);
        exit;
    }
    
    // Start transaction
    pg_query($con, "BEGIN");
    
    // Insert into job_seeker_data
    $seeker_query = "INSERT INTO job_seeker_data 
                     (name, contact, email, age, education, skills, preferences, address1, status, created_at) 
                     VALUES ($1, $2, $3, $4, $5, $6, $7, $8, 'Active', NOW()) 
                     RETURNING id";
    
    $seeker_result = pg_query_params($con, $seeker_query, [
        $name, $phone, $email, $age, $education, $skills, $preferences, $address
    ]);
    
    if (!$seeker_result) {
        pg_query($con, "ROLLBACK");
        echo json_encode([
            'success' => false,
            'message' => 'Failed to save applicant data'
        ]);
        exit;
    }
    
    $seeker_row = pg_fetch_assoc($seeker_result);
    $seeker_id = $seeker_row['id'];
    
    // Insert into job_applications
    $application_query = "INSERT INTO job_applications 
                          (job_seeker_id, job_id, application_date, status) 
                          VALUES ($1, $2, NOW(), 'Applied') 
                          RETURNING id";
    
    $application_result = pg_query_params($con, $application_query, [$seeker_id, $job_id]);
    
    if (!$application_result) {
        pg_query($con, "ROLLBACK");
        echo json_encode([
            'success' => false,
            'message' => 'Failed to submit application'
        ]);
        exit;
    }
    
    // Get job title for email
    $job_query = "SELECT job_title FROM job_posts WHERE id = $1";
    $job_result = pg_query_params($con, $job_query, [$job_id]);
    $job_title = '';
    if ($job_result && pg_num_rows($job_result) > 0) {
        $job = pg_fetch_assoc($job_result);
        $job_title = $job['job_title'];
    }
    
    // Commit transaction
    pg_query($con, "COMMIT");
    
    echo json_encode([
        'success' => true,
        'message' => 'Application submitted successfully',
        'applicant_id' => $seeker_id,
        'applicant_name' => $name,
        'email' => $email,
        'job_title' => $job_title,
        'job_id' => $job_id  // Add this line
    ]);
    
} catch (Exception $e) {
    pg_query($con, "ROLLBACK");
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>