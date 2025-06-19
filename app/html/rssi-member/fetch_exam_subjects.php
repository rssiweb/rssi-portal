<?php
require_once __DIR__ . "/../../bootstrap.php";

$year = $_GET['academic_year'] ?? '';
$type = $_GET['exam_type'] ?? '';
$class = $_GET['class'] ?? '';

$query = "SELECT DISTINCT e.exam_id, e.subject
FROM exams e
LEFT JOIN exam_marks_data em ON e.exam_id = em.exam_id
WHERE e.academic_year = $1 
  AND e.exam_type = $2 
  AND em.class = $3 
  AND e.estatus IS NULL
ORDER BY e.subject
";

$result = pg_query_params($con, $query, [$year, $type, $class]);
$rows = pg_fetch_all($result, PGSQL_ASSOC) ?: [];

header('Content-Type: application/json');
echo json_encode($rows);
