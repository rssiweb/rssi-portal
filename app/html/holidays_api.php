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
$eventResult = pg_query_params(
    $con,
    "SELECT event_name as name, event_date::date as date, event_type as type 
     FROM internal_events 
     WHERE event_date BETWEEN $1 AND $2 
     ORDER BY event_date",
    [$start, $end]
);

if ($eventResult) {
    while ($row = pg_fetch_assoc($eventResult)) {
        $response['events'][] = [
            'name' => $row['name'],
            'date' => $row['date'],
            'type' => $row['type']
        ];
    }
}

echo json_encode($response);
