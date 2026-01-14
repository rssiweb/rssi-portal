<?php
http_response_code(200);
header("Content-Type: text/html; charset=UTF-8");
header("Accept-Ranges: none");

require_once __DIR__ . '/../bootstrap.php';
include(__DIR__ . "/image_functions.php");

$slug = trim($_GET['slug'] ?? '');
if ($slug === '') {
    http_response_code(404);
    exit;
}

$sql = "
SELECT title, excerpt, content, featured_image, slug
FROM blog_posts
WHERE slug = $1 AND status = 'published'
LIMIT 1
";

$res = pg_query_params($con, $sql, [$slug]);
if (!$res || pg_num_rows($res) === 0) {
    http_response_code(404);
    exit;
}

$post = pg_fetch_assoc($res);

$title = htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8');
$description = htmlspecialchars(
    $post['excerpt'] ?: substr(strip_tags($post['content']), 0, 160),
    ENT_QUOTES,
    'UTF-8'
);

$image = processImageUrl($post['featured_image'])
    ?: 'https://login.rssi.in/img/default-og-image.jpg';

$selfUrl = "https://login.rssi.in/blog-detail.php?slug={$slug}";
$frontendUrl = "https://rssi.in/blog/blog-detail.html?slug={$slug}";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title><?= $title ?></title>

    <meta name="description" content="<?= $description ?>">

    <!-- Canonical MUST be THIS page for LinkedIn -->
    <link rel="canonical" href="<?= $selfUrl ?>">

    <!-- Open Graph -->
    <meta property="og:type" content="article">
    <meta property="og:title" content="<?= $title ?>">
    <meta property="og:description" content="<?= $description ?>">
    <meta property="og:image" content="<?= $image ?>">
    <meta property="og:url" content="<?= $selfUrl ?>">
    <meta property="og:site_name" content="RSSI NGO">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= $title ?>">
    <meta name="twitter:description" content="<?= $description ?>">
    <meta name="twitter:image" content="<?= $image ?>">

    <!-- Redirect real users ONLY -->
    <script>
        if (!/linkedinbot|facebookexternalhit|twitterbot|whatsapp/i.test(navigator.userAgent)) {
            window.location.href = "<?= $frontendUrl ?>";
        }
    </script>

</head>

<body></body>

</html>