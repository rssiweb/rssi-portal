<?php
// ===============================
// blog-meta.php
// Server-rendered meta for social crawlers
// ===============================

// Validate slug
$slug = $_GET['slug'] ?? '';
if (!$slug) {
    http_response_code(404);
    exit;
}

// Detect environment (same logic as JS)
$isLocal = in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1']);

$API_BASE = $isLocal
    ? 'http://localhost:8082/blog-api/'
    : 'https://login.rssi.in/blog-api/';

// Build API URL
$apiUrl = $API_BASE . 'get_blog_detail.php?slug=' . urlencode($slug);

// Call API (cURL)
$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_FOLLOWLOCATION => true,
]);
$response = curl_exec($ch);
curl_close($ch);

// Decode response
$data = json_decode($response, true);

// Validate API response
if (!$data || empty($data['success']) || empty($data['post'])) {
    http_response_code(404);
    exit;
}

$post = $data['post'];

// Prepare meta values safely
$title = htmlspecialchars($post['title'] ?? 'RSSI NGO Blog');
$description = htmlspecialchars(
    $post['excerpt']
        ?? substr(strip_tags($post['content'] ?? ''), 0, 160)
);
$image = htmlspecialchars(
    $post['featured_image']
        ?? 'https://login.rssi.in/img/default-og-image.jpg'
);

// Canonical URL (main JS page)
$canonicalUrl = (
    $isLocal
    ? 'http://localhost/blog-detail.html'
    : 'https://login.rssi.in/blog-detail.html'
) . '?slug=' . urlencode($slug);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">

    <title><?= $title ?></title>

    <!-- Basic SEO -->
    <meta name="description" content="<?= $description ?>">
    <link rel="canonical" href="<?= $canonicalUrl ?>">

    <!-- Open Graph -->
    <meta property="og:type" content="article">
    <meta property="og:title" content="<?= $title ?>">
    <meta property="og:description" content="<?= $description ?>">
    <meta property="og:image" content="<?= $image ?>">
    <meta property="og:url" content="<?= $canonicalUrl ?>">
    <meta property="og:site_name" content="RSSI NGO">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= $title ?>">
    <meta name="twitter:description" content="<?= $description ?>">
    <meta name="twitter:image" content="<?= $image ?>">

    <!-- Redirect humans to JS page -->
    <meta http-equiv="refresh" content="0;url=<?= $canonicalUrl ?>">

    <!-- Cache (recommended) -->
    <meta http-equiv="Cache-Control" content="public, max-age=600">
</head>

<body></body>

</html>