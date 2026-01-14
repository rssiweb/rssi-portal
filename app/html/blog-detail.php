<?php
// --------------------------------------------------
// Force correct HTTP response for LinkedIn
// --------------------------------------------------
http_response_code(200);
header("Content-Type: text/html; charset=UTF-8");
header("Accept-Ranges: none");

require_once __DIR__ . '/../bootstrap.php';
include(__DIR__ . "/image_functions.php");

// --------------------------------------------------
// Get slug
// --------------------------------------------------
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if ($slug === '') {
    http_response_code(404);
    exit;
}

// --------------------------------------------------
// Fetch post
// --------------------------------------------------
$sql = "
SELECT 
    bp.title,
    bp.excerpt,
    bp.content,
    bp.featured_image,
    bp.slug
FROM blog_posts bp
WHERE bp.slug = $1
  AND bp.status = 'published'
LIMIT 1
";

$result = pg_query_params($con, $sql, [$slug]);

if (!$result || pg_num_rows($result) === 0) {
    http_response_code(404);
    exit;
}

$post = pg_fetch_assoc($result);

// --------------------------------------------------
// Prepare meta values
// --------------------------------------------------
$title = htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8');

$description = htmlspecialchars(
    $post['excerpt']
        ?: substr(trim(strip_tags($post['content'])), 0, 160),
    ENT_QUOTES,
    'UTF-8'
);

$image = processImageUrl($post['featured_image']);
if (!$image) {
    $image = 'https://login.rssi.in/img/default-og-image.jpg';
}

// --------------------------------------------------
// Environment detection
// --------------------------------------------------
$host = $_SERVER['HTTP_HOST'] ?? '';

$isLocal = (
    $host === 'localhost' ||
    str_starts_with($host, 'localhost:') ||
    $host === '127.0.0.1' ||
    str_starts_with($host, '127.0.0.1:')
);

// --------------------------------------------------
// Frontend URL
// --------------------------------------------------
$frontendUrl = $isLocal
    ? "http://localhost:8081/blog/blog-detail.html?slug={$slug}"
    : "https://rssi.in/blog/blog-detail.html?slug={$slug}";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title><?= $title ?></title>

    <!-- SEO -->
    <meta name="description" content="<?= $description ?>">
    <link rel="canonical" href="<?= $frontendUrl ?>">

    <!-- Open Graph (LinkedIn / Facebook) -->
    <meta property="og:type" content="article">
    <meta property="og:title" content="<?= $title ?>">
    <meta property="og:description" content="<?= $description ?>">
    <meta property="og:image" content="<?= $image ?>">
    <meta property="og:url" content="<?= $frontendUrl ?>">
    <meta property="og:site_name" content="RSSI NGO">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= $title ?>">
    <meta name="twitter:description" content="<?= $description ?>">
    <meta name="twitter:image" content="<?= $image ?>">

    <!-- Cache -->
    <meta http-equiv="Cache-Control" content="public, max-age=600">

    <!-- Delayed JS Redirect (crawler-safe) -->
    <script>
        setTimeout(function() {
            window.location.href = "<?= $frontendUrl ?>";
        }, 800);
    </script>
</head>

<body></body>

</html>