<?php
require_once __DIR__ . '/../../bootstrap.php';
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$response = ['success' => false, 'message' => ''];

try {
    // Validate input
    if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        throw new Exception('Invalid concession ID');
    }
    
    $requiredFields = ['effective_from', 'concession_amount', 'reason', 'concession_category'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    $concessionId = (int)$_POST['id'];
    $userId = $associatenumber; // Assuming this is set in the session after login
    $effectiveUntil = !empty($_POST['effective_until']) ? $_POST['effective_until'] : null;
    
    // Begin transaction
    pg_query($con, "BEGIN");
    
    // Get current concession data
    $query = "SELECT * FROM student_concessions WHERE id = $1 FOR UPDATE";
    $result = pg_query_params($con, $query, [$concessionId]);
    $oldData = pg_fetch_assoc($result);
    
    if (!$oldData) {
        throw new Exception('Concession not found');
    }
    
    // Update concession
    $updateQuery = "UPDATE student_concessions SET 
        effective_from = $1,
        effective_until = $2,
        concession_amount = $3,
        reason = $4,
        concession_category = $5,
        updated_at = CURRENT_TIMESTAMP
        WHERE id = $6
        RETURNING *";
    
    $updateResult = pg_query_params($con, $updateQuery, [
        $_POST['effective_from'],
        $effectiveUntil,
        $_POST['concession_amount'],
        $_POST['reason'],
        $_POST['concession_category'],
        $concessionId
    ]);
    
    if (!$updateResult) {
        throw new Exception(pg_last_error($con));
    }
    
    $newData = pg_fetch_assoc($updateResult);
    
    // Log changes to history
    $historyQuery = "INSERT INTO concession_history 
        (concession_id, changed_by, action, old_values, new_values)
        VALUES ($1, $2, 'modified', $3::jsonb, $4::jsonb)";
    
    $historyResult = pg_query_params($con, $historyQuery, [
        $concessionId,
        $userId,
        json_encode($oldData),
        json_encode($newData)
    ]);
    
    if (!$historyResult) {
        throw new Exception('Failed to save history: ' . pg_last_error($con));
    }
    
    // Commit transaction
    pg_query($con, "COMMIT");
    
    $response['success'] = true;
    $response['message'] = 'Concession updated successfully';
    $response['data'] = $newData;
    
} catch (Exception $e) {
    // Rollback on error
    pg_query($con, "ROLLBACK");
    $response['message'] = 'Error: ' . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?>