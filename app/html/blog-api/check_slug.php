<?php
require_once __DIR__ . "/../../bootstrap.php";
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$slug = $_POST['slug'] ?? '';
$post_id = (int)($_POST['post_id'] ?? 0);

if (!$slug) {
    echo json_encode(['success' => false, 'message' => 'Slug is required']);
    exit;
}

// Clean and validate slug
$slug = strtolower(trim($slug));
$slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
$slug = preg_replace('/\-+/', '-', $slug);
$slug = trim($slug, '-');

// Check if slug exists (excluding current post if editing)
$sql = "SELECT id, title FROM blog_posts WHERE slug = $1";
$params = [$slug];

if ($post_id > 0) {
    $sql .= " AND id != $2";
    $params[] = $post_id;
}

$result = pg_query_params($con, $sql, $params);

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$available = (pg_num_rows($result) === 0);

$suggestions = [];
if (!$available) {
    // Generate alternative suggestions
    $suggestions = generateSlugSuggestions($slug, $con);
}

echo json_encode([
    'success' => true,
    'available' => $available,
    'slug' => $slug,
    'suggestions' => $suggestions
]);

function generateSlugSuggestions($baseSlug, $connection) {
    $suggestions = [];
    
    // Try adding numbers
    for ($i = 1; $i <= 5; $i++) {
        $suggestion = $baseSlug . '-' . $i;
        $checkSql = "SELECT id FROM blog_posts WHERE slug = $1";
        $checkResult = pg_query_params($connection, $checkSql, [$suggestion]);
        
        if (pg_num_rows($checkResult) === 0) {
            $suggestions[] = $suggestion;
            if (count($suggestions) >= 3) break;
        }
    }
    
    // Try adding year
    $yearSuggestion = $baseSlug . '-' . date('Y');
    $checkSql = "SELECT id FROM blog_posts WHERE slug = $1";
    $checkResult = pg_query_params($connection, $checkSql, [$yearSuggestion]);
    
    if (pg_num_rows($checkResult) === 0) {
        $suggestions[] = $yearSuggestion;
    }
    
    // Try adding month
    $monthSuggestion = $baseSlug . '-' . strtolower(date('F'));
    $checkResult = pg_query_params($connection, $checkSql, [$monthSuggestion]);
    
    if (pg_num_rows($checkResult) === 0) {
        $suggestions[] = $monthSuggestion;
    }
    
    return array_slice($suggestions, 0, 3);
}