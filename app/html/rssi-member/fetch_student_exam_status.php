<?php
require_once __DIR__ . "/../../bootstrap.php";

header('Content-Type: application/json');

// Verify database connection
if (!isset($con) || !($con instanceof PgSql\Connection)) {
    echo json_encode([
        'error' => 'Database connection failed'
    ]);
    exit;
}

try {
    // Validate input
    if (empty($_POST['student_ids']) || empty($_POST['exam_ids'])) {
        throw new Exception('Missing required parameters');
    }

    // Clean and prepare IDs
    $student_ids = array_map('trim', explode(',', $_POST['student_ids']));
    $exam_ids = array_map('trim', explode(',', $_POST['exam_ids']));
    
    // Remove empty values
    $student_ids = array_filter($student_ids);
    $exam_ids = array_filter($exam_ids);

    if (empty($student_ids) || empty($exam_ids)) {
        throw new Exception('Invalid student_ids or exam_ids format');
    }

    // Prepare parameterized query - modified to be more precise
    $query = "SELECT 
                emd.student_id, 
                e.exam_id, 
                e.subject,
                e.exam_type,
                e.academic_year
              FROM exam_marks_data emd
              JOIN exams e ON emd.exam_id = e.exam_id
              WHERE emd.student_id = ANY($1::text[])
              AND e.exam_id = ANY($2::text[])";

    // Convert arrays to PostgreSQL array format
    $student_ids_param = '{' . implode(',', array_map(function($id) use ($con) {
        return pg_escape_string($con, $id);
    }, $student_ids)) . '}';
    
    $exam_ids_param = '{' . implode(',', array_map(function($id) use ($con) {
        return pg_escape_string($con, $id);
    }, $exam_ids)) . '}';

    // Execute query with parameters
    $result = pg_query_params($con, $query, [$student_ids_param, $exam_ids_param]);

    if (!$result) {
        throw new Exception('Database query failed: ' . pg_last_error($con));
    }

    // Format results
    $enrollments = [];
    while ($row = pg_fetch_assoc($result)) {
        $enrollments[] = [
            'student_id' => $row['student_id'],
            'exam_id' => $row['exam_id'],
            'subject' => $row['subject'],
            'exam_type' => $row['exam_type'],
            'academic_year' => $row['academic_year']
        ];
    }

    // Return just the array of enrollments
    echo json_encode($enrollments);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}