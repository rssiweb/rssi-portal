<?php
require_once __DIR__ . '/../bootstrap.php';
include(__DIR__ . "/../util/login_util.php");

// Set headers first to ensure JSON response
header('Content-Type: application/json');

// Turn off error display to users (log to file instead)
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Get specific date from request
$date = $_GET['date'] ?? date('Y-m-d');

// Initialize response
$response = [
    'date' => $date,
    'holidays' => [],
    'events' => [],
    'error' => null
];

try {
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        throw new Exception('Invalid date format. Use YYYY-MM-DD');
    }

    // Fetch holidays for this specific date
    $holidayResult = pg_query_params(
        $con,
        "SELECT holiday_name, holiday_date::date as date
         FROM holidays 
         WHERE holiday_date = $1 
         ORDER BY holiday_name",
        [$date]
    );

    if ($holidayResult) {
        while ($row = pg_fetch_assoc($holidayResult)) {
            $response['holidays'][] = [
                'name' => $row['holiday_name'],
                'date' => $row['date']
            ];
        }
    } else {
        error_log("Holiday query failed for date: $date");
    }

    // Fetch events for this specific date with event_type display_name
    $eventResult = pg_query_params(
        $con,
        "SELECT e.*, 
                u.fullname AS created_by_name, 
                u2.fullname AS updated_by_name,
                et.display_name AS event_type_name
         FROM internal_events e 
         LEFT JOIN rssimyaccount_members u ON e.created_by = u.associatenumber 
         LEFT JOIN rssimyaccount_members u2 ON e.updated_by = u2.associatenumber 
         LEFT JOIN event_types et ON e.event_type = et.id
         WHERE e.event_date = $1 
         ORDER BY e.created_at",
        [$date]
    );

    if ($eventResult) {
        while ($row = pg_fetch_assoc($eventResult)) {
            $event = [
                'id' => $row['id'],
                'name' => $row['event_name'],
                'date' => $row['event_date'],
                // Replace ID with display_name
                'type' => $row['event_type_name'] ?? 'Other',
                'is_full_day' => $row['is_full_day'] == 't',
                'location' => $row['location'],
                'description' => $row['description'],
                'created_by' => $row['created_by'],
                'created_by_name' => $row['created_by_name'],
                'created_at' => $row['created_at'],
                'updated_by' => $row['updated_by'],
                'updated_by_name' => $row['updated_by_name'],
                'updated_at' => $row['updated_at']
            ];

            // Add time fields if not full day
            if ($row['is_full_day'] == 'f') {
                $event['start_time'] = $row['event_start_time'] ? date('h:i A', strtotime($row['event_start_time'])) : null;
                $event['end_time'] = $row['event_end_time'] ? date('h:i A', strtotime($row['event_end_time'])) : null;
                $event['reporting_time'] = $row['reporting_time'] ? date('h:i A', strtotime($row['reporting_time'])) : null;
            }

            $response['events'][] = $event;
        }
    } else {
        error_log("Event query failed for date: $date");
    }

    // Add summary
    $response['summary'] = [
        'total_holidays' => count($response['holidays']),
        'total_events' => count($response['events']),
        'has_data' => !empty($response['holidays']) || !empty($response['events'])
    ];
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
    http_response_code(400);
}

// Always output JSON, even if there's an error
echo json_encode($response);
exit;
