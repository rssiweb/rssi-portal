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

// Get batch data
$query = "SELECT 
             s.student_id, s.studentname, s.class, s.category, s.photourl, 
             i.order_type, i.payment_status, i.remarks, i.order_date,
             i.vendor_name, i.admin_remarks, i.status,
             a.fullname as requested_by
          FROM id_card_orders i
          JOIN rssimyprofile_student s ON i.student_id = s.student_id
          LEFT JOIN rssimyaccount_members a ON i.order_placed_by = a.associatenumber
          WHERE i.batch_id = '$batch_id'
          ORDER BY s.class, s.category, s.studentname,s.photourl";

$result = pg_query($con, $query);
if (!$result) {
    die('Error fetching batch data');
}

// Set headers for download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=ID_Card_Orders_' . $batch_id . '.csv');

$output = fopen('php://output', 'w');

// Write CSV headers
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

// Write data rows
while ($row = pg_fetch_assoc($result)) {
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
        $row['student_id'] . ',' . $row['studentname'] . ',' . $row['photourl'] . ',Student' // Key for identification
    ]);
}

fclose($output);
exit;
