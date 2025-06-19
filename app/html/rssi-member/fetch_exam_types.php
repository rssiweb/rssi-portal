<?php 
require_once __DIR__ . "/../../bootstrap.php";

$year = $_GET['academic_year'] ?? '';

$query = "SELECT DISTINCT exam_type FROM exams 
          WHERE academic_year = $1 AND estatus IS NULL
          ORDER BY exam_type";

$result = pg_query_params($con, $query, [$year]);

$rows = pg_fetch_all($result, PGSQL_ASSOC) ?: [];

// Flatten to indexed array
$exam_types = array_values(array_column($rows, 'exam_type')); // <-- force numeric indexing

header('Content-Type: application/json');
echo json_encode($exam_types);
