<?php
// --------------------------------------------------
// Force correct response
// --------------------------------------------------
http_response_code(200);
header("Content-Type: text/html; charset=UTF-8");
header("Accept-Ranges: none");

require_once __DIR__ . '/../bootstrap.php';
include(__DIR__ . "/image_functions.php");

// --------------------------------------------------
// Detect social crawlers
// --------------------------------------------------
$userAgent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');

$isBot = preg_match(
    '/linkedinbot|facebookexternalhit|twitterbot|whatsapp|telegrambot|slackbot|googlebot|bingbot/i',
    $userAgent
);

// --------------------------------------------------
// Get slug
// --------------------------------------------------
$slug = trim($_GET['slug'] ?? '');

if ($slug === '') {
    http_response_code(404);
    exit;
}

// --------------------------------------------------
// Fetch post
// --------------------------------------------------
$sql = "
SELECT 
    title,
    excerpt,
    content,
    featured_image,
    slug
FROM blog_posts
WHERE slug = $1
  AND status = 'published'
LIMIT 1
";

$result = pg_query_params($con, $sql, [$slug]);

if (!$result || pg_num_rows($result) === 0) {
    http_response_code(404);
    exit;
}

$post = pg_fetch_assoc($result);

// --------------------------------------------------
// Meta values
// --------------------------------------------------
$title = htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8');

$description = htmlspecialchars(
    $post['excerpt']
        ?: substr(strip_tags($post['content']), 0, 160),
    ENT_QUOTES,
    'UTF-8'
);

$image = processImageUrl($post['featured_image'])
    ?: 'https://login.rssi.in/img/default-og-image.jpg';

// --------------------------------------------------
// Frontend URL
// --------------------------------------------------
$frontendUrl = "https://rssi.in/blog/blog-detail.html?slug={$slug}";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= $title ?></title>

    <!-- SEO -->
    <meta name="description" content="<?= $description ?>">
    <link rel="canonical" href="<?= $frontendUrl ?>">

    <!-- Open Graph -->
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

<?php if (!$isBot): ?>
    <!-- Redirect ONLY real users -->
    <script>
        window.location.href = "<?= $frontendUrl ?>";
    </script>
<?php endif; ?>

</head>
<body></body>
</html>
