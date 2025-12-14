<?php
require_once __DIR__ . '/../bootstrap.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/image_functions.php';

// Assume current logged-in user ID is available
$current_user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

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

function hasUserLiked($event_id, $user_id, $con)
{
    if (!$user_id) return false; // if not logged in
    $query = "SELECT 1 FROM likes WHERE event_id = $1 AND user_id = $2";
    $result = pg_query_params($con, $query, array($event_id, $user_id));
    return pg_num_rows($result) > 0;
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
$likes = [];

// In get_blog_data.php, modify the while loop:
while ($row = pg_fetch_assoc($result_events)) {
    if (!empty($row['event_image_url'])) {
        $row['event_image_url'] = processImageUrl($row['event_image_url']);
    }

    $event_id = $row['event_id'];
    $likeCount = getLikeCount($event_id, $con);
    $likedUsers = getLikedUsers($event_id, $con);
    $userLiked = hasUserLiked($event_id, $current_user_id, $con);

    $likes[$event_id] = [
        'like_count' => $likeCount,
        'liked_users' => $likedUsers,
        'liked' => $userLiked  // This indicates if current user liked this event
    ];

    $events[] = $row;
}

$response = [
    'success' => true,
    'events' => $events,
    'likes' => $likes,
    'has_more' => count($events) === $limit
];

header('Content-Type: application/json');
echo json_encode($response);
