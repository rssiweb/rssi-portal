<?php
require_once __DIR__ . '/../bootstrap.php';
include(__DIR__ . "/../util/login_util.php");

// Set headers first
header('Content-Type: application/json');
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Get date range from request
$start = $_GET['start'] ?? date('Y-m-01');
$end = $_GET['end'] ?? date('Y-m-t');

$response = [
    'holiday_dates' => [],
    'event_dates' => [],
    'error' => null
];

try {
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
        throw new Exception('Invalid date format. Use YYYY-MM-DD');
    }

    // Get dates that have holidays
    $holidayResult = pg_query_params(
        $con,
        "SELECT DISTINCT holiday_date::date as date 
         FROM holidays 
         WHERE holiday_date BETWEEN $1 AND $2 
         ORDER BY holiday_date",
        [$start, $end]
    );

    if ($holidayResult) {
        while ($row = pg_fetch_assoc($holidayResult)) {
            $response['holiday_dates'][] = $row['date'];
        }
    }

    // Get dates that have events
    $eventResult = pg_query_params(
        $con,
        "SELECT DISTINCT event_date::date as date 
         FROM internal_events 
         WHERE event_date BETWEEN $1 AND $2 
         ORDER BY event_date",
        [$start, $end]
    );

    if ($eventResult) {
        while ($row = pg_fetch_assoc($eventResult)) {
            $response['event_dates'][] = $row['date'];
        }
    }

    // Add metadata
    $response['meta'] = [
        'date_range' => ['start' => $start, 'end' => $end],
        'holiday_count' => count($response['holiday_dates']),
        'event_count' => count($response['event_dates']),
        'timestamp' => date('c')
    ];
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);
exit;
