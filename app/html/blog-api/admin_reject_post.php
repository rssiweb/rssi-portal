<?php
require_once __DIR__ . "/../../bootstrap.php";

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['post_id']) || !isset($data['admin_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters'
    ]);
    exit;
}

$post_id = trim($data['post_id']);
$admin_id = trim($data['admin_id']);
$reason = isset($data['reason']) ? trim($data['reason']) : '';
$status = isset($data['status']) ? trim($data['status']) : 'draft';

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
    
    // Update the post
    $escaped_post_id = pg_escape_string($con, $post_id);
    $escaped_reason = pg_escape_string($con, $reason);
    $escaped_status = pg_escape_string($con, $status);
    
    $update_sql = "
        UPDATE blog_posts 
        SET 
            status = '$escaped_status',
            rejection_reason = '$escaped_reason',
            published_by = NULL,
            published_at = NULL,
            updated_at = NOW()
        WHERE id = '$escaped_post_id'
        RETURNING id, title, status
    ";
    
    error_log("Rejecting post $post_id by admin $admin_id, reason: $reason");
    
    $update_result = pg_query($con, $update_sql);
    
    if (!$update_result) {
        $error = pg_last_error($con);
        error_log("Database update error: " . $error);
        throw new Exception('Failed to reject post');
    }
    
    $updated_post = pg_fetch_assoc($update_result);
    
    echo json_encode([
        'success' => true,
        'message' => 'Post rejected successfully',
        'post' => $updated_post
    ]);
    
} catch (Exception $e) {
    error_log("Exception in admin_reject_post.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error rejecting post: ' . $e->getMessage()
    ]);
}
?>