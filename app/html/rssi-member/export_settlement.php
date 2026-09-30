<?php
require_once __DIR__ . "/../../bootstrap.php";

$status = $_GET['status'] ?? 'unsettled';
$settlementDate = $_GET['settlement_date'] ?? date('Y-m-d');
$location = $_GET['location'] ?? '';

// Date range for settled view (default: last 30 days)
$dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$dateTo   = $_GET['date_to']   ?? date('Y-m-d');

// Build a sensible filename per branch
if ($status === 'settled') {
    $filename = 'settlement_settled_' . $dateFrom . '_to_' . $dateTo . '.csv';
} else {
    $filename = 'settlement_unsettled_' . $settlementDate . '.csv';
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$output = fopen('php://output', 'w');

if ($status === 'unsettled') {
    // Export unsettled payments
    fputcsv($output, [
        'Payment ID',
        'Date',
        'Student ID',
        'Student Name',
        'Class',
        'Month',
        'Year',
        'Amount',
        'Type',
        'Transaction ID',
        'Collector',
        'Location'  // Added Location column
    ]);

    $query = "SELECT p.id, p.collection_date, p.student_id, 
       COALESCE(s.studentname, m.fullname, h.name) AS studentname, 
       s.class, 
       COALESCE(
           ol_s.name,
           ol_m.name,
           ol_h.name
       ) AS location_name,
       p.month, p.academic_year, p.amount, p.payment_type, 
       p.transaction_id, c.fullname as collector_name
        FROM fee_payments p
        LEFT JOIN rssimyprofile_student s ON p.student_id = s.student_id
        LEFT JOIN rssimyaccount_members m ON p.student_id = m.associatenumber
        LEFT JOIN public_health_records h ON p.student_id = h.id::text
        LEFT JOIN rssimyaccount_members c ON p.collected_by = c.associatenumber
        LEFT JOIN office_locations ol_s ON ol_s.id = s.preferredbranch::int
        LEFT JOIN office_locations ol_m ON ol_m.id = m.basebranch::int
        LEFT JOIN office_locations ol_h ON ol_h.id = h.location_id
        WHERE p.is_settled = FALSE";

    // Add location filter if selected
    // preferredbranch and basebranch now store location IDs — compare directly.
    if (!empty($location)) {
        $locationId = pg_escape_string($con, $location);
        $query .= " AND (s.preferredbranch::int = '$locationId'::int
                    OR m.basebranch::int      = '$locationId'::int
                    OR h.location_id          = '$locationId'::int)";
    }

    $query .= " ORDER BY p.id DESC";

    $result = pg_query($con, $query);
    while ($row = pg_fetch_assoc($result)) {
        fputcsv($output, [
            $row['id'],
            date('d-M-Y H:i', strtotime($row['collection_date'])),
            $row['student_id'],
            $row['studentname'],
            $row['class'],
            $row['month'],
            $row['academic_year'],
            $row['amount'],
            $row['payment_type'],
            $row['transaction_id'] ?: 'N/A',
            $row['collector_name'],
            $row['location_name'] ?: 'N/A'  // resolved from ID to name
        ]);
    }
} else {
    // Export settled payments
    fputcsv($output, [
        'Settlement ID',
        'Date',
        'Total Amount',
        'Cash Amount',
        'Online Amount',
        'Settled By',
        'Location(s)',
        'Notes'
    ]);

    $query = "SELECT s.id, s.settlement_date, s.total_amount, s.cash_amount, 
                 s.online_amount, m.fullname as settled_by_name, s.notes,
                 sl.location_name
          FROM settlements s
          JOIN rssimyaccount_members m ON s.settled_by = m.associatenumber
                    LEFT JOIN (
              SELECT loc_data.settlement_id,
                     STRING_AGG(DISTINCT ol.name, ', ' ORDER BY ol.name) AS location_name
              FROM (
                  SELECT fp.settlement_id, s.preferredbranch::int AS location_id
                  FROM fee_payments fp
                  LEFT JOIN rssimyprofile_student s ON fp.student_id = s.student_id
                  WHERE fp.settlement_id IS NOT NULL AND s.preferredbranch IS NOT NULL
                  UNION
                  SELECT fp.settlement_id, m.basebranch::int AS location_id
                  FROM fee_payments fp
                  LEFT JOIN rssimyaccount_members m ON fp.student_id = m.associatenumber
                  WHERE fp.settlement_id IS NOT NULL AND m.basebranch IS NOT NULL
                  UNION
                  SELECT fp.settlement_id, h.location_id AS location_id
                  FROM fee_payments fp
                  LEFT JOIN public_health_records h ON fp.student_id = h.id::text
                  WHERE fp.settlement_id IS NOT NULL AND h.location_id IS NOT NULL
              ) loc_data
              LEFT JOIN office_locations ol ON ol.id = loc_data.location_id
              GROUP BY loc_data.settlement_id
          ) sl ON s.id = sl.settlement_id
          WHERE 1=1";

    // Add location filter if selected
    if (!empty($location)) {
        $locationId = pg_escape_string($con, $location);
        $query .= " AND s.id IN (
            SELECT DISTINCT fp.settlement_id
            FROM fee_payments fp
            LEFT JOIN rssimyprofile_student s ON fp.student_id = s.student_id
            LEFT JOIN rssimyaccount_members m ON fp.student_id = m.associatenumber
            LEFT JOIN public_health_records h ON fp.student_id = h.id::text
            WHERE fp.settlement_id IS NOT NULL
              AND (s.preferredbranch::int = '$locationId'::int
                   OR m.basebranch::int      = '$locationId'::int
                   OR h.location_id          = '$locationId'::int)
        )";
    }

    // Apply date range filter (settled view)
    $dateFromEsc = pg_escape_string($con, $dateFrom);
    $dateToEsc   = pg_escape_string($con, $dateTo);
    $query .= " AND s.settlement_date BETWEEN '$dateFromEsc' AND '$dateToEsc'";

    $query .= " ORDER BY s.settlement_date DESC";

    $result = pg_query($con, $query);
    while ($row = pg_fetch_assoc($result)) {
        fputcsv($output, [
            $row['id'],
            date('d-M-Y', strtotime($row['settlement_date'])),
            $row['total_amount'],
            $row['cash_amount'],
            $row['online_amount'],
            $row['settled_by_name'],
            $row['location_name'] ?: 'N/A',
            $row['notes'] ?: 'N/A'
        ]);
    }
}

fclose($output);
exit;
