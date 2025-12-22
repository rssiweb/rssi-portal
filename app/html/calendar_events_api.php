<?php
require_once __DIR__ . '/../bootstrap.php';
include(__DIR__ . "/../util/login_util.php");
header('Content-Type: application/json');

// Get date range from request
$start = $_GET['start'] ?? date('Y-m-01');
$end = $_GET['end'] ?? date('Y-m-t');

$response = [
    'holidays' => [],
    'events' => []
];

// Fetch holidays
$holidayResult = pg_query_params(
    $con,
    "SELECT holiday_name as name, holiday_date::date as date 
     FROM holidays 
     WHERE holiday_date BETWEEN $1 AND $2 
     ORDER BY holiday_date",
    [$start, $end]
);

if ($holidayResult) {
    while ($row = pg_fetch_assoc($holidayResult)) {
        $response['holidays'][] = [
            'name' => $row['name'],
            'date' => $row['date']
        ];
    }
}

// Fetch internal events
// In the events query section, update to:
$eventResult = pg_query_params(
    $con,
    "SELECT 
        event_name as name, 
        event_date::date as date, 
        event_type as type,
        is_full_day,
        event_start_time,
        event_end_time,
        reporting_time,
        location,
        description
     FROM internal_events 
     WHERE event_date BETWEEN $1 AND $2 
     ORDER BY event_date",
    [$start, $end]
);

if ($eventResult) {
    while ($row = pg_fetch_assoc($eventResult)) {
        $eventData = [
            'name' => $row['name'],
            'date' => $row['date'],
            'type' => $row['type'] ?? 'event',
            'is_full_day' => $row['is_full_day'] == 't',
            'location' => $row['location'],
            'description' => $row['description']
        ];
        
        // Add time fields if not full day
        if ($row['is_full_day'] == 'f') {
            $eventData['start_time'] = $row['event_start_time'];
            $eventData['end_time'] = $row['event_end_time'];
            $eventData['reporting_time'] = $row['reporting_time'];
        }
        
        $response['events'][] = $eventData;
    }
}

echo json_encode($response);
