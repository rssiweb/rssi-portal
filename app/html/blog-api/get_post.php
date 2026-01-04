<?php
require_once __DIR__ . "/../../bootstrap.php";

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Get parameters
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = isset($_GET['user_id']) ? pg_escape_string($con, $_GET['user_id']) : null;

if (!$post_id || !$user_id) {
    echo json_encode(['success' => false, 'message' => 'Post ID and User ID are required']);
    exit;
}

try {
    // First get user email from user ID
    $user_sql = "SELECT email FROM blog_users WHERE google_id = '$user_id' LIMIT 1";
    $user_result = pg_query($con, $user_sql);
    
    if (!$user_result || pg_num_rows($user_result) === 0) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    $user_data = pg_fetch_assoc($user_result);
    $user_email = $user_data['email'];
    
    // Get post data
    $sql = "SELECT 
                id, title, slug, excerpt, content, featured_image, 
                category, tags, author_name, author_email, author_photo,
                reading_time, status, views, created_at, published_at
            FROM blog_posts 
            WHERE id = $post_id AND author_email = '$user_email'
            LIMIT 1";
    
    error_log("Getting post query: " . $sql);
    
    $result = pg_query($con, $sql);
    
    if (!$result) {
        throw new Exception('Database query failed: ' . pg_last_error($con));
    }
    
    if (pg_num_rows($result) === 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Post not found or you do not have permission to edit it'
        ]);
        exit;
    }
    
    $post = pg_fetch_assoc($result);
    
    // Convert PostgreSQL array to PHP array
    if (!empty($post['tags'])) {
        // Remove {} from tags array
        $tags_string = trim($post['tags'], '{}');
        $post['tags'] = $tags_string ? explode(',', $tags_string) : [];
        // Remove quotes from each tag
        $post['tags'] = array_map(function($tag) {
            return trim($tag, '"');
        }, $post['tags']);
    } else {
        $post['tags'] = [];
    }
    
    echo json_encode([
        'success' => true,
        'post' => $post,
        'message' => 'Post loaded successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_post.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error loading post: ' . $e->getMessage()
    ]);
}
?>