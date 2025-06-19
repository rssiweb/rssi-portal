<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

validation();

// Get filter parameters
$exam_id = $_POST['exam_id'] ?? null;
$class = $_POST['class'] ?? [];
$category = $_POST['category'] ?? [];
$student_ids = $_POST['student_ids'] ?? '';
$excluded_ids = $_POST['excluded_ids'] ?? '';

// Build the query
$query = "SELECT student_id, studentname, category, class 
          FROM rssimyprofile_student 
          WHERE filterstatus = 'Active'";

$params = [];
$types = '';
$conditions = [];

if (!empty($class)) {
    $placeholders = implode(',', array_map(function($i) { return '$'.$i; }, range(count($params)+1, count($params)+count($class))));
    $conditions[] = "class IN ($placeholders)";
    $params = array_merge($params, $class);
}

if (!empty($category)) {
    $placeholders = implode(',', array_map(function($i) { return '$'.$i; }, range(count($params)+1, count($params)+count($category))));
    $conditions[] = "category IN ($placeholders)";
    $params = array_merge($params, $category);
}

if (!empty($student_ids)) {
    $idList = array_filter(array_map('trim', explode(',', $student_ids)));
    if (!empty($idList)) {
        $placeholders = implode(',', array_map(function($i) { return '$'.$i; }, range(count($params)+1, count($params)+count($idList))));
        $conditions[] = "student_id IN ($placeholders)";
        $params = array_merge($params, $idList);
    }
}

if (!empty($excluded_ids)) {
    $idList = array_filter(array_map('trim', explode(',', $excluded_ids)));
    if (!empty($idList)) {
        $placeholders = implode(',', array_map(function($i) { return '$'.$i; }, range(count($params)+1, count($params)+count($idList))));
        $conditions[] = "student_id NOT IN ($placeholders)";
        $params = array_merge($params, $idList);
    }
}

if (count($conditions) > 0) {
    $query .= " AND " . implode(" AND ", $conditions);
}

$query .= " ORDER BY studentname";

// Debugging (you can remove this after testing)
error_log("Query: " . $query);
error_log("Params: " . print_r($params, true));

// Execute query
$result = pg_query_params($con, $query, $params);

if (!$result) {
    $error = pg_last_error($con);
    error_log("Database error: " . $error);
    http_response_code(500);
    echo json_encode(['error' => 'Database query failed', 'details' => $error]);
    exit;
}

$students = pg_fetch_all($result) ?: [];

header('Content-Type: application/json');
echo json_encode($students);