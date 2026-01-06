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
    
    // Get current rejection reason and admin info
    $escaped_post_id = pg_escape_string($con, $post_id);
    $current_post_sql = "SELECT rejection_reason, title FROM blog_posts WHERE id = '$escaped_post_id' LIMIT 1";
    $current_post_result = pg_query($con, $current_post_sql);
    
    if (!$current_post_result) {
        throw new Exception('Failed to fetch current post data');
    }
    
    $current_post = pg_fetch_assoc($current_post_result);
    $current_rejection_reason = $current_post['rejection_reason'] ?? '';
    $post_title = $current_post['title'] ?? 'Unknown Post';
    
    // Format timestamp for the new rejection entry
    $timestamp = date('Y-m-d H:i:s');
    $admin_name_query = "SELECT name FROM blog_users WHERE id = '$escaped_admin_id' LIMIT 1";
    $admin_name_result = pg_query($con, $admin_name_query);
    $admin_name_data = pg_fetch_assoc($admin_name_result);
    $admin_name = $admin_name_data['name'] ?? 'Admin';
    
    // Build the new rejection entry
    $new_rejection_entry = "=== Rejected on $timestamp by $admin_name ===\n";
    $new_rejection_entry .= "Reason: " . $reason . "\n\n";
    
    // Append to existing rejection reason (if any)
    if (!empty($current_rejection_reason)) {
        $new_rejection_reason = $current_rejection_reason . "\n\n" . $new_rejection_entry;
    } else {
        $new_rejection_reason = $new_rejection_entry;
    }
    
    // Escape the new rejection reason
    $escaped_new_reason = pg_escape_string($con, $new_rejection_reason);
    $escaped_status = pg_escape_string($con, $status);
    
    // Update the post with appended rejection reason and set is_rejected to true
    $update_sql = "
        UPDATE blog_posts 
        SET 
            status = '$escaped_status',
            rejection_reason = '$escaped_new_reason',
            is_rejected = true,
            published_by = NULL,
            published_at = NULL,
            updated_at = NOW()
        WHERE id = '$escaped_post_id'
        RETURNING id, title, status, is_rejected
    ";
    
    error_log("Rejecting post '$post_title' (ID: $post_id) by admin $admin_id");
    error_log("Rejection reason (appended): " . substr($reason, 0, 100) . "...");
    
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
        'post' => $updated_post,
        'rejection_added' => true,
        'timestamp' => $timestamp
    ]);
    
} catch (Exception $e) {
    error_log("Exception in admin_reject_post.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error rejecting post: ' . $e->getMessage()
    ]);
}
?>