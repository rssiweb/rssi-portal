<?php
require_once __DIR__ . "/../../bootstrap.php";

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Get user_id from query parameter
$user_id = isset($_GET['user_id']) ? trim($_GET['user_id']) : null;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

try {
    // First, get the user's email from blog_users table
    $user_sql = "SELECT email FROM blog_users WHERE id = '$user_id' LIMIT 1";
    $user_result = pg_query($con, $user_sql);

    if (!$user_result || pg_num_rows($user_result) === 0) {
        echo json_encode(['success' => true, 'posts' => []]);
        exit;
    }

    $user_data = pg_fetch_assoc($user_result);
    $user_email = $user_data['email'];

    // Get posts by author_email
    $sql = "SELECT 
                id, title, slug, excerpt, category, status, 
                views, created_at, published_at,
                COALESCE(featured_image, '') as featured_image
            FROM blog_posts 
            WHERE author_id = '$user_id'
            ORDER BY 
                CASE status 
                    WHEN 'published' THEN 1
                    WHEN 'pending' THEN 2
                    WHEN 'draft' THEN 3
                END,
                created_at DESC";

    $result = pg_query($con, $sql);

    if (!$result) {
        throw new Exception('Database query failed: ' . pg_last_error($con));
    }

    $posts = [];
    while ($row = pg_fetch_assoc($result)) {
        $posts[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'slug' => $row['slug'],
            'excerpt' => $row['excerpt'],
            'category' => $row['category'],
            'status' => $row['status'],
            'views' => (int)$row['views'],
            'created_at' => $row['created_at'],
            'published_at' => $row['published_at'],
            'featured_image' => $row['featured_image']
        ];
    }

    echo json_encode([
        'success' => true,
        'posts' => $posts,
        'count' => count($posts)
    ]);
} catch (Exception $e) {
    error_log("Error in get_user_posts.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error loading posts: ' . $e->getMessage(),
        'posts' => []
    ]);
}
