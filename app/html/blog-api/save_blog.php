<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/drive.php"); // Your existing Drive upload system
include(__DIR__ . "/../image_functions.php");

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

// Handle featured image - upload to Drive if it's a base64 string
$featured_image = null;
if (!empty($data['featured_image'])) {
    // Check if it's a base64 image or a URL
    if (strpos($data['featured_image'], 'data:image') === 0) {
        // It's a base64 image, upload to Drive
        $image_url = uploadBase64ImageToDrive($data['featured_image'], 'blog_featured_' . time());
        if ($image_url) {
            $featured_image = processImageUrl($image_url);
        }
    } else {
        // It's already a URL
        $featured_image = pg_escape_string($con, $data['featured_image']);
    }
}

$category = pg_escape_string($con, $data['category']);

// Handle author photo
$author_photo = null;
if (!empty($data['author_photo'])) {
    // Check if it's a base64 image or a URL
    if (strpos($data['author_photo'], 'data:image') === 0) {
        // It's a base64 image, upload to Drive
        $author_image_url = uploadBase64ImageToDrive($data['author_photo'], 'author_' . time());
        if ($author_image_url) {
            $author_photo = processImageUrl($author_image_url);
        }
    } else {
        // It's already a URL
        $author_photo = pg_escape_string($con, $data['author_photo']);
    }
}

$author_name = !empty($data['author_name']) ? pg_escape_string($con, $data['author_name']) : 'Anonymous';
$reading_time = !empty($data['reading_time']) ? (int)$data['reading_time'] : 5;
$status = !empty($data['status']) ? pg_escape_string($con, $data['status']) : 'draft';
$views = 0;

// Handle tags - convert to PostgreSQL text[] array format
$tags = !empty($data['tags']) && is_array($data['tags']) ? $data['tags'] : [];

// Function to format array for PostgreSQL
function formatPostgresArray($array)
{
    if (empty($array)) {
        return '{}';
    }

    // Escape and quote each element
    $elements = array_map(function ($item) {
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

    // Process images in content and upload to Drive
    if (!empty($content)) {
        $updated_content = processContentImages($content, $post_id);
        if ($updated_content !== $content) {
            // Update content with Drive URLs
            $update_sql = "UPDATE blog_posts SET content = '" . pg_escape_string($con, $updated_content) . "' WHERE id = $post_id";
            pg_query($con, $update_sql);
        }
    }

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

function generateSlug($title)
{
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

/**
 * Upload base64 image to Google Drive
 */
function uploadBase64ImageToDrive($base64_image, $filename)
{
    // Extract the base64 data
    if (preg_match('/^data:image\/(\w+);base64,/', $base64_image, $matches)) {
        $image_type = $matches[1];
        $base64_data = substr($base64_image, strpos($base64_image, ',') + 1);
        $image_data = base64_decode($base64_data);

        // Create a temporary file
        $temp_file = tempnam(sys_get_temp_dir(), 'blog_img_');
        file_put_contents($temp_file, $image_data);

        // Prepare file for upload
        $uploadedFile = [
            'name' => $filename . '.' . $image_type,
            'type' => 'image/' . $image_type,
            'tmp_name' => $temp_file,
            'error' => 0,
            'size' => filesize($temp_file)
        ];

        // Upload to Google Drive
        // Replace with your actual Drive folder ID for blog images
        $parent_folder_id = '1lhVTEMKD7ItMjPjYCow-cWboq0jOo-Ut';

        $drive_url = uploadeToDrive($uploadedFile, $parent_folder_id, $filename);

        // Clean up temp file
        unlink($temp_file);

        return $drive_url;
    }

    return null;
}

/**
 * Process images in content and upload base64 images to Drive
 */
function processContentImages($content, $post_id)
{
    // Find all base64 images in the content
    $pattern = '/<img[^>]+src="(data:image\/[^;]+;base64,[^"]+)"[^>]*>/i';

    preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
        $full_img_tag = $match[0];
        $base64_src = $match[1];

        // Upload to Drive
        $drive_url = uploadBase64ImageToDrive($base64_src, 'blog_content_' . $post_id . '_' . uniqid());

        if ($drive_url) {
            // Replace base64 with Drive URL
            $new_img_tag = str_replace($base64_src, processImageUrl($drive_url), $full_img_tag);
            $content = str_replace($full_img_tag, $new_img_tag, $content);
        }
    }

    return $content;
}
