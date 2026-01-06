<?php
require_once __DIR__ . "/../../bootstrap.php";

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['post_id']) || !isset($data['admin_id']) || !isset($data['status'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters'
    ]);
    exit;
}

$post_id = trim($data['post_id']);
$admin_id = trim($data['admin_id']);
$status = trim($data['status']);

// Validate status
$valid_statuses = ['draft', 'pending', 'published'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid status value'
    ]);
    exit;
}

try {
    // First verify this user is an admin
    $escaped_admin_id = pg_escape_string($con, $admin_id);
    $admin_check_sql = "SELECT is_admin FROM blog_users WHERE id = '$escaped_admin_id' LIMIT 1";
    
    $admin_result = pg_query($con, $admin_check_sql);
    
    if (!$admin_result) {
        throw new Exception('Admin verification failed');
    }
    
    $admin_data = pg_fetch_assoc($admin_result);
    $is_admin = ($admin_data['is_admin'] === 't' || $admin_data['is_admin'] === true);
    
    if (!$is_admin) {
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized: User is not an admin'
        ]);
        exit;
    }
    
    // Build update SQL
    $escaped_post_id = pg_escape_string($con, $post_id);
    $escaped_status = pg_escape_string($con, $status);
    
    // Handle published_at and published_by for status changes
    if ($status === 'published') {
        $update_sql = "
            UPDATE blog_posts 
            SET 
                status = '$escaped_status',
                published_at = NOW(),
                published_by = '$escaped_admin_id',
                updated_at = NOW()
            WHERE id = '$escaped_post_id'
        ";
    } elseif ($status === 'draft') {
        $update_sql = "
            UPDATE blog_posts 
            SET 
                status = '$escaped_status',
                published_by = NULL,
                published_at = NULL,
                updated_at = NOW()
            WHERE id = '$escaped_post_id'
        ";
    } else {
        $update_sql = "
            UPDATE blog_posts 
            SET 
                status = '$escaped_status',
                updated_at = NOW()
            WHERE id = '$escaped_post_id'
        ";
    }
    
    error_log("Updating post $post_id status to $status by admin $admin_id");
    
    $update_result = pg_query($con, $update_sql);
    
    if (!$update_result) {
        $error = pg_last_error($con);
        error_log("Database update error: " . $error);
        throw new Exception('Failed to update post status');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Post status updated successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Exception in admin_update_status.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error updating post status: ' . $e->getMessage()
    ]);
}
?>