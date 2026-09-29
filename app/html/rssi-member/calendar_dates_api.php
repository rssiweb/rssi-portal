<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

// Set headers first
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Get date range from request
$start = $_GET['start'] ?? date('Y-m-01');
$end   = $_GET['end']   ?? date('Y-m-t');

// Admins see everything; others are scoped to their department
$isAdmin = (($role ?? '') === 'Admin');

$userDepb = null;
if (!$isAdmin && !empty($associatenumber)) {
    $depbRes = pg_query_params(
        $con,
        "SELECT depb FROM rssimyaccount_members WHERE associatenumber = \$1",
        [$associatenumber]
    );
    if ($depbRes) {
        $depbRow = pg_fetch_assoc($depbRes);
        if ($depbRow && $depbRow['depb'] !== null && $depbRow['depb'] !== '') {
            $userDepb = (int)$depbRow['depb'];
        }
    }
}

$response = [
    'holiday_dates' => [],
    'event_dates'   => [],
    'user_depb'     => $userDepb,
    'error'         => null
];

try {
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
        throw new Exception('Invalid date format. Use YYYY-MM-DD');
    }

    // ---------- HOLIDAYS ----------
    if ($isAdmin) {
        // Admin: no location filter at all
        $holidaySql = "
            SELECT DISTINCT holiday_date::date AS date
            FROM holidays
            WHERE holiday_date BETWEEN \$1 AND \$2
            ORDER BY holiday_date";
        $holidayResult = pg_query_params($con, $holidaySql, [$start, $end]);
    } elseif ($userDepb !== null) {
        $holidaySql = "
            SELECT DISTINCT holiday_date::date AS date
            FROM holidays
            WHERE holiday_date BETWEEN \$1 AND \$2
              AND (location IS NULL OR location = \$3)
            ORDER BY holiday_date";
        $holidayResult = pg_query_params($con, $holidaySql, [$start, $end, $userDepb]);
    } else {
        $holidaySql = "
            SELECT DISTINCT holiday_date::date AS date
            FROM holidays
            WHERE holiday_date BETWEEN \$1 AND \$2
              AND location IS NULL
            ORDER BY holiday_date";
        $holidayResult = pg_query_params($con, $holidaySql, [$start, $end]);
    }

    if ($holidayResult) {
        while ($row = pg_fetch_assoc($holidayResult)) {
            $response['holiday_dates'][] = $row['date'];
        }
    }

    // ---------- EVENTS ----------
    if ($isAdmin) {
        $eventSql = "
            SELECT DISTINCT event_date::date AS date
            FROM internal_events
            WHERE event_date BETWEEN \$1 AND \$2
            ORDER BY event_date";
        $eventResult = pg_query_params($con, $eventSql, [$start, $end]);
    } elseif ($userDepb !== null) {
        $eventSql = "
            SELECT DISTINCT event_date::date AS date
            FROM internal_events
            WHERE event_date BETWEEN \$1 AND \$2
              AND (location IS NULL OR location = \$3)
            ORDER BY event_date";
        $eventResult = pg_query_params($con, $eventSql, [$start, $end, $userDepb]);
    } else {
        $eventSql = "
            SELECT DISTINCT event_date::date AS date
            FROM internal_events
            WHERE event_date BETWEEN \$1 AND \$2
              AND location IS NULL
            ORDER BY event_date";
        $eventResult = pg_query_params($con, $eventSql, [$start, $end]);
    }

    if ($eventResult) {
        while ($row = pg_fetch_assoc($eventResult)) {
            $response['event_dates'][] = $row['date'];
        }
    }

    // Metadata
    $response['meta'] = [
        'date_range'    => ['start' => $start, 'end' => $end],
        'holiday_count' => count($response['holiday_dates']),
        'event_count'   => count($response['event_dates']),
        'timestamp'     => date('c')
    ];
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);
exit;
