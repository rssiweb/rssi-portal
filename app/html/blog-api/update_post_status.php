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
if (empty($data['post_id']) || empty($data['user_id']) || empty($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$post_id = (int)$data['post_id'];
$user_id = pg_escape_string($con, $data['user_id']);
$status = pg_escape_string($con, $data['status']);

// Validate status
$valid_statuses = ['draft', 'pending', 'published'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

try {
    // First, verify the post belongs to the user
    $verify_sql = "SELECT bp.id 
                   FROM blog_posts bp
                   INNER JOIN blog_users bu ON bu.email = bp.author_email
                   WHERE bp.id = $post_id AND bu.google_id = '$user_id'";
    
    $verify_result = pg_query($con, $verify_sql);
    
    if (!$verify_result || pg_num_rows($verify_result) === 0) {
        echo json_encode(['success' => false, 'message' => 'Post not found or unauthorized']);
        exit;
    }
    
    // Update post status
    $update_sql = "UPDATE blog_posts 
                   SET status = '$status', 
                       updated_at = NOW()";
    
    // If publishing, set publish date
    if ($status === 'published') {
        $update_sql .= ", published_at = NOW()";
    }
    
    $update_sql .= " WHERE id = $post_id";
    
    $update_result = pg_query($con, $update_sql);
    
    if ($update_result) {
        echo json_encode([
            'success' => true,
            'message' => 'Post status updated successfully'
        ]);
    } else {
        throw new Exception('Update failed: ' . pg_last_error($con));
    }
    
} catch (Exception $e) {
    error_log("Error in update_post_status.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error updating post: ' . $e->getMessage()
    ]);
}
?>