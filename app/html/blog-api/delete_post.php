<?php
require_once __DIR__ . "/../../bootstrap.php";

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit;
}

// Validate required fields
if (empty($data['post_id']) || empty($data['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$post_id = (int)$data['post_id'];
$user_id = pg_escape_string($con, $data['user_id']);

try {
    // First, verify the post belongs to the user
    $verify_sql = "SELECT bp.id 
                   FROM blog_posts bp
                   INNER JOIN blog_users bu ON bu.email = bp.author_email
                   WHERE bp.id = $post_id AND bu.id = '$user_id'";
    
    $verify_result = pg_query($con, $verify_sql);
    
    if (!$verify_result || pg_num_rows($verify_result) === 0) {
        echo json_encode(['success' => false, 'message' => 'Post not found or unauthorized']);
        exit;
    }
    
    // Only allow deletion of draft posts
    $status_sql = "SELECT status FROM blog_posts WHERE id = $post_id";
    $status_result = pg_query($con, $status_sql);
    $status_row = pg_fetch_assoc($status_result);
    
    if ($status_row['status'] !== 'draft') {
        echo json_encode([
            'success' => false, 
            'message' => 'Only draft posts can be deleted. Please contact admin for published/pending posts.'
        ]);
        exit;
    }
    
    // Delete from blog_post_tags first (foreign key constraint)
    $delete_tags_sql = "DELETE FROM blog_post_tags WHERE post_id = $post_id";
    pg_query($con, $delete_tags_sql);
    
    // Delete the post
    $delete_sql = "DELETE FROM blog_posts WHERE id = $post_id";
    $delete_result = pg_query($con, $delete_sql);
    
    if ($delete_result) {
        echo json_encode([
            'success' => true,
            'message' => 'Post deleted successfully'
        ]);
    } else {
        throw new Exception('Delete failed: ' . pg_last_error($con));
    }
    
} catch (Exception $e) {
    error_log("Error in delete_post.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error deleting post: ' . $e->getMessage()
    ]);
}
?>