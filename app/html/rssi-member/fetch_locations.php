<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

header('Content-Type: application/json');

if (!isLoggedIn("aid")) {
    // Match legacy behavior on auth failure (plain array), unless select2 format requested
    $format = isset($_GET['format']) ? $_GET['format'] : 'legacy';
    echo $format === 'select2' ? json_encode(['results' => []]) : json_encode([]);
    exit;
}

$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$format = isset($_GET['format']) ? $_GET['format'] : 'legacy'; // 'legacy' | 'select2'

if ($search !== '') {
    $query = "SELECT id, name, is_active FROM office_locations 
              WHERE is_active = TRUE AND name ILIKE $1 
              ORDER BY name";
    $result = pg_query_params($con, $query, ['%' . $search . '%']);
} else {
    $query = "SELECT id, name, is_active FROM office_locations 
              WHERE is_active = TRUE 
              ORDER BY name";
    $result = pg_query($con, $query);
}

$locations = [];
if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $locations[] = [
            'id'        => $row['id'],
            'name'      => $row['name'],      // legacy field (unchanged)
            'text'      => $row['name'],      // Select2 field
            'is_active' => $row['is_active']  // legacy field (unchanged)
        ];
    }
}

if ($format === 'select2') {
    echo json_encode(['results' => $locations]);
} else {
    echo json_encode($locations); // legacy: plain array, unchanged shape
}
