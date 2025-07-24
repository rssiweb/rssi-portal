<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    header("Location: index.php");
    exit;
}

if (!isset($_POST['batch_ids'])) {
    die("No batches selected for export");
}

$batch_ids = explode(',', $_POST['batch_ids']);
$batch_ids = array_map(function ($id) use ($con) {
    return pg_escape_string($con, $id);
}, $batch_ids);
$batch_ids_str = "'" . implode("','", $batch_ids) . "'";

// Get batch header information
$batch_query = "SELECT 
    b.batch_id,
    b.created_date,
    b.status,
    b.vendor_name,
    b.admin_remarks,
    b.ordered_date,
    MAX(o.delivered_date) AS delivered_date,
    MAX(o.delivered_remarks) AS delivered_remarks,
    COUNT(o.id) as item_count,
    MIN(o.academic_year) as academic_year,
    MIN(o.order_date) as start_date,
    MAX(o.order_date) as end_date
FROM id_card_batches b
LEFT JOIN id_card_orders o ON b.batch_id = o.batch_id
WHERE b.batch_id IN ($batch_ids_str)
GROUP BY b.batch_id, b.created_date, b.status, b.vendor_name, 
         b.admin_remarks, b.ordered_date
ORDER BY start_date DESC";

$batch_result = pg_query($con, $batch_query);
$batches = pg_fetch_all($batch_result) ?: [];

// Get all orders in selected batches
$orders_query = "SELECT 
    o.batch_id,
    o.student_id,
    COALESCE(s.studentname, m.fullname) as student_name,
    s.class,
    o.order_type,
    o.payment_status,
    o.remarks,
    o.order_date,
    o.status,
    u.fullname as order_placed_by_name,
    (SELECT COUNT(*) FROM id_card_orders WHERE student_id = o.student_id AND status = 'Delivered') as times_issued,
    (SELECT MAX(order_date) FROM id_card_orders WHERE student_id = o.student_id AND status = 'Delivered') as last_issued
FROM id_card_orders o
LEFT JOIN rssimyprofile_student s ON o.student_id = s.student_id
LEFT JOIN rssimyaccount_members m ON o.student_id = m.associatenumber
JOIN rssimyaccount_members u ON o.order_placed_by = u.associatenumber
WHERE o.batch_id IN ($batch_ids_str)
ORDER BY o.batch_id, o.order_date DESC";

$orders_result = pg_query($con, $orders_query);
$orders = pg_fetch_all($orders_result) ?: [];

// Set headers for download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=id_card_batches_export_' . date('Ymd_His') . '.csv');

// Create output file pointer
$output = fopen('php://output', 'w');

// Write headers for batches summary
fputcsv($output, [
    'Batch ID',
    'Status',
    'Created Date',
    'Academic Year',
    'Start Date',
    'End Date',
    'Item Count',
    'Vendor',
    'Admin Remarks',
    'Ordered Date',
    'Delivered Date',
    'Delivery Remarks'
]);

// Write batch data
foreach ($batches as $batch) {
    fputcsv($output, [
        $batch['batch_id'],
        $batch['status'],
        $batch['created_date'],
        $batch['academic_year'],
        $batch['start_date'],
        $batch['end_date'],
        $batch['item_count'],
        $batch['vendor_name'] ?? '',
        $batch['admin_remarks'] ?? '',
        $batch['ordered_date'] ?? '',
        $batch['delivered_date'] ?? '',
        $batch['delivered_remarks'] ?? ''
    ]);
}

// Add separator
fputcsv($output, []);
fputcsv($output, ['ORDER DETAILS']);
fputcsv($output, []);

// Write headers for order details
fputcsv($output, [
    'Batch ID',
    'Student ID',
    'Student Name',
    'Class',
    'Order Type',
    'Payment Status',
    'Remarks',
    'Order Date',
    'Status',
    'Requested By',
    'Times Issued',
    'Last Issued Date'
]);

// Write order data
foreach ($orders as $order) {
    fputcsv($output, [
        $order['batch_id'],
        $order['student_id'],
        $order['student_name'],
        $order['class'] ?? '',
        $order['order_type'],
        $order['payment_status'] ?? '',
        $order['remarks'] ?? '',
        $order['order_date'],
        $order['status'],
        $order['order_placed_by_name'],
        $order['times_issued'],
        $order['last_issued'] ?? ''
    ]);
}

fclose($output);
exit;
