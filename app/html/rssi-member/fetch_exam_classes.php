<?php
require_once __DIR__ . "/../../bootstrap.php";

$year = $_GET['academic_year'] ?? '';
$type = $_GET['exam_type'] ?? '';

$query = "SELECT DISTINCT class FROM exams
          LEFT JOIN exam_marks_data em ON exams.exam_id = em.exam_id
          WHERE academic_year = $1 AND exam_type = $2 AND estatus IS NULL
          ORDER BY class";

$result = pg_query_params($con, $query, [$year, $type]);
$rows = pg_fetch_all($result, PGSQL_ASSOC) ?: [];

// Flatten to indexed array
$classes = array_values(array_column($rows, 'class'));

header('Content-Type: application/json');
echo json_encode($classes);
