<?php
require_once __DIR__ . '/../bootstrap.php';
include(__DIR__ . "/../util/login_util.php");

// Set headers first to ensure JSON response
header('Content-Type: application/json');

// Turn off error display to users (log to file instead)
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// IMPORTANT: Check if this is a search request FIRST
// Use isset() and check if 'search' parameter exists in GET request
$isSearchRequest = isset($_GET['search']) && $_GET['search'] !== '';
$date = $_GET['date'] ?? date('Y-m-d');

try {
    // ========== HANDLE SEARCH REQUEST (for Select2 dropdown) ==========
    if ($isSearchRequest) {
        $searchTerm = trim($_GET['search']);

        // If search term is empty, return empty results
        if (empty($searchTerm)) {
            echo json_encode(['results' => []]);
            exit;
        }

        // Search events across all dates
        $searchQuery = "
            SELECT 
                e.id, 
                e.event_name, 
                e.event_date,
                TO_CHAR(e.event_date, 'DD Mon YYYY') as formatted_date,
                e.location,
                COALESCE(et.display_name, 'Other') as event_type,
                e.applicable_classes,
                e.is_full_day,
                e.event_start_time,
                e.event_end_time,
                e.reporting_time,
                e.description,
                u.fullname AS created_by_name
            FROM internal_events e 
            LEFT JOIN rssimyaccount_members u ON e.created_by = u.associatenumber 
            LEFT JOIN event_types et ON e.event_type = et.id
            WHERE e.event_name ILIKE $1 
               OR CAST(e.id AS TEXT) ILIKE $1
            ORDER BY e.event_date DESC
            LIMIT 20";

        $searchResult = pg_query_params($con, $searchQuery, ["%$searchTerm%"]);

        if (!$searchResult) {
            echo json_encode(['results' => [], 'error' => pg_last_error($con)]);
            exit;
        }

        $events = [];
        while ($row = pg_fetch_assoc($searchResult)) {
            $event = [
                'id' => $row['id'],
                'event_name' => $row['event_name'],
                'event_date' => $row['event_date'],
                'formatted_date' => $row['formatted_date'],
                'event_type' => $row['event_type'],
                'location' => $row['location'] ?? 'Not specified',
                'applicable_classes' => $row['applicable_classes'],
                'description' => $row['description'],
                'is_full_day' => $row['is_full_day'] == 't',
                'created_by_name' => $row['created_by_name']
            ];

            // Add time fields if not full day
            if ($row['is_full_day'] == 'f') {
                $event['start_time'] = $row['event_start_time'] ? date('h:i A', strtotime($row['event_start_time'])) : null;
                $event['end_time'] = $row['event_end_time'] ? date('h:i A', strtotime($row['event_end_time'])) : null;
                $event['reporting_time'] = $row['reporting_time'] ? date('h:i A', strtotime($row['reporting_time'])) : null;
            }

            $events[] = $event;
        }

        // Return search results in Select2 format
        $results = array_map(function ($event) {
            return [
                'id' => $event['id'],
                'text' => $event['id'] . ' - ' . $event['event_name'] . ' (' . $event['formatted_date'] . ')',
                'event_name' => $event['event_name'],
                'event_date' => $event['event_date'],
                'formatted_date' => $event['formatted_date'],
                'location' => $event['location'],
                'event_type' => $event['event_type'],
                'applicable_classes' => $event['applicable_classes'],
                'description' => $event['description'],
                'is_full_day' => $event['is_full_day'],
                'start_time' => $event['start_time'] ?? null,
                'end_time' => $event['end_time'] ?? null,
                'reporting_time' => $event['reporting_time'] ?? null,
                'created_by_name' => $event['created_by_name']
            ];
        }, $events);

        echo json_encode(['results' => $results]);
        exit;
    }

    // ========== HANDLE DATE-SPECIFIC REQUEST (original functionality) ==========
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        echo json_encode(['error' => 'Invalid date format. Use YYYY-MM-DD']);
        exit;
    }

    $response = [
        'date' => $date,
        'holidays' => [],
        'events' => [],
        'error' => null
    ];

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
    }

    // Fetch events for this specific date
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
                'type' => $row['event_type_name'] ?? 'Other',
                'is_full_day' => $row['is_full_day'] == 't',
                'location' => $row['location'],
                'description' => $row['description'],
                'created_by' => $row['created_by'],
                'created_by_name' => $row['created_by_name'],
                'created_at' => $row['created_at'],
                'updated_by' => $row['updated_by'],
                'updated_by_name' => $row['updated_by_name'],
                'updated_at' => $row['updated_at'],
                'applicable_classes' => $row['applicable_classes']
            ];

            if ($row['is_full_day'] == 'f') {
                $event['start_time'] = $row['event_start_time'] ? date('h:i A', strtotime($row['event_start_time'])) : null;
                $event['end_time'] = $row['event_end_time'] ? date('h:i A', strtotime($row['event_end_time'])) : null;
                $event['reporting_time'] = $row['reporting_time'] ? date('h:i A', strtotime($row['reporting_time'])) : null;
            }

            $response['events'][] = $event;
        }
    }

    $response['summary'] = [
        'total_holidays' => count($response['holidays']),
        'total_events' => count($response['events']),
        'has_data' => !empty($response['holidays']) || !empty($response['events'])
    ];

    echo json_encode($response);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
exit;
