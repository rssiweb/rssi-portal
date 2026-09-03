<?php
require_once __DIR__ . "/../../bootstrap.php";

$status = $_GET['status'] ?? 'unsettled';
$settlementDate = $_GET['settlement_date'] ?? date('Y-m-d');
$location = $_GET['location'] ?? '';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=settlement_' . $status . '_' . $settlementDate . '.csv');

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

    $query = "SELECT p.id, p.collection_date, p.student_id, COALESCE(s.studentname, m.fullname, h.name) AS studentname, 
                 s.class, 
                 COALESCE(s.preferredbranch, m.basebranch) AS location,
                 p.month, p.academic_year, p.amount, p.payment_type, 
                 p.transaction_id, c.fullname as collector_name
          FROM fee_payments p
          LEFT JOIN rssimyprofile_student s ON p.student_id = s.student_id
          LEFT JOIN rssimyaccount_members m ON p.student_id = m.associatenumber
          LEFT JOIN public_health_records h ON p.student_id = h.id::text
          LEFT JOIN rssimyaccount_members c ON p.collected_by = c.associatenumber
          WHERE p.is_settled = FALSE";

    // Add location filter if selected
    if (!empty($location)) {
        // Get location name from ID
        $locationNameQuery = "SELECT name FROM office_locations WHERE id = '$location'";
        $locationNameResult = pg_query($con, $locationNameQuery);
        $locationNameRow = pg_fetch_assoc($locationNameResult);
        $locationName = $locationNameRow['name'];

        $query .= " AND (s.preferredbranch = '$locationName' OR m.basebranch = '$locationName')";
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
            $row['location'] ?: 'N/A'  // Added location data
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
                  SELECT DISTINCT settlement_id, STRING_AGG(DISTINCT preferredbranch, ', ') AS location_name
                  FROM (
                      SELECT sp.settlement_id, stu.preferredbranch
                      FROM settlement_payments sp
                      JOIN fee_payments fp ON sp.payment_id = fp.id
                      LEFT JOIN rssimyprofile_student stu ON fp.student_id = stu.student_id
                      WHERE stu.preferredbranch IS NOT NULL
                  ) loc_data
                  GROUP BY settlement_id
              ) sl ON s.id = sl.settlement_id
              WHERE 1=1";

    // Add location filter if selected
    if (!empty($location)) {
        $locationNameQuery = "SELECT name FROM office_locations WHERE id = '$location'";
        $locationNameResult = pg_query($con, $locationNameQuery);
        $locationNameRow = pg_fetch_assoc($locationNameResult);
        $locationName = $locationNameRow['name'];

        $query .= " AND s.id IN (
            SELECT DISTINCT sp.settlement_id
            FROM settlement_payments sp
            JOIN fee_payments fp ON sp.payment_id = fp.id
            LEFT JOIN rssimyprofile_student stu ON fp.student_id = stu.student_id
            WHERE stu.preferredbranch = '$locationName'
        )";
    }

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
