<?php
require_once __DIR__ . "/../../bootstrap.php";

$student_ids = explode(',', $_POST['student_ids']);
$exam_ids = explode(',', $_POST['exam_ids']);

$query = "SELECT em.student_id, e.subject 
          FROM exam_marks_data em
          JOIN exams e ON em.exam_id = e.exam_id
          WHERE em.student_id = ANY($1) AND em.exam_id = ANY($2)";

$result = pg_query_params($con, $query, [
    '{' . implode(',', $student_ids) . '}',
    '{' . implode(',', $exam_ids) . '}'
]);

echo json_encode(pg_fetch_all($result, PGSQL_ASSOC) ?: []);