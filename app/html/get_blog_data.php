<?php
require_once __DIR__ . '/../bootstrap.php';

// Include the necessary functions
function getLikeCount($event_id, $con)
{
    $query = "SELECT COUNT(*) FROM likes WHERE event_id = $1";
    $result = pg_query_params($con, $query, array($event_id));
    return pg_fetch_result($result, 0, 0);
}

function getLikedUsers($event_id, $con)
{
    $query = "
        SELECT m.fullname AS name
        FROM likes l
        JOIN rssimyaccount_members m ON l.user_id = m.associatenumber
        WHERE l.event_id = $1
        UNION
        SELECT s.applicant_name AS name
        FROM likes l
        JOIN signup s ON l.user_id = s.application_number
        WHERE l.event_id = $1
    ";
    $result = pg_query_params($con, $query, array($event_id));

    $likedUsers = [];
    while ($row = pg_fetch_assoc($result)) {
        $likedUsers[] = $row['name'];
    }

    shuffle($likedUsers);

    return $likedUsers;
}

// Function to process event_image_url
function processImageUrl($imageUrl)
{
    $pattern = '/\/d\/([a-zA-Z0-9_-]+)/';
    if (preg_match($pattern, $imageUrl, $matches)) {
        $photoID = $matches[1];
        return "https://drive.google.com/file/d/{$photoID}/preview";
    }
    return $imageUrl; // Return the original URL if the pattern doesn't match
}

// Get offset and limit from query parameters
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 3;

// Fetch events with pagination
$query_events = "
    SELECT e.event_name, e.event_id, e.event_description, e.event_date, e.event_location, e.event_image_url, 
           e.created_at, m.fullname, m.photo 
    FROM events e
    JOIN rssimyaccount_members m ON e.created_by = m.associatenumber
    WHERE e.review_status = 'Approved'
    ORDER BY e.created_at DESC
    LIMIT $limit OFFSET $offset";
$result_events = pg_query($con, $query_events);

$events = [];
while ($row = pg_fetch_assoc($result_events)) {
    // Process the event_image_url
    if (!empty($row['event_image_url'])) {
        $row['event_image_url'] = processImageUrl($row['event_image_url']);
    }
    $events[] = $row;
}

// Fetch likes for events
$likes = [];
foreach ($events as $event) {
    $event_id = $event['event_id'];
    $likeCount = getLikeCount($event_id, $con);
    $likedUsers = getLikedUsers($event_id, $con);
    $likes[$event_id] = [
        'like_count' => $likeCount,
        'liked_users' => $likedUsers
    ];
}

// Combine all data into a single response
$response = [
    'success' => true,
    'events' => $events,
    'likes' => $likes,
    'has_more' => count($events) === $limit // Indicates if there are more events to load
];

// Output as JSON
header('Content-Type: application/json');
echo json_encode($response);
?>