<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid concession ID']);
    exit;
}

$concessionId = (int)$_GET['id'];

try {
    $query = "SELECT h.*, u.fullname as changed_by_name,
                     TO_CHAR(h.changed_at, 'DD Mon YYYY HH24:MI') as formatted_date
              FROM concession_history h
              JOIN rssimyaccount_members u ON h.changed_by = u.associatenumber
              WHERE h.concession_id = $1
              ORDER BY h.changed_at DESC";
    
    $result = pg_query_params($con, $query, [$concessionId]);
    
    if (!$result) {
        throw new Exception(pg_last_error($con));
    }
    
    $history = pg_fetch_all($result) ?: [];
    
    echo json_encode([
        'success' => true,
        'data' => $history
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

if (isset($result)) pg_free_result($result);
?>