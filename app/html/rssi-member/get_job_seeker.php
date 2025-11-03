<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID parameter required']);
    exit;
}

$id = intval($_GET['id']);

$query = "SELECT * FROM job_seeker_data WHERE id = $1";
$result = pg_query_params($con, $query, array($id));

if ($result && pg_num_rows($result) > 0) {
    $job_seeker = pg_fetch_assoc($result);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'id' => $job_seeker['id'],
        'name' => $job_seeker['name'],
        'age' => $job_seeker['age'],
        'contact' => $job_seeker['contact'],
        'education' => $job_seeker['education'],
        'skills' => $job_seeker['skills'],
        'preferences' => $job_seeker['preferences']
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Job seeker not found']);
}
?>