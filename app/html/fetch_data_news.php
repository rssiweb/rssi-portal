<?php
require_once __DIR__ . '/../bootstrap.php'; // Include your database connection file

// Fetch latest 5 public notices
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

// Fetch latest 5 news items
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

// Return data as JSON
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'notices' => $notices,
    'news' => $news
]);
?>