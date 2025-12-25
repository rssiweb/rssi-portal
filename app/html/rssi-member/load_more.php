<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include(__DIR__ . "/../image_functions.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();
?>

<?php
// Fetch dynamically loaded events
$offset = $_GET['offset'];
$limit = 3;
$current_user_id = $associatenumber;

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
            SELECT COALESCE(m2.fullname, s2.applicant_name) AS fullname 
            FROM likes l 
            LEFT JOIN rssimyaccount_members m2 ON l.user_id = m2.associatenumber
            LEFT JOIN signup s2 ON l.user_id = s2.application_number 
            WHERE l.event_id = e.event_id 
            LIMIT 10
        ) AS liked_users
    FROM events e
    JOIN rssimyaccount_members m ON e.created_by = m.associatenumber
    WHERE review_status='Approved'
    ORDER BY e.created_at DESC
    OFFSET $2 LIMIT $3
";

$result = pg_query_params($con, $query, array($current_user_id, $offset, $limit));

$events = [];
while ($row = pg_fetch_assoc($result)) {
    // Process the image URL using shared function
    $row['event_image_url'] = processImageUrl($row['event_image_url']);

    $row['liked_users'] = $row['liked_users']
        ? array_map(function ($user) {
            return trim($user, '"');
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