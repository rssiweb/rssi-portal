<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

header('Content-Type: application/json');

if (!isLoggedIn("aid")) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$associatenumber = $_GET['associatenumber'] ?? null;
$offset = isset($_GET['offset']) ? max(0, intval($_GET['offset'])) : 0;
$limit  = isset($_GET['limit'])  ? min(50, max(1, intval($_GET['limit']))) : 10;

if (!$associatenumber) {
    echo json_encode(['records' => [], 'has_more' => false]);
    exit;
}

// Non-Admin users can only view their own history
if ($role !== 'Admin' && $associatenumber !== $associatenumber) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$fetchLimit = $limit + 1; // fetch one extra to detect has_more

$query = "SELECT history_id, associatenumber, field_name, old_value, new_value,
                 changed_by, changed_at, effective_from, effective_to,
                 change_reason
          FROM associate_employment_history
          WHERE associatenumber = $1
            AND field_name IN ('engagement', 'job_type', 'position', 'grade')
          ORDER BY 
              COALESCE(effective_from, changed_at::date) DESC,
              changed_at DESC
          LIMIT $2 OFFSET $3";

$result = pg_query_params($con, $query, [$associatenumber, $fetchLimit, $offset]);

if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'details' => pg_last_error($con)]);
    exit;
}

$rows = pg_fetch_all($result) ?: [];

$hasMore = count($rows) > $limit;
if ($hasMore) {
    array_pop($rows);
}

echo json_encode([
    'records'  => $rows,
    'has_more' => $hasMore,
    'offset'   => $offset,
    'limit'    => $limit,
    'count'    => count($rows)
]);
