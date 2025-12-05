<?php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$response = ['success' => false, 'message' => '', 'new_job_id' => null];

try {
    if (empty($_POST['id'])) {
        throw new Exception('Job ID is required');
    }

    $jobId = $_POST['id'];

    // Fetch job to duplicate
    $query = "SELECT * FROM job_posts WHERE id = $1";
    $result = pg_query_params($con, $query, [$jobId]);

    if (pg_num_rows($result) === 0) {
        throw new Exception('Job not found');
    }

    $job = pg_fetch_assoc($result);

    // Create duplicate with "Copy" in title
    $newJobTitle = $job['job_title'] . ' (Copy)';
    $newStatus = 'draft'; // Set duplicate as draft

    $insertQuery = "INSERT INTO job_posts (
        recruiter_id, job_title, job_type, location, min_salary, max_salary,
        vacancies, job_description, requirements, benefits, experience,
        education_levels, apply_by, status, job_file_path,
        admin_notes, created_at, updated_at
    ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16, NOW(), NOW()) 
    RETURNING id";

    $params = [
        $job['recruiter_id'],
        $newJobTitle,
        $job['job_type'],
        $job['location'],
        $job['min_salary'],
        $job['max_salary'],
        $job['vacancies'],
        $job['job_description'],
        $job['requirements'],
        $job['benefits'],
        $job['experience'],
        $job['education_levels'],
        $job['apply_by'],
        $newStatus,
        $job['job_file_path'],
        $job['admin_notes'],
    ];

    $insertResult = pg_query_params($con, $insertQuery, $params);

    if (!$insertResult) {
        throw new Exception('Failed to duplicate job: ' . pg_last_error($con));
    }

    $newJobId = pg_fetch_result($insertResult, 0, 0);

    $response['success'] = true;
    $response['message'] = 'Job duplicated successfully';
    $response['new_job_id'] = $newJobId;
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
