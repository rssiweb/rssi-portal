<?php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

$response = ['success' => false, 'data' => []];

try {
    if (!isset($_GET['id'])) {
        throw new Exception('Recruiter ID is required');
    }
    
    $recruiterId = $_GET['id'];
    
    $query = "SELECT * FROM recruiters WHERE id = $1";
    $result = pg_query_params($con, $query, [$recruiterId]);
    
    if (!$result) {
        throw new Exception('Database error');
    }
    
    $recruiter = pg_fetch_assoc($result);
    
    if (!$recruiter) {
        throw new Exception('Recruiter not found');
    }
    
    $response['success'] = true;
    $response['data'] = $recruiter;
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);