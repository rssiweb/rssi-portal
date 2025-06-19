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
$exam_ids = $_POST['exam_id'] ?? '';
$class = $_POST['class'] ?? [];
$category = $_POST['category'] ?? [];
$student_ids = $_POST['student_ids'] ?? '';
$excluded_ids = $_POST['excluded_ids'] ?? '';

// Convert comma-separated exam_ids to array and properly format for PostgreSQL
$exam_id_array = explode(',', $exam_ids);
$exam_id_array = array_map('trim', $exam_id_array);
$exam_id_array = array_filter($exam_id_array); // Remove empty values

// Validate that all selected exams are for the same class
$exam_classes_query = "SELECT DISTINCT class FROM exams 
LEFT JOIN exam_marks_data em ON exams.exam_id = em.exam_id
WHERE exams.exam_id = ANY($1)";
$exam_classes_result = pg_query_params($con, $exam_classes_query, ['{' . implode(',', $exam_id_array) . '}']);
$exam_classes = pg_fetch_all($exam_classes_result, PGSQL_ASSOC) ?: [];

if (count($exam_classes) > 1) {
    http_response_code(400);
    echo json_encode(['error' => 'Selected exams must be for the same class']);
    exit;
}

if (count($exam_classes) === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'No valid exams found']);
    exit;
}

$required_class = $exam_classes[0]['class'];

// Build the base query
$query = "SELECT s.student_id, s.studentname, s.category, s.class,
          (SELECT COUNT(*) FROM exam_marks_data em 
           WHERE em.student_id = s.student_id 
           AND em.exam_id = ANY($1)) AS exam_count
          FROM rssimyprofile_student s
          WHERE s.filterstatus = 'Active'";

$params = [];
$conditions = [];

// Format exam IDs for PostgreSQL array
$exam_ids_param = '{' . implode(',', $exam_id_array) . '}';
$params[] = $exam_ids_param;

// Automatically add class filter based on exam's class
$conditions[] = "s.class = $" . (count($params) + 1);
$params[] = $required_class;

// Additional filters (will be combined with AND)
if (!empty($class)) {
    // Warn if user is trying to filter by different class than exam's class
    $invalid_classes = array_diff($class, [$required_class]);
    if (!empty($invalid_classes)) {
        http_response_code(400);
        echo json_encode(['error' => 'Cannot filter by different class than exam class']);
        exit;
    }
}

if (!empty($category)) {
    $category_placeholders = implode(',', array_map(function($i) use ($params) { 
        return '$' . (count($params) + $i + 1); 
    }, array_keys($category)));
    $conditions[] = "s.category IN ($category_placeholders)";
    $params = array_merge($params, $category);
}

if (!empty($student_ids)) {
    $idList = array_filter(array_map('trim', explode(',', $student_ids)));
    if (!empty($idList)) {
        $student_placeholders = implode(',', array_map(function($i) use ($params) { 
            return '$' . (count($params) + $i + 1); 
        }, array_keys($idList)));
        $conditions[] = "s.student_id IN ($student_placeholders)";
        $params = array_merge($params, $idList);
    }
}

if (!empty($excluded_ids)) {
    $idList = array_filter(array_map('trim', explode(',', $excluded_ids)));
    if (!empty($idList)) {
        $exclude_placeholders = implode(',', array_map(function($i) use ($params) { 
            return '$' . (count($params) + $i + 1); 
        }, array_keys($idList)));
        $conditions[] = "s.student_id NOT IN ($exclude_placeholders)";
        $params = array_merge($params, $idList);
    }
}

if (count($conditions) > 0) {
    $query .= " AND " . implode(" AND ", $conditions);
}

$query .= " ORDER BY s.studentname";

// Debugging (remove in production)
error_log("Query: " . $query);
error_log("Params: " . print_r($params, true));

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