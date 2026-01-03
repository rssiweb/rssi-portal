<?php
// test_debug.php
require_once __DIR__ . "/../../bootstrap.php";
header('Content-Type: application/json');

$limit = 6;
$offset = 0;
$category = null;
$tag = null;
$search = null;

// Build query
$where = "WHERE status = 'published'";
$params = [];

echo "<h2>Debug Information</h2>";

// Test 1: Direct query without params
$testSql = "SELECT id, title FROM blog_posts WHERE status = 'published' LIMIT 5";
$testResult = pg_query($con, $testSql);

echo "<h3>Test Query 1 (Simple):</h3>";
echo "SQL: " . htmlspecialchars($testSql) . "<br>";

if ($testResult) {
    $count = pg_num_rows($testResult);
    echo "Rows found: " . $count . "<br>";
    while ($row = pg_fetch_assoc($testResult)) {
        echo "ID: " . $row['id'] . " - Title: " . $row['title'] . "<br>";
    }
} else {
    echo "Query failed: " . pg_last_error($con) . "<br>";
}

// Test 2: Full query with params
$params = [$limit, $offset];
$sql = "SELECT 
            id, title, slug, excerpt, content, 
            featured_image, category, tags, 
            author_name, author_photo,
            views, reading_time,
            created_at, published_at
        FROM blog_posts 
        WHERE status = 'published'
        ORDER BY published_at DESC 
        LIMIT $1 OFFSET $2";

echo "<h3>Test Query 2 (With params):</h3>";
echo "SQL: " . htmlspecialchars($sql) . "<br>";
echo "Params: limit=$limit, offset=$offset<br>";

$result = pg_query_params($con, $sql, $params);

if ($result) {
    $count = pg_num_rows($result);
    echo "Rows found: " . $count . "<br>";
    while ($row = pg_fetch_assoc($result)) {
        echo "ID: " . $row['id'] . " - Title: " . $row['title'] . "<br>";
    }
} else {
    echo "Query failed: " . pg_last_error($con) . "<br>";
}

// Test 3: Check table structure
echo "<h3>Table Structure:</h3>";
$structureSql = "SELECT column_name, data_type FROM information_schema.columns 
                 WHERE table_name = 'blog_posts' 
                 ORDER BY ordinal_position";
$structureResult = pg_query($con, $structureSql);

if ($structureResult) {
    echo "<table border='1'><tr><th>Column</th><th>Type</th></tr>";
    while ($col = pg_fetch_assoc($structureResult)) {
        echo "<tr><td>{$col['column_name']}</td><td>{$col['data_type']}</td></tr>";
    }
    echo "</table>";
}

// Test 4: Check data directly
echo "<h3>All Posts in Database:</h3>";
$allSql = "SELECT id, title, status, published_at FROM blog_posts ORDER BY id";
$allResult = pg_query($con, $allSql);

if ($allResult) {
    echo "<table border='1'><tr><th>ID</th><th>Title</th><th>Status</th><th>Published At</th></tr>";
    while ($post = pg_fetch_assoc($allResult)) {
        echo "<tr><td>{$post['id']}</td><td>{$post['title']}</td><td>{$post['status']}</td><td>{$post['published_at']}</td></tr>";
    }
    echo "</table>";
}
?>