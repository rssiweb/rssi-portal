<?php
require_once __DIR__ . "/bootstrap.php";
include __DIR__ . "/image_functions.php";

$slug = $_GET['slug'] ?? '';
if (!$slug) exit;

$sql = "SELECT title, excerpt, featured_image, slug 
        FROM blog_posts 
        WHERE slug = $1 AND status = 'published' LIMIT 1";

$result = pg_query_params($con, $sql, [$slug]);
if (!$result || pg_num_rows($result) === 0) exit;

$post = pg_fetch_assoc($result);

// Auto-generate meta
$title = htmlspecialchars($post['title'] . " | RSSI NGO Blog");
$description = htmlspecialchars(
    $post['excerpt'] ?: mb_substr(strip_tags($post['title']), 0, 160)
);

$image = processImageUrl($post['featured_image']) 
    ?: "https://rssi.in/img/default-og.jpg";

$url = "https://rssi.in/blog/" . $post['slug'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= $title ?></title>

<meta name="description" content="<?= $description ?>">

<meta property="og:type" content="article">
<meta property="og:title" content="<?= $title ?>">
<meta property="og:description" content="<?= $description ?>">
<meta property="og:image" content="<?= $image ?>">
<meta property="og:url" content="<?= $url ?>">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= $title ?>">
<meta name="twitter:description" content="<?= $description ?>">
<meta name="twitter:image" content="<?= $image ?>">

</head>
<body></body>
</html>
