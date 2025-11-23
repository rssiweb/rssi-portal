<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

$search  = $_GET['search']  ?? '';
$preload = $_GET['preload'] ?? '';

$response = [];

// ----------------------------------------------------
// 1. PRELOAD SELECTED IDs (for Select2 initial values)
// ----------------------------------------------------
if (!empty($preload)) {

    // Convert "1,2,3" → [1,2,3] (safe array)
    $ids = array_filter(array_map('intval', explode(',', $preload)));

    if (!empty($ids)) {
        $query = "
            SELECT id, category_name 
            FROM fee_categories
            WHERE id IN (" . implode(',', $ids) . ")
        ";

        $result = pg_query($con, $query);

        while ($row = pg_fetch_assoc($result)) {
            $response[] = [
                'id'   => $row['id'],
                'category_name' => $row['category_name']
            ];
        }
    }

    echo json_encode($response);
    exit;
}

// ----------------------------------------------------
// 2. NORMAL AJAX SEARCH (Select2 live search)
// ----------------------------------------------------

// Escape search string properly (fixes deprecation warning)
$search_escaped = pg_escape_string($con, $search);

$query = "
    SELECT id, category_name
    FROM fee_categories
    WHERE category_name ILIKE '%{$search_escaped}%'
    ORDER BY category_name
";

$result = pg_query($con, $query);

while ($row = pg_fetch_assoc($result)) {
    $response[] = [
        'id'   => $row['id'],
        'category_name' => $row['category_name']
    ];
}

echo json_encode($response);
