<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/drive.php");
include(__DIR__ . "/../proxy_to_drive_image.php"); // Add this line

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

// Get user details from blog_users
$user_sql = "SELECT email, name, profile_picture FROM blog_users WHERE id = '$user_id' LIMIT 1";
$user_result = pg_query($con, $user_sql);

if (!$user_result || pg_num_rows($user_result) === 0) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

$user_data = pg_fetch_assoc($user_result);
$user_email = $user_data['email'];
$current_name = $user_data['name'];
$current_profile_picture = $user_data['profile_picture'];

// Check if post belongs to user
$check_sql = "SELECT id, status FROM blog_posts WHERE id = $post_id AND author_id = '$user_id'";
$check_result = pg_query($con, $check_sql);

if (!$check_result || pg_num_rows($check_result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Post not found or unauthorized']);
    exit;
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
$category = pg_escape_string($con, $data['category']);
$status = !empty($data['status']) ? pg_escape_string($con, $data['status']) : 'draft';
$reading_time = !empty($data['reading_time']) ? (int)$data['reading_time'] : 5;

// ==================== HANDLE IMAGES ====================

// Handle featured image - convert proxy URLs to Drive URLs
$featured_image = null;
if (!empty($data['featured_image'])) {
    if (strpos($data['featured_image'], 'data:image') === 0) {
        // It's a base64 image, upload to Drive
        $image_url = uploadBase64ImageToDrive($data['featured_image'], 'blog_featured_' . time());
        if ($image_url) {
            $featured_image = $image_url;
        }
    } else {
        // It's a URL, check if it's a proxy URL and convert to Drive URL
        $featured_image = convertProxyToDriveUrl($data['featured_image']);
        $featured_image = pg_escape_string($con, $featured_image);
    }
}

// Handle content - convert all proxy image URLs to Drive URLs
$content = !empty($data['content']) ? $data['content'] : '';
// First convert proxy URLs to Drive URLs
$content = processProxyImagesInContent($content);
// Then escape for database
$content = pg_escape_string($con, $content);

// Handle author details - UPDATE BLOG_USERS if changed
$new_name = !empty($data['author_name']) ? pg_escape_string($con, $data['author_name']) : $current_name;
$new_profile_picture = $current_profile_picture; // Default to current

if (!empty($data['author_photo'])) {
    if (strpos($data['author_photo'], 'data:image') === 0) {
        // Upload new profile picture
        $author_image_url = uploadBase64ImageToDrive($data['author_photo'], 'author_profile_' . time());
        if ($author_image_url) {
            $new_profile_picture = $author_image_url;
        }
    } else {
        // Use provided URL
        $new_profile_picture = pg_escape_string($con, $data['author_photo']);
    }
}

// Update blog_users table if name or profile picture changed
$update_user = false;
if ($new_name !== $current_name || $new_profile_picture !== $current_profile_picture) {
    $update_user_sql = "UPDATE blog_users SET 
                        name = '$new_name', 
                        profile_picture = '$new_profile_picture',
                        updated_at = NOW()
                        WHERE id = '$user_id'";

    $update_user_result = pg_query($con, $update_user_sql);
    $update_user = $update_user_result ? true : false;

    if ($update_user) {
        error_log("User profile updated for user_id: $user_id");
    }
}

// Handle tags
$tags = !empty($data['tags']) && is_array($data['tags']) ? $data['tags'] : [];
$tags_formatted = formatPostgresArray($tags);

// Determine SQL based on status
$result = false;
$message = '';

if ($status === 'draft') {
    $sql = "UPDATE blog_posts SET
                title = '$title',
                slug = '$slug',
                excerpt = " . ($excerpt ? "'$excerpt'" : "NULL") . ",
                content = '$content',
                featured_image = " . ($featured_image ? "'$featured_image'" : "featured_image") . ",
                category = '$category',
                tags = '$tags_formatted',
                reading_time = $reading_time,
                status = '$status',
                updated_at = NOW()
            WHERE id = $post_id
            RETURNING id";

    $result = pg_query($con, $sql);
    $message = 'Draft has been saved successfully';
} else if ($status === 'pending') {
    $sql = "UPDATE blog_posts SET
                title = '$title',
                slug = '$slug',
                excerpt = " . ($excerpt ? "'$excerpt'" : "NULL") . ",
                content = '$content',
                featured_image = " . ($featured_image ? "'$featured_image'" : "featured_image") . ",
                category = '$category',
                tags = '$tags_formatted',
                reading_time = $reading_time,
                status = '$status',
                is_rejected = false,
                updated_at = NOW()
            WHERE id = $post_id
            RETURNING id";

    $result = pg_query($con, $sql);
    $message = 'Blog post submitted for review successfully';
}

error_log("Update SQL Query: " . $sql);

if ($result) {
    $return_post_id = $post_id;
    $return_slug = $slug;

    // Process images in content
    if (!empty($content)) {
        $updated_content = processContentImages($content, $return_post_id);
        if ($updated_content !== $content) {
            $update_sql = "UPDATE blog_posts SET content = '" . pg_escape_string($con, $updated_content) . "' WHERE id = $return_post_id";
            pg_query($con, $update_sql);
        }
    }

    // Update tags in blog_tags table
    $delete_tags_sql = "DELETE FROM blog_post_tags WHERE post_id = $return_post_id";
    pg_query($con, $delete_tags_sql);

    // Add new tags
    if (!empty($tags)) {
        foreach ($tags as $tag_name) {
            $tag_name_clean = strtolower(trim($tag_name));
            $tag_name_escaped = pg_escape_string($con, $tag_name_clean);
            $tag_slug = str_replace(' ', '-', $tag_name_clean);
            $tag_slug_escaped = pg_escape_string($con, $tag_slug);

            // Check if tag exists by SLUG
            $tag_check = pg_query($con, "SELECT id FROM blog_tags WHERE slug = '$tag_slug_escaped' LIMIT 1");
            if ($tag_check && pg_num_rows($tag_check) > 0) {
                $tag_row = pg_fetch_assoc($tag_check);
                $tag_id = $tag_row['id'];
            } else {
                // Create new tag
                $tag_insert = pg_query(
                    $con,
                    "INSERT INTO blog_tags (name, slug) 
                     VALUES ('$tag_name_escaped', '$tag_slug_escaped') 
                     ON CONFLICT (slug) DO NOTHING 
                     RETURNING id"
                );

                if ($tag_insert && pg_num_rows($tag_insert) > 0) {
                    $tag_row = pg_fetch_assoc($tag_insert);
                    $tag_id = $tag_row['id'];
                } else {
                    // Try to get existing tag if insert failed
                    $tag_check = pg_query($con, "SELECT id FROM blog_tags WHERE slug = '$tag_slug_escaped' LIMIT 1");
                    if ($tag_check && pg_num_rows($tag_check) > 0) {
                        $tag_row = pg_fetch_assoc($tag_check);
                        $tag_id = $tag_row['id'];
                    } else {
                        error_log("Failed to create/find tag: '$tag_name_clean'");
                        continue;
                    }
                }
            }

            // Link tag to post
            if (!empty($tag_id)) {
                $link_sql = "INSERT INTO blog_post_tags (post_id, tag_id) VALUES ($return_post_id, $tag_id)";
                pg_query($con, $link_sql);
            }
        }
    }

    // Add update_user status to response if needed
    $response = [
        'success' => true,
        'message' => $message,
        'post_id' => $return_post_id,
        'slug' => $return_slug,
        'is_revision' => false
    ];

    if ($update_user) {
        $response['profile_updated'] = true;
    }

    echo json_encode($response);
} else {
    $error = pg_last_error($con);
    error_log("Database error: " . $error);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $error
    ]);
}

