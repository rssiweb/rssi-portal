<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

header('Content-Type: application/json');

if (!isLoggedIn("aid")) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Define permission levels (higher number = higher permission)
$permission_levels = [
    'Admin' => 2,            // Highest level  
    'Offline Manager' => 1,  // Basic level
];

// Get current user's permission level
$current_user_level = isset($permission_levels[$role]) ? $permission_levels[$role] : 0;

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Read inputs
$id              = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$effective_until = isset($_POST['effective_until']) && $_POST['effective_until'] !== ''
    ? $_POST['effective_until'] : null;
$is_valid        = isset($_POST['is_valid']) ? filter_var($_POST['is_valid'], FILTER_VALIDATE_BOOLEAN) : false;
$remarks         = isset($_POST['remarks']) ? trim($_POST['remarks']) : '';

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid ID']);
    exit;
}

// Validate date if provided
if ($effective_until !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $effective_until)) {
    echo json_encode(['success' => false, 'error' => 'Invalid effective_until date format']);
    exit;
}

// Fetch the row first to validate and for audit
$rowResult = pg_query_params(
    $con,
    "SELECT id, student_id, effective_from, effective_until, is_valid, remarks
     FROM student_category_history
     WHERE id = $1",
    [$id]
);

if (!$rowResult || pg_num_rows($rowResult) === 0) {
    echo json_encode(['success' => false, 'error' => 'History record not found']);
    exit;
}

$row = pg_fetch_assoc($rowResult);

// Determine if the current user is Admin
$isAdmin = ($current_user_level >= 2);   // or use $current_user_level >= 2 if you have that helper here

// Non-Admins may only edit open-ended (active) rows
$isOpenEnded = empty($row['effective_until']);
if (!$isAdmin && !$isOpenEnded) {
    echo json_encode([
        'success' => false,
        'error'   => 'Only active (open-ended) plan records can be edited by non-admin users.'
    ]);
    exit;
}

// Sanitize remarks
$remarksEscaped = $remarks === '' ? null : pg_escape_string($con, $remarks);
$untilValue     = $effective_until === null ? 'NULL' : "'$effective_until'";
$validValue     = $is_valid ? 'true' : 'false';
$updatedBy      = pg_escape_string($con, $associatenumber);

// Update
$updateSql = "
    UPDATE student_category_history
    SET effective_until = $untilValue,
        is_valid        = $validValue,
        remarks         = " . ($remarksEscaped === null ? "NULL" : "'$remarksEscaped'") . ",
        updated_by      = '$updatedBy',
        updated_on      = NOW()
    WHERE id = $id
";

$updateResult = pg_query($con, $updateSql);

if (!$updateResult) {
    echo json_encode(['success' => false, 'error' => 'Update failed: ' . pg_last_error($con)]);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Plan history record updated successfully.',
    'id' => $id
]);
