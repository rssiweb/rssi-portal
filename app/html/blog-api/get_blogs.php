<?php
require_once __DIR__ . "/../../bootstrap.php";
header('Content-Type: application/json');
include(__DIR__ . "/../image_functions.php");

// At the top of your PHP file, after header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 6;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$category = isset($_GET['category']) ? pg_escape_string($con, $_GET['category']) : null;
$tag = isset($_GET['tag']) ? pg_escape_string($con, $_GET['tag']) : null;
$search = isset($_GET['search']) ? pg_escape_string($con, $_GET['search']) : null;

// Build query
$where = "WHERE status = 'published'";
$params = [];
$paramCount = 0;

if ($category) {
    $paramCount++;
    $where .= " AND category = $" . $paramCount;
    $params[] = $category;
}

if ($tag) {
    $paramCount++;
    $where .= " AND $" . $paramCount . " = ANY(tags)";
    $params[] = $tag;
}

if ($search) {
    $paramCount++;
    $where .= " AND (title ILIKE '%' || $" . $paramCount . " || '%' OR content ILIKE '%' || $" . $paramCount . " || '%' OR excerpt ILIKE '%' || $" . $paramCount . " || '%')";
    $params[] = $search;
}

// Debug: Log parameters
error_log("Where clause: " . $where);
error_log("Params: " . print_r($params, true));

// Count total posts
$countSql = "SELECT COUNT(*) as total FROM blog_posts $where";
$countResult = pg_query_params($con, $countSql, $params);
$total = 0;
if ($countResult) {
    $countData = pg_fetch_assoc($countResult);
    $total = $countData['total'] ?? 0;
}

// Get posts
$posts = [];
if ($total > 0) {
    $postSql = "SELECT 
                    bp.id, title, slug, excerpt, content, 
                    featured_image, category, tags, 
                    bu.name AS author_name, bu.profile_picture AS author_photo,
                    views, reading_time,
                    bp.created_at, published_at
                FROM blog_posts bp
                LEFT JOIN blog_users bu ON bp.author_id = bu.id
                $where 
                ORDER BY COALESCE(published_at, bp.created_at) DESC 
                LIMIT $" . ($paramCount + 1) . " OFFSET $" . ($paramCount + 2);

    $postParams = $params;
    $postParams[] = $limit;
    $postParams[] = $offset;

    $result = pg_query_params($con, $postSql, $postParams);

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            // Ensure proper data types
            $row['id'] = (int)$row['id'];
            $row['views'] = (int)($row['views'] ?? 0);
            $row['reading_time'] = (int)($row['reading_time'] ?? 5);
            $row['featured_image'] = processImageUrl($row['featured_image']) ?? null;
            $row['author_photo'] = processImageUrl($row['author_photo']) ?? null;

            // Handle tags
            if (!empty($row['tags']) && !is_array($row['tags'])) {
                $row['tags'] = json_decode($row['tags'], true) ?? [];
            }
            $row['tags'] = $row['tags'] ?? [];

            // Get likes count
            $likeCount = 0;
            $likeSql = "SELECT COUNT(*) as like_count FROM blog_likes WHERE post_id = " . $row['id'];
            $likeResult = pg_query($con, $likeSql);
            if ($likeResult && $likeData = pg_fetch_assoc($likeResult)) {
                $likeCount = (int)$likeData['like_count'];
            }

            // Get comments count
            $commentCount = 0;
            $commentSql = "SELECT COUNT(*) as comment_count FROM blog_comments WHERE post_id = " . $row['id'] . " AND status = 'approved'";
            $commentResult = pg_query($con, $commentSql);
            if ($commentResult && $commentData = pg_fetch_assoc($commentResult)) {
                $commentCount = (int)$commentData['comment_count'];
            }

            // Format dates
            $row['like_count'] = $likeCount;
            $row['comment_count'] = $commentCount;
            $row['created_at_formatted'] = date('F j, Y', strtotime($row['created_at']));

            // Handle featured image - add default if missing
            if (empty($row['featured_image']) || $row['featured_image'] == 'null') {
                $row['featured_image'] = 'https://via.placeholder.com/400x250/e3f2fd/2c3e50?text=RSSI+BLOG';
            }

            // Create excerpt if missing
            if (empty($row['excerpt'])) {
                $row['excerpt'] = strip_tags(substr($row['content'] ?? '', 0, 150)) . '...';
            }

            $posts[] = $row;
        }
    }
}

// Get categories for sidebar
$catSql = "SELECT category as name, COUNT(*) as post_count 
           FROM blog_posts 
           WHERE status = 'published' AND category IS NOT NULL AND category != ''
           GROUP BY category 
           ORDER BY category";
$catResult = pg_query($con, $catSql);

$categories = [];
if ($catResult) {
    while ($cat = pg_fetch_assoc($catResult)) {
        $cat['post_count'] = (int)$cat['post_count'];
        $categories[] = $cat;
    }
}

// Get tags from array field - simplified approach
$tags = [];
$tagSql = "SELECT unnest(tags) as tag_name 
           FROM blog_posts 
           WHERE status = 'published' AND tags IS NOT NULL AND array_length(tags, 1) > 0
           GROUP BY tag_name 
           ORDER BY COUNT(*) DESC 
           LIMIT 15";
$tagResult = pg_query($con, $tagSql);

if ($tagResult) {
    $tagCounts = [];
    while ($tagRow = pg_fetch_assoc($tagResult)) {
        $tagName = $tagRow['tag_name'];
        if (!isset($tagCounts[$tagName])) {
            $tagCounts[$tagName] = 0;
        }
        $tagCounts[$tagName]++;
    }

    foreach ($tagCounts as $name => $count) {
        $tags[] = [
            'name' => $name,
            'post_count' => $count
        ];
    }
}

// Prepare final response
$response = [
    'success' => true,
    'posts' => $posts,
    'categories' => $categories,
    'tags' => $tags,
    'total' => (int)$total,
    'has_more' => ($offset + $limit) < $total
];

// Add debug info if requested
if (isset($_GET['debug'])) {
    $response['debug'] = [
        'query_params' => [
            'category' => $category,
            'tag' => $tag,
            'search' => $search,
            'limit' => $limit,
            'offset' => $offset
        ],
        'post_count' => count($posts),
        'category_count' => count($categories),
        'tag_count' => count($tags)
    ];
}

// Send response
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