// Helper functions (same as before)
function generateSlug($title)
{
    global $con, $post_id;
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');

    // Check if slug exists (excluding current post)
    $check_sql = "SELECT COUNT(*) as count FROM blog_posts WHERE slug = '$slug' AND id != " . $post_id;
    $result = pg_query($con, $check_sql);
    if ($result) {
        $row = pg_fetch_assoc($result);
        if ($row['count'] > 0) {
            $slug .= '-' . time();
        }
    }

    return $slug;
}

function formatPostgresArray($array)
{
    if (empty($array)) {
        return '{}';
    }

    $elements = array_map(function ($item) {
        $escaped = addcslashes($item, '"\\');
        return '"' . $escaped . '"';
    }, $array);

    return '{' . implode(',', $elements) . '}';
}

function uploadBase64ImageToDrive($base64_image, $filename)
{
    if (preg_match('/^data:image\/(\w+);base64,/', $base64_image, $matches)) {
        $image_type = $matches[1];
        $base64_data = substr($base64_image, strpos($base64_image, ',') + 1);
        $image_data = base64_decode($base64_data);

        $temp_file = tempnam(sys_get_temp_dir(), 'blog_img_');
        file_put_contents($temp_file, $image_data);

        $uploadedFile = [
            'name' => $filename . '.' . $image_type,
            'type' => 'image/' . $image_type,
            'tmp_name' => $temp_file,
            'error' => 0,
            'size' => filesize($temp_file)
        ];

        $parent_folder_id = '1lhVTEMKD7ItMjPjYCow-cWboq0jOo-Ut';
        $drive_url = uploadeToDrive($uploadedFile, $parent_folder_id, $filename);

        unlink($temp_file);
        return $drive_url;
    }

    return null;
}

function processContentImages($content, $post_id)
{
    $pattern = '/<img[^>]+src="(data:image\/[^;]+;base64,[^"]+)"[^>]*>/i';
    preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
        $full_img_tag = $match[0];
        $base64_src = $match[1];

        $drive_url = uploadBase64ImageToDrive($base64_src, 'blog_content_' . $post_id . '_' . uniqid());

        if ($drive_url) {
            $new_img_tag = str_replace($base64_src, $drive_url, $full_img_tag);
            $content = str_replace($full_img_tag, $new_img_tag, $content);
        }
    }

    return $content;
}
