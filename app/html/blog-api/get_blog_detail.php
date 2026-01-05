<?php
require_once __DIR__ . "/../../bootstrap.php";
header('Content-Type: application/json');
include(__DIR__ . "/../image_functions.php");

// At the top of your PHP file, after header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$slug = isset($_GET['slug']) ? pg_escape_string($con, $_GET['slug']) : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$slug && !$id) {
    echo json_encode(['success' => false, 'message' => 'Post identifier required']);
    exit;
}

$where = $id ? "id = $1" : "slug = $1";
$param = $id ? [$id] : [$slug];

// Get post
$sql = "SELECT *, b.id, u.name AS author_name, u.profile_picture AS author_photo FROM blog_posts b
LEFT JOIN blog_users u ON b.author_id = u.id WHERE $where AND status = 'published'";
$result = pg_query_params($con, $sql, $param);

if (!$result || pg_num_rows($result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Post not found']);
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

// Add reading time calculation in your PHP:
function calculateReadingTime($content)
{
    $word_count = str_word_count(strip_tags($content));
    $reading_time = ceil($word_count / 200); // 200 words per minute
    return $reading_time;
}

// Update the post array to include these:
$post['reading_time'] = calculateReadingTime($post['content']);
$post['word_count'] = str_word_count(strip_tags($post['content']));

// Then ensure your JSON response includes these fields:

// Increment view count
$viewSql = "UPDATE blog_posts SET views = views + 1 WHERE id = $1";
pg_query_params($con, $viewSql, [$post['id']]);

// Get likes
$likeSql = "SELECT COUNT(*) as like_count, 
                   ARRAY_AGG(user_id) as liked_users 
            FROM blog_likes 
            WHERE post_id = $1";
$likeResult = pg_query_params($con, $likeSql, [$post['id']]);
$likes = pg_fetch_assoc($likeResult);

// Get comments
$commentSql = "SELECT c.*, u.name AS user_name, u.profile_picture AS user_photo,
                      (SELECT COUNT(*) FROM blog_comments WHERE parent_id = c.id) as replies_count
               FROM blog_comments c 
               LEFT JOIN blog_users u ON c.user_id = u.id
               WHERE c.post_id = $1 AND c.status = 'approved' AND c.parent_id IS NULL
               ORDER BY c.created_at DESC";
$commentResult = pg_query_params($con, $commentSql, [$post['id']]);

$comments = [];
while ($comment = pg_fetch_assoc($commentResult)) {
    // Get replies
    $replySql = "SELECT *, u.name AS user_name, u.profile_picture AS user_photo FROM blog_comments c
                 LEFT JOIN blog_users u ON c.user_id = u.id
                 WHERE parent_id = $1 AND status = 'approved'
                 ORDER BY c.created_at ASC";
    $replyResult = pg_query_params($con, $replySql, [$comment['id']]);

    $replies = [];
    while ($reply = pg_fetch_assoc($replyResult)) {
        $replies[] = $reply;
    }

    $comment['replies'] = $replies;
    $comments[] = $comment;
}

// Get related posts
$relatedSql = "SELECT id, title, slug, excerpt, featured_image, created_at
               FROM blog_posts 
               WHERE category = $1 AND id != $2 AND status = 'published'
               ORDER BY published_at DESC
               LIMIT 3";
$relatedResult = pg_query_params($con, $relatedSql, [$post['category'], $post['id']]);

$related_posts = [];
while ($related = pg_fetch_assoc($relatedResult)) {
    $related['featured_image'] = processImageUrl($related['featured_image']) ?? null;
    $related_posts[] = $related;
}

echo json_encode([
    'success' => true,
    'post' => $post,
    'likes' => $likes,
    'comments' => $comments,
    'related_posts' => $related_posts
]);
