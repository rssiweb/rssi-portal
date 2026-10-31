<?php
// Place this file in the same folder as get_blog_data.php (so ../bootstrap.php and CORS behave the same).
require_once __DIR__ . '/../bootstrap.php';
include($_SERVER['DOCUMENT_ROOT'] . '/image_functions.php');

header('Content-Type: application/json');

/*
 * Academic year runs from this month to the month before it, a year later.
 * 4 = April to March. Must match START_MONTH in academic-calendar.html.
 */
const ACADEMIC_START_MONTH = 4;

/* =====================================================================
 * 1) ?action=locations  ->  just the location names (tiny, loaded first)
 * ===================================================================== */
if (($_GET['action'] ?? '') === 'locations') {
    // To list only active locations, add:  WHERE is_active = TRUE
    $locations = [];
    $locRes = pg_query($con, "SELECT name FROM office_locations ORDER BY name ASC");
    if (!$locRes) {
        error_log('get_academic_calendar (locations): ' . pg_last_error($con));
        echo json_encode(['success' => false, 'message' => 'Could not load locations.']);
        exit;
    }
    while ($l = pg_fetch_assoc($locRes)) $locations[] = $l['name'];

    echo json_encode(['success' => true, 'locations' => $locations]);
    exit;
}

/* =====================================================================
 * 2) ?year=2025&location=Lucknow  ->  events for ONE location and academic year
 * ===================================================================== */
$location = trim($_GET['location'] ?? '');
if ($location === '') {
    echo json_encode(['success' => false, 'message' => 'Please select a location.']);
    exit;
}

$today       = new DateTime('today');
$defaultYear = (int)$today->format('Y') - ((int)$today->format('n') < ACADEMIC_START_MONTH ? 1 : 0);

$year = isset($_GET['year']) ? (int)$_GET['year'] : $defaultYear;
if ($year < 2000 || $year > 2100) $year = $defaultYear;

$start = sprintf('%04d-%02d-01', $year, ACADEMIC_START_MONTH);      // inclusive
$end   = sprintf('%04d-%02d-01', $year + 1, ACADEMIC_START_MONTH);  // exclusive
$label = ACADEMIC_START_MONTH === 1 ? (string)$year : $year . '-' . substr((string)($year + 1), 2);

/*
 * internal_events
 *   JOIN office_locations : internal_events.location holds the location id; we match it to the
 *                           chosen location name.
 *   LEFT JOIN events      : events.event_name_id = internal_events.id (the posts shown in
 *                           Activities & Initiatives). Only Approved posts. An event can have
 *                           several posts, so rows are grouped per event below.
 */
$sql = "
    SELECT ie.id,
           ie.event_name,
           to_char(ie.event_date, 'YYYY-MM-DD') AS event_date,
           ie.event_type,
           ie.description,
           ol.name              AS location_name,
           e.event_id           AS post_id,
           e.event_description  AS post_description,
           e.event_image_url    AS post_image,
           e.created_at         AS post_created_at,
           m.fullname           AS post_author,
           m.photo              AS post_author_photo
    FROM internal_events ie
    JOIN office_locations ol
           ON ol.id::text = btrim(ie.location::text)
    LEFT JOIN events e
           ON e.event_name_id = ie.id
          AND e.review_status = 'Approved'
    LEFT JOIN rssimyaccount_members m
           ON m.associatenumber = e.created_by
    WHERE ie.event_date >= $1
      AND ie.event_date <  $2
      AND lower(ol.name) = lower($3)
    ORDER BY ie.event_date DESC, ie.event_start_time ASC NULLS LAST, ie.id ASC, e.created_at DESC";

$res = pg_query_params($con, $sql, [$start, $end, $location]);

if (!$res) {
    error_log('get_academic_calendar: ' . pg_last_error($con));
    echo json_encode(['success' => false, 'message' => 'Could not load the academic calendar.']);
    exit;
}

/* ---------- Group rows: one entry per event, with all its approved posts ---------- */
$events = [];
while ($r = pg_fetch_assoc($res)) {
    $id = (int)$r['id'];

    if (!isset($events[$id])) {
        $events[$id] = [
            'id'          => $id,
            'name'        => $r['event_name'],
            'date'        => $r['event_date'],
            'type'        => $r['event_type'],
            'description' => $r['description'],
            'location'    => $r['location_name'],
            'posts'       => [],
        ];
    }

    if (!empty($r['post_id'])) {
        $events[$id]['posts'][] = [
            'id'           => (int)$r['post_id'],
            'description'  => $r['post_description'],
            'image'        => !empty($r['post_image']) ? processImageUrl($r['post_image']) : null,
            'created_at'   => $r['post_created_at'],
            'author'       => $r['post_author'],
            'author_photo' => $r['post_author_photo'],
        ];
    }
}

echo json_encode([
    'success'  => true,
    'year'     => $year,
    'label'    => $label,
    'location' => $location,
    'events'   => array_values($events),
]);
