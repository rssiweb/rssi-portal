<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    header("Location: index.php");
    exit;
}

if (!isset($_GET['batch_id'])) {
    die('Batch ID not specified');
}

$batch_id = pg_escape_string($con, $_GET['batch_id']);

// Updated query: support both students and associates using COALESCE
$query = "
    SELECT 
        i.student_id,
        COALESCE(s.studentname, m.fullname) AS studentname,
        s.class,
        s.category,
        COALESCE(s.photourl, m.photo) AS photourl,
        m.position,
        i.order_type,
        i.payment_status,
        i.remarks,
        i.order_date,
        b.vendor_name,
        b.admin_remarks,
        i.status,
        a.fullname AS requested_by
    FROM id_card_orders i
    LEFT JOIN id_card_batches b ON i.batch_id = b.batch_id
    LEFT JOIN rssimyprofile_student s ON i.student_id = s.student_id
    LEFT JOIN rssimyaccount_members m ON i.student_id = m.associatenumber
    LEFT JOIN rssimyaccount_members a ON i.order_placed_by = a.associatenumber
    WHERE i.batch_id = '$batch_id'
    ORDER BY s.class NULLS LAST, s.category NULLS LAST, studentname, photourl
";

$result = pg_query($con, $query);
if (!$result) {
    die('Error fetching batch data');
}

// CSV output setup
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=ID_Card_Orders_' . $batch_id . '.csv');

$output = fopen('php://output', 'w');

// CSV Headers
fputcsv($output, [
    'Student ID',
    'Name',
    'Class',
    'Section',
    'Order Type',
    'Payment Status',
    'Remarks',
    'Order Date',
    'Status',
    'Vendor Name',
    'Admin Remarks',
    'Requested By',
    'Key'
]);

// CSV Rows
while ($row = pg_fetch_assoc($result)) {
    $source = !empty($row['class']) ? 'Student' : ($row['position'] ?? 'Unknown');

    fputcsv($output, [
        $row['student_id'],
        $row['studentname'],
        $row['class'],
        $row['category'],
        $row['order_type'],
        $row['payment_status'],
        $row['remarks'],
        $row['order_date'],
        $row['status'],
        $row['vendor_name'],
        $row['admin_remarks'],
        $row['requested_by'],
        $row['student_id'] . ',' . $row['studentname'] . ',' . $row['photourl'] . ',' . $source
    ]);
}

fclose($output);
exit;
