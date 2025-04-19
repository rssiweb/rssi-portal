<?php
require_once __DIR__ . "/../../bootstrap.php";

$studentId = $_GET['student_id'] ?? '';

if (empty($studentId)) {
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(['error' => 'Student ID is required']);
    exit;
}

// Get student info
$studentQuery = "SELECT class, type_of_admission FROM rssimyprofile_student WHERE student_id = $1";
$studentResult = pg_query_params($con, $studentQuery, [$studentId]);
$student = pg_fetch_assoc($studentResult);

if (!$student) {
    header("HTTP/1.1 404 Not Found");
    echo json_encode(['error' => 'Student not found']);
    exit;
}

// Determine student type
$studentType = (in_array($student['type_of_admission'], ['New Admission', 'Transfer Admission'])) 
             ? 'New' : 'Existing';

// Get current applicable fees
$feeQuery = "SELECT fc.id, fc.category_name, fs.amount
             FROM fee_structure fs
             JOIN fee_categories fc ON fs.category_id = fc.id
             WHERE fs.class = $1
             AND fs.student_type = $2
             AND CURRENT_DATE BETWEEN fs.effective_from AND COALESCE(fs.effective_until, '9999-12-31')";
$feeResult = pg_query_params($con, $feeQuery, [$student['class'], $studentType]);
$feeItems = pg_fetch_all($feeResult) ?? [];

// Prepare response
$response = [
    'categories' => array_map(function($item) {
        return [
            'id' => $item['id'],
            'name' => $item['category_name'],
            'amount' => (float)$item['amount']
        ];
    }, $feeItems)
];

header('Content-Type: application/json');
echo json_encode($response);