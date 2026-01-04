<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/drive.php"); // Your existing Drive upload system

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
if (empty($data['id']) || empty($data['title']) || empty($data['content']) || empty($data['category'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Verify user owns this post
$post_id = (int)$data['id'];
$user_id = pg_escape_string($con, $data['user_id']);

// Get user email
$user_sql = "SELECT email FROM blog_users WHERE id = '$user_id' LIMIT 1";
$user_result = pg_query($con, $user_sql);

if (!$user_result || pg_num_rows($user_result) === 0) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

$user_data = pg_fetch_assoc($user_result);
$user_email = $user_data['email'];

// Check if post belongs to user
$check_sql = "SELECT id, status FROM blog_posts WHERE id = $post_id AND author_email = '$user_email'";
$check_result = pg_query($con, $check_sql);

if (!$check_result || pg_num_rows($check_result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Post not found or unauthorized']);
    exit;
}

$post_data = pg_fetch_assoc($check_result);
$original_status = !empty($data['original_status']) ? $data['original_status'] : $post_data['status'];

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
            $featured_image = $image_url;
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
            $author_photo = $author_image_url;
        }
    } else {
        // It's already a URL
        $author_photo = pg_escape_string($con, $data['author_photo']);
    }
}

$author_name = !empty($data['author_name']) ? pg_escape_string($con, $data['author_name']) : 'Anonymous';
$author_email = pg_escape_string($con, $user_email);
$reading_time = !empty($data['reading_time']) ? (int)$data['reading_time'] : 5;
$status = !empty($data['status']) ? pg_escape_string($con, $data['status']) : 'draft';

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

// Set publish date if publishing
$publish_sql = "";
if ($status === 'published') {
    if (!empty($data['publish_date'])) {
        $publish_date = pg_escape_string($con, $data['publish_date']);
        $publish_sql = ", published_at = '$publish_date'";
    } else if ($original_status !== 'published') {
        $publish_sql = ", published_at = NOW()";
    }
}

// Build SQL query for UPDATE
$sql = "UPDATE blog_posts SET
            title = '$title',
            slug = '$slug',
            excerpt = " . ($excerpt ? "'$excerpt'" : "NULL") . ",
            content = '$content',
            featured_image = " . ($featured_image ? "'$featured_image'" : "featured_image") . ",
            category = '$category',
            tags = '$tags_formatted',
            author_name = '$author_name',
            author_email = '$author_email',
            author_photo = " . ($author_photo ? "'$author_photo'" : "author_photo") . ",
            reading_time = $reading_time,
            status = '$status',
            updated_at = NOW()
            $publish_sql
        WHERE id = $post_id
        RETURNING id";

error_log("Update SQL Query: " . $sql);

$result = pg_query($con, $sql);

if ($result) {
    // Process images in content and upload to Drive
    if (!empty($content)) {
        $updated_content = processContentImages($content, $post_id);
        if ($updated_content !== $content) {
            // Update content with Drive URLs
            $update_sql = "UPDATE blog_posts SET content = '" . pg_escape_string($con, $updated_content) . "' WHERE id = $post_id";
            pg_query($con, $update_sql);
        }
    }

    // Update tags in blog_tags table
    // First, remove existing tags
    $delete_tags_sql = "DELETE FROM blog_post_tags WHERE post_id = $post_id";
    pg_query($con, $delete_tags_sql);
    
    // Add new tags
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
        'message' => 'Blog post updated successfully',
        'post_id' => $post_id,
        'slug' => $slug
    ]);
} else {
    $error = pg_last_error($con);
    error_log("Database error: " . $error);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $error
    ]);
}

// Reuse your existing helper functions from save_blog.php
function generateSlug($title)
{
    global $con;
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');

    // Check if slug exists (excluding current post)
    $check_sql = "SELECT COUNT(*) as count FROM blog_posts WHERE slug = '$slug' AND id != " . $GLOBALS['post_id'];
    $result = pg_query($con, $check_sql);
    if ($result) {
        $row = pg_fetch_assoc($result);
        if ($row['count'] > 0) {
            $slug .= '-' . time();
        }
    }

    return $slug;
}

function uploadBase64ImageToDrive($base64_image, $filename)
{
    // Your existing function from save_blog.php
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
        $parent_folder_id = '1lhVTEMKD7ItMjPjYCow-cWboq0jOo-Ut';

        $drive_url = uploadeToDrive($uploadedFile, $parent_folder_id, $filename);

        // Clean up temp file
        unlink($temp_file);

        return $drive_url;
    }

    return null;
}

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
            $new_img_tag = str_replace($base64_src, $drive_url, $full_img_tag);
            $content = str_replace($full_img_tag, $new_img_tag, $content);
        }
    }

    return $content;
}
?>