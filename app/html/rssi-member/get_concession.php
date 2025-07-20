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
    $query = "SELECT sc.*, 
                     s.studentname, 
                     s.class,
                     TO_CHAR(sc.effective_from, 'YYYY-MM') as effective_from_month,
                     TO_CHAR(sc.effective_until, 'YYYY-MM') as effective_until_month
              FROM student_concessions sc
              JOIN rssimyprofile_student s ON sc.student_id = s.student_id
              WHERE sc.id = $1";
    
    $result = pg_query_params($con, $query, [$concessionId]);
    
    if (!$result) {
        throw new Exception(pg_last_error($con));
    }
    
    $concession = pg_fetch_assoc($result);
    
    if (!$concession) {
        echo json_encode(['success' => false, 'message' => 'Concession not found']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $concession
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

if (isset($result)) pg_free_result($result);
?>