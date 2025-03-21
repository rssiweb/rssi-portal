<?php
require_once __DIR__ . '/../bootstrap.php'; // Include your database connection file

// Check if the request is for all news (used by the "View More" modal)
$fetchAllNews = isset($_GET['fetch_all_news']) && $_GET['fetch_all_news'] === 'true';

if ($fetchAllNews) {
    // Fetch all news items for the "View More" modal
    $query_news = "
        SELECT noticeid, refnumber, date, subject, url 
        FROM notice 
        WHERE category = 'News & Press Releases' 
        ORDER BY date DESC";
    $result_news = pg_query($con, $query_news);

    $news = [];
    while ($row = pg_fetch_assoc($result_news)) {
        $news[] = $row;
    }

    // Return all news data as JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'news' => $news
    ]);
    exit; // Stop further execution
}

// Default behavior: Fetch latest 5 news items and 3 public notices
$query_notices = "
    SELECT noticeid, refnumber, date, subject, url 
    FROM notice 
    WHERE category = 'Public' 
    ORDER BY date DESC 
    LIMIT 3";
$result_notices = pg_query($con, $query_notices);

$notices = [];
while ($row = pg_fetch_assoc($result_notices)) {
    $notices[] = $row;
}

$query_news = "
    SELECT noticeid, refnumber, date, subject, url 
    FROM notice 
    WHERE category = 'News & Press Releases' 
    ORDER BY date DESC 
    LIMIT 5";
$result_news = pg_query($con, $query_news);

$news = [];
while ($row = pg_fetch_assoc($result_news)) {
    $news[] = $row;
}

// Return default data as JSON
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'notices' => $notices,
    'news' => $news
]);
?>