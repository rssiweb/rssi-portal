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
$required = ['title', 'content', 'category'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        echo json_encode(['success' => false, 'message' => "$field is required"]);
        exit;
    }
}

// Generate slug if not provided
if (empty($data['slug'])) {
    $data['slug'] = generateSlug($data['title']);
}

// Prepare data for database
$title = pg_escape_string($con, $data['title']);
$slug = pg_escape_string($con, $data['slug']);
$excerpt = !empty($data['excerpt']) ? pg_escape_string($con, $data['excerpt']) : null;
$content = pg_escape_string($con, $data['content']);
$featured_image = !empty($data['featured_image']) ? pg_escape_string($con, $data['featured_image']) : null;
$category = pg_escape_string($con, $data['category']);
$author_name = !empty($data['author_name']) ? pg_escape_string($con, $data['author_name']) : 'Anonymous';
$author_photo = !empty($data['author_photo']) ? pg_escape_string($con, $data['author_photo']) : null;
$reading_time = !empty($data['reading_time']) ? (int)$data['reading_time'] : 5;
$status = !empty($data['status']) ? pg_escape_string($con, $data['status']) : 'draft';
$views = 0;

// Handle tags - convert to PostgreSQL text[] array format
$tags = !empty($data['tags']) && is_array($data['tags']) ? $data['tags'] : [];

// Function to format array for PostgreSQL
function formatPostgresArray($array) {
    if (empty($array)) {
        return '{}';
    }
    
    // Escape and quote each element
    $elements = array_map(function($item) {
        // Escape quotes and backslashes
        $escaped = addcslashes($item, '"\\');
        return '"' . $escaped . '"';
    }, $array);
    
    return '{' . implode(',', $elements) . '}';
}

// Get formatted tags array
$tags_formatted = formatPostgresArray($tags);

// Set publish date
$publish_sql = "";
if (!empty($data['publish_date']) && $status === 'published') {
    $publish_date = pg_escape_string($con, $data['publish_date']);
    $publish_sql = ", published_at = '$publish_date'";
} else if ($status === 'published') {
    $publish_sql = ", published_at = NOW()";
}

// Build SQL query
$sql = "INSERT INTO blog_posts (
    title, slug, excerpt, content, featured_image, 
    category, tags, author_name, author_photo, 
    reading_time, views, status, created_at, updated_at
) VALUES (
    '$title', 
    '$slug', 
    " . ($excerpt ? "'$excerpt'" : "NULL") . ", 
    '$content', 
    " . ($featured_image ? "'$featured_image'" : "NULL") . ",
    '$category', 
    '$tags_formatted',
    '$author_name', 
    " . ($author_photo ? "'$author_photo'" : "NULL") . ",
    $reading_time, 
    $views, 
    '$status', 
    NOW(), 
    NOW()
) $publish_sql RETURNING id";

error_log("SQL Query: " . $sql); // For debugging

$result = pg_query($con, $sql);

if ($result) {
    // Get the inserted ID using RETURNING clause
    $row = pg_fetch_assoc($result);
    $post_id = $row['id'];
    
    // Update tags in blog_tags table if tags array exists
    if (!empty($tags)) {
        foreach ($tags as $tag_name) {
            $tag_name_clean = strtolower(trim($tag_name));
            $tag_name_escaped = pg_escape_string($con, $tag_name_clean);
            
            // Check if tag exists
            $tag_check = pg_query($con, "SELECT id FROM blog_tags WHERE name = '$tag_name_escaped'");
            if ($tag_check && pg_num_rows($tag_check) > 0) {
                $tag_row = pg_fetch_assoc($tag_check);
                $tag_id = $tag_row['id'];
            } else {
                // Create new tag
                $tag_slug = str_replace(' ', '-', $tag_name_clean);
                $tag_slug_escaped = pg_escape_string($con, $tag_slug);
                $tag_insert = pg_query($con, "INSERT INTO blog_tags (name, slug) VALUES ('$tag_name_escaped', '$tag_slug_escaped') RETURNING id");
                if ($tag_insert) {
                    $tag_row = pg_fetch_assoc($tag_insert);
                    $tag_id = $tag_row['id'];
                } else {
                    $tag_id = null;
                }
            }
            
            // Link tag to post
            if ($tag_id) {
                $link_sql = "INSERT INTO blog_post_tags (post_id, tag_id) VALUES ($post_id, $tag_id)";
                pg_query($con, $link_sql);
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Blog post saved successfully',
        'post_id' => $post_id,
        'slug' => $data['slug']
    ]);
} else {
    $error = pg_last_error($con);
    error_log("Database error: " . $error);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $error
    ]);
}

function generateSlug($title) {
    global $con;
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    
    // Check if slug exists
    $check_sql = "SELECT COUNT(*) as count FROM blog_posts WHERE slug = '$slug'";
    $result = pg_query($con, $check_sql);
    if ($result) {
        $row = pg_fetch_assoc($result);
        if ($row['count'] > 0) {
            $slug .= '-' . time();
        }
    }
    
    return $slug;
}
?>