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
$publish_date = isset($data['publish_date']) ? trim($data['publish_date']) : null;

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
    
    // Update the post
    $escaped_post_id = pg_escape_string($con, $post_id);
    
    // If publish_date is provided, use it; otherwise use current timestamp
    $publish_date_sql = $publish_date 
        ? "'" . pg_escape_string($con, $publish_date) . "'" 
        : 'NOW()';
    
    $update_sql = "
        UPDATE blog_posts 
        SET 
            status = 'published',
            published_at = $publish_date_sql,
            published_by = '$escaped_admin_id',
            updated_at = NOW()
        WHERE id = '$escaped_post_id'
        RETURNING id, title, status, published_at
    ";
    
    error_log("Publishing post $post_id by admin $admin_id");
    
    $update_result = pg_query($con, $update_sql);
    
    if (!$update_result) {
        $error = pg_last_error($con);
        error_log("Database update error: " . $error);
        throw new Exception('Failed to publish post');
    }
    
    $updated_post = pg_fetch_assoc($update_result);
    
    error_log("Post published successfully: " . json_encode($updated_post));
    
    echo json_encode([
        'success' => true,
        'message' => 'Post published successfully',
        'post' => $updated_post
    ]);
    
} catch (Exception $e) {
    error_log("Exception in admin_publish_post.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error publishing post: ' . $e->getMessage()
    ]);
}
?>