<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

// Check authentication
if (!isLoggedIn("aid")) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Get student ID from request
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : null;

if (!$student_id) {
    echo json_encode(['success' => false, 'error' => 'Student ID required']);
    exit;
}

// Sanitize input
$student_id = pg_escape_string($con, $student_id);

// Query to get plan history with user full name
$historyQuery = "SELECT 
                    sch.category_type, 
                    sch.class, 
                    sch.effective_from, 
                    sch.effective_until, 
                    sch.created_at, 
                    sch.created_by,
                    COALESCE(ram.fullname, sch.created_by) as created_by_name
                 FROM student_category_history sch
                 LEFT JOIN rssimyaccount_members ram ON sch.created_by = ram.associatenumber
                 WHERE sch.student_id = '$student_id' 
                 AND (sch.is_valid = true OR sch.is_valid IS NULL)
                 ORDER BY sch.effective_from DESC, sch.created_at DESC";

$historyResult = pg_query($con, $historyQuery);

if (!$historyResult) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . pg_last_error($con)]);
    exit;
}

$plans = [];
while ($row = pg_fetch_assoc($historyResult)) {
    $plans[] = [
        'category_type' => $row['category_type'],
        'class' => $row['class'],
        'effective_from' => $row['effective_from'],
        'effective_until' => $row['effective_until'],
        'created_at' => $row['created_at'],
        'created_by_id' => $row['created_by'], // Original associatenumber
        'created_by_name' => $row['created_by_name'] // Full name from rssimyaccount_member
    ];
}

echo json_encode([
    'success' => true,
    'data' => $plans
]);
?>