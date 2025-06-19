<?php
require_once __DIR__ . "/../../bootstrap.php";

header('Content-Type: application/json');

// Verify database connection
if (!isset($con) || !($con instanceof PgSql\Connection)) {
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed'
    ]);
    exit;
}

try {
    // Validate input
    if (empty($_POST['student_ids']) || empty($_POST['exam_ids'])) {
        throw new Exception('Missing required parameters');
    }

    $student_ids = array_filter(explode(',', $_POST['student_ids']));
    $exam_ids = array_filter(explode(',', $_POST['exam_ids']));

    if (empty($student_ids) || empty($exam_ids)) {
        throw new Exception('Invalid student_ids or exam_ids format');
    }

    // Prepare parameterized query
    $query = "SELECT e.exam_id as exam_id, emd.student_id, e.subject 
              FROM exam_marks_data emd
              JOIN exams e ON emd.exam_id = e.exam_id
              WHERE emd.student_id = ANY($1)
              AND e.exam_id = ANY($2)";

    // Convert arrays to PostgreSQL array format
    $student_ids_param = '{' . implode(',', $student_ids) . '}';
    $exam_ids_param = '{' . implode(',', $exam_ids) . '}';

    // Execute query with parameters
    $result = pg_query_params($con, $query, [$student_ids_param, $exam_ids_param]);

    if (!$result) {
        throw new Exception('Database query failed: ' . pg_last_error($con));
    }

    $enrollments = [];
    while ($row = pg_fetch_assoc($result)) {
        $enrollments[] = [
            'exam_id' => $row['exam_id'],
            'student_id' => $row['student_id'],
            'subject' => $row['subject'] ?? 'Unknown'
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $enrollments
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}