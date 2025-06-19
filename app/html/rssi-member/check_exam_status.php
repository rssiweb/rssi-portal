<?php
require_once __DIR__ . "/../../bootstrap.php";

$exam_ids = explode(',', $_GET['exam_ids']);
$query = "SELECT exam_id, subject, estatus FROM exams WHERE exam_id = ANY($1)";
$result = pg_query_params($con, $query, ['{' . implode(',', $exam_ids) . '}']);

echo json_encode(pg_fetch_all($result, PGSQL_ASSOC) ?: []);