<?php
require_once __DIR__ . "/../../bootstrap.php";

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$response = ['success' => false, 'message' => ''];

try {
    if (!isset($_POST['id'])) {
        throw new Exception('Recruiter ID is required');
    }

    $recruiterId = $_POST['id'];

    // Check if recruiter exists
    $checkQuery = "SELECT id FROM recruiters WHERE id = $1";
    $checkResult = pg_query_params($con, $checkQuery, [$recruiterId]);

    if (pg_num_rows($checkResult) === 0) {
        throw new Exception('Recruiter not found');
    }

    // Soft delete (update is_active to false) OR hard delete
    // Option 1: Soft delete (recommended)
    $deleteQuery = "UPDATE recruiters SET is_active = false WHERE id = $1";

    // Option 2: Hard delete
    // $deleteQuery = "DELETE FROM recruiters WHERE id = $1";

    $result = pg_query_params($con, $deleteQuery, [$recruiterId]);

    if (!$result) {
        throw new Exception('Database error: ' . pg_last_error($con));
    }

    $response['success'] = true;
    $response['message'] = 'Recruiter deleted successfully';
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
