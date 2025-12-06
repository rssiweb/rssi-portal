<?php
require_once __DIR__ . "/../../bootstrap.php";

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

$response = ['success' => false, 'data' => []];

try {
    $search = $_GET['search'] ?? '';
    $page = $_GET['page'] ?? 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    // Build query
    $query = "SELECT id, full_name, company_name, email 
              FROM recruiters 
              WHERE is_active = true";

    $params = [];
    if (!empty($search)) {
        $query .= " AND (company_name ILIKE $1 OR full_name ILIKE $1 OR email ILIKE $1)";
        $params[] = '%' . $search . '%';
    }

    $query .= " ORDER BY company_name LIMIT $limit OFFSET $offset";

    $result = pg_query_params($con, $query, $params);

    if (!$result) {
        throw new Exception('Database error');
    }

    $recruiters = pg_fetch_all($result) ?: [];

    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) as total FROM recruiters WHERE is_active = true";
    if (!empty($search)) {
        $countQuery .= " AND (company_name ILIKE $1 OR full_name ILIKE $1 OR email ILIKE $1)";
    }
    $countResult = pg_query_params($con, $countQuery, $params);
    $total = pg_fetch_result($countResult, 0, 'total');

    $response['success'] = true;
    $response['data'] = array_map(function ($recruiter) {
        return [
            'id' => $recruiter['id'],
            'text' => $recruiter['company_name'] . ' - ' . $recruiter['full_name'] . ' (' . $recruiter['email'] . ')',
            'email' => $recruiter['email'],
            'name' => $recruiter['full_name'],
            'company' => $recruiter['company_name']
        ];
    }, $recruiters);

    $response['pagination'] = [
        'more' => ($offset + $limit) < $total
    ];
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
