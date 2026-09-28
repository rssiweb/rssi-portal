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

// Query to get plan history with user full name and location name
$historyQuery = "SELECT 
                    sch.id,
                    sch.category_type, 
                    sch.class, 
                    sch.location_id,
                    ol.name AS location_name,
                    sch.effective_from, 
                    sch.effective_until,
                    sch.is_valid,
                    sch.created_at, 
                    sch.created_by,
                    sch.remarks,
                    COALESCE(ram.fullname, sch.created_by) as created_by_name
                 FROM student_category_history sch
                 LEFT JOIN rssimyaccount_members ram ON sch.created_by = ram.associatenumber
                 LEFT JOIN office_locations ol ON sch.location_id = ol.id
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
        'id' => $row['id'],
        'category_type' => $row['category_type'],
        'class' => $row['class'],
        'location_id' => $row['location_id'],
        'location_name' => $row['location_name'],
        'effective_from' => $row['effective_from'],
        'effective_until' => $row['effective_until'],
        'is_valid' => $row['is_valid'],       // ← NEW
        'created_at' => $row['created_at'],
        'created_by_id' => $row['created_by'],
        'created_by_name' => $row['created_by_name'],
        'remarks' => $row['remarks']
    ];
}

echo json_encode([
    'success' => true,
    'data' => $plans
]);
