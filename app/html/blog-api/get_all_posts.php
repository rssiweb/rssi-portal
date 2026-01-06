<?php
require_once __DIR__ . "/../../bootstrap.php";

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Check if admin is making the request
$admin_id = isset($_GET['user_id']) ? trim($_GET['user_id']) : null;

if (!$admin_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Admin ID is required',
        'posts' => []
    ]);
    exit;
}

try {
    // First verify this user is an admin
    $escaped_admin_id = pg_escape_string($con, $admin_id);
    $admin_check_sql = "SELECT is_admin FROM blog_users WHERE id = '$escaped_admin_id' LIMIT 1";
    
    error_log("Admin verification for user: " . $admin_id);
    
    $admin_result = pg_query($con, $admin_check_sql);
    
    if (!$admin_result) {
        throw new Exception('Admin verification failed');
    }
    
    $admin_data = pg_fetch_assoc($admin_result);
    $is_admin = ($admin_data['is_admin'] === 't' || $admin_data['is_admin'] === true);
    
    if (!$is_admin) {
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized: User is not an admin',
            'posts' => []
        ]);
        exit;
    }
    
    // Get all posts with author information
    $sql = "
        SELECT 
            bp.*,
            bu.name as author_name,
            bu.email as author_email,
            bu.profile_picture as author_photo,
            bu.id as author_id
        FROM blog_posts bp
        LEFT JOIN blog_users bu ON bp.author_id = bu.id
        ORDER BY bp.created_at DESC
    ";
    
    error_log("Fetching all posts for admin: " . $admin_id);
    
    $result = pg_query($con, $sql);
    
    if (!$result) {
        $error = pg_last_error($con);
        error_log("Database query error: " . $error);
        throw new Exception('Failed to fetch posts');
    }
    
    $posts = [];
    while ($row = pg_fetch_assoc($result)) {
        // Format the post data
        $post = [
            'id' => $row['id'],
            'title' => $row['title'],
            'slug' => $row['slug'],
            'excerpt' => $row['excerpt'],
            'content' => $row['content'],
            'featured_image' => $row['featured_image'],
            'category' => $row['category'],
            'tags' => json_decode($row['tags'], true) ?: [],
            'status' => $row['status'],
            'reading_time' => intval($row['reading_time']),
            'views' => intval($row['views']),
            'author_name' => $row['author_name'] ?: 'Unknown Author',
            'author_email' => $row['author_email'],
            'author_photo' => $row['author_photo'],
            'author_id' => $row['author_id'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'published_at' => $row['published_at'],
            'published_by' => $row['published_by']
        ];
        
        // Calculate a default reading time if not set
        if (!$post['reading_time'] || $post['reading_time'] < 1) {
            $wordCount = str_word_count(strip_tags($row['content']));
            $post['reading_time'] = max(1, ceil($wordCount / 200));
        }
        
        $posts[] = $post;
    }
    
    error_log("Found " . count($posts) . " posts for admin");
    
    echo json_encode([
        'success' => true,
        'posts' => $posts,
        'count' => count($posts),
        'message' => 'Posts loaded successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Exception in get_all_posts.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'posts' => [],
        'message' => 'Error loading posts: ' . $e->getMessage()
    ]);
}
?>