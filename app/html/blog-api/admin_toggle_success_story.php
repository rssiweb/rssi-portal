<?php
require_once __DIR__ . "/../../bootstrap.php";

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['post_id']) || !isset($data['admin_id']) || !isset($data['is_success_story'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters'
    ]);
    exit;
}

$post_id = trim($data['post_id']);
$admin_id = trim($data['admin_id']);
$is_success_story = $data['is_success_story'] ? 't' : 'f';

try {
    // First verify this user is an admin
    $escaped_admin_id = pg_escape_string($con, $admin_id);
    $admin_check_sql = "SELECT is_admin, name FROM blog_users WHERE id = '$escaped_admin_id' LIMIT 1";
    
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
    
    // Update the success story status
    $escaped_post_id = pg_escape_string($con, $post_id);
    $escaped_is_success_story = pg_escape_string($con, $is_success_story);
    
    $update_sql = "
        UPDATE blog_posts 
        SET 
            is_success_story = '$escaped_is_success_story',
            updated_at = NOW()
        WHERE id = '$escaped_post_id'
        RETURNING id, title, is_success_story
    ";
    
    error_log("Toggling success story status for post $post_id to $is_success_story by admin $admin_id");
    
    $update_result = pg_query($con, $update_sql);
    
    if (!$update_result) {
        $error = pg_last_error($con);
        error_log("Database update error: " . $error);
        throw new Exception('Failed to update success story status');
    }
    
    $updated_post = pg_fetch_assoc($update_result);
    
    $status_text = $is_success_story === 't' ? 'marked as success story' : 'removed from success stories';
    error_log("Post $post_id $status_text successfully");
    
    echo json_encode([
        'success' => true,
        'message' => "Post $status_text successfully",
        'post' => $updated_post
    ]);
    
} catch (Exception $e) {
    error_log("Exception in admin_toggle_success_story.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error updating success story status: ' . $e->getMessage()
    ]);
}
?>