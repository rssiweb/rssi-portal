<?php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$response = ['success' => false, 'message' => ''];

try {
    if (empty($_POST['id'])) {
        throw new Exception('Job ID is required');
    }
    
    $jobId = $_POST['id'];
    
    // Check if job exists
    $checkQuery = "SELECT id FROM job_posts WHERE id = $1";
    $checkResult = pg_query_params($con, $checkQuery, [$jobId]);
    
    if (pg_num_rows($checkResult) === 0) {
        throw new Exception('Job not found');
    }
    
    // Option 1: Soft delete (recommended)
    $deleteQuery = "UPDATE job_posts SET status = 'deleted', updated_at = NOW() WHERE id = $1";
    
    // Option 2: Hard delete (use with caution)
    // $deleteQuery = "DELETE FROM jobs WHERE id = $1";
    
    $result = pg_query_params($con, $deleteQuery, [$jobId]);
    
    if (!$result) {
        throw new Exception('Database error: ' . pg_last_error($con));
    }
    
    $response['success'] = true;
    $response['message'] = 'Job deleted successfully';
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);