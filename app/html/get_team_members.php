<?php
require_once __DIR__ . '/../bootstrap.php';
include($_SERVER['DOCUMENT_ROOT'] . '/image_functions.php');

header('Content-Type: application/json');

try {
    // Fetch active team members, ordered by display_order
    $query = "
        SELECT id, name, role, photo_url, full_quote, short_quote,
               facebook_url, twitter_url, instagram_url, linkedin_url
        FROM team_members
        WHERE is_active = 1
        ORDER BY display_order ASC, id ASC
    ";
    $result = pg_query($con, $query);

    if (!$result) {
        throw new Exception(pg_last_error($con));
    }

    $members = [];
    while ($row = pg_fetch_assoc($result)) {
        // Process image URL through the same helper used for events,
        // in case photo_url is a Cloudinary / storage path
        if (!empty($row['photo_url'])) {
            $row['photo_url'] = processImageUrl($row['photo_url']);
        }
        $members[] = $row;
    }

    echo json_encode([
        'success' => true,
        'members' => $members
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Failed to load team members'
    ]);
}
