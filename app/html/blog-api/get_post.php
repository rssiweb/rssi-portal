<?php
require_once __DIR__ . "/../../bootstrap.php";
include(__DIR__ . "/../image_functions.php");

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
    $user_sql = "SELECT email FROM blog_users WHERE id = '$user_id' LIMIT 1";
    $user_result = pg_query($con, $user_sql);

    if (!$user_result || pg_num_rows($user_result) === 0) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    $user_data = pg_fetch_assoc($user_result);
    $user_email = $user_data['email'];

    // Get post data
    $sql = "SELECT 
                b.id, title, slug, excerpt, content, featured_image, 
                category, tags, u.name AS author_name, u.profile_picture AS author_photo, u.email AS author_email,
                reading_time, status, views, b.created_at, published_at
            FROM blog_posts b
            LEFT JOIN blog_users u ON b.author_id = u.id
            WHERE b.id = $post_id AND author_id = '$user_id'
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

    $post['featured_image'] = processImageUrl($post['featured_image']) ?? null;
    $post['author_photo'] = processImageUrl($post['author_photo']) ?? null;
    $dom = new DOMDocument();
    libxml_use_internal_errors(true); // ignore warnings for HTML5 tags
    $dom->loadHTML(mb_convert_encoding($post['content'], 'HTML-ENTITIES', 'UTF-8'));
    libxml_clear_errors();

    // Update all <img> src attributes
    foreach ($dom->getElementsByTagName('img') as $img) {
        $src = $img->getAttribute('src');
        $img->setAttribute('src', processImageUrl($src) ?? null);
    }

    // Save back the updated HTML without <html> and <body> wrappers
    $body = $dom->getElementsByTagName('body')->item(0);
    $innerHTML = '';
    foreach ($body->childNodes as $child) {
        $innerHTML .= $dom->saveHTML($child);
    }
    $post['content'] = $innerHTML;

    // Convert PostgreSQL array to PHP array
    if (!empty($post['tags'])) {
        // Remove {} from tags array
        $tags_string = trim($post['tags'], '{}');
        $post['tags'] = $tags_string ? explode(',', $tags_string) : [];
        // Remove quotes from each tag
        $post['tags'] = array_map(function ($tag) {
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
