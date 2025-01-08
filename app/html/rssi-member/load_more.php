<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();
?>
<?php
// Fetch dynamically loaded events based on the offset
$offset = $_GET['offset'];
$limit = 3; // Number of events per batch
$current_user_id = $associatenumber; // Assume $associatenumber holds the logged-in user's ID

$query = "
    SELECT 
        e.event_id,
        e.event_description,
        e.event_image_url,
        e.created_at,
        m.fullname,
        m.photo,
        (SELECT COUNT(*) FROM likes WHERE event_id = e.event_id) AS like_count,
        (SELECT COUNT(*) > 0 FROM likes WHERE event_id = e.event_id AND user_id = $1) AS liked,
        ARRAY(
            SELECT m2.fullname 
            FROM likes l 
            JOIN rssimyaccount_members m2 ON l.user_id = m2.associatenumber 
            WHERE l.event_id = e.event_id 
            LIMIT 10
        ) AS liked_users
    FROM events e
    JOIN rssimyaccount_members m ON e.created_by = m.associatenumber
    ORDER BY e.created_at DESC
    OFFSET $2 LIMIT $3
";

$result = pg_query_params($con, $query, array($current_user_id, $offset, $limit));

$events = [];
while ($row = pg_fetch_assoc($result)) {
    $row['liked_users'] = $row['liked_users'] 
    ? array_map(function($user) { 
        return trim($user, '"'); // Remove double quotes from each username 
      }, explode(',', trim($row['liked_users'], '{}'))) 
    : [];
    $events[] = $row;
}

echo json_encode([
    'success' => true,
    'events' => $events
]);
exit;
?>
