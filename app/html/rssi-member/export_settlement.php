<?php
require_once __DIR__ . "/../../bootstrap.php";

$status = $_GET['status'] ?? 'unsettled';
$settlementDate = $_GET['settlement_date'] ?? date('Y-m-d');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=settlement_' . $status . '_' . $settlementDate . '.csv');

$output = fopen('php://output', 'w');

if ($status === 'unsettled') {
    // Export unsettled payments
    fputcsv($output, [
        'Payment ID', 'Date', 'Student ID', 'Student Name', 'Class', 
        'Month', 'Year', 'Amount', 'Type', 'Transaction ID', 'Collector'
    ]);
    
    $query = "SELECT p.id, p.collection_date, p.student_id, s.studentname, s.class, 
                     p.month, p.academic_year, p.amount, p.payment_type, 
                     p.transaction_id, m.fullname as collector_name
              FROM fee_payments p
              JOIN rssimyprofile_student s ON p.student_id = s.student_id
              JOIN rssimyaccount_members m ON p.collected_by = m.associatenumber
              WHERE p.is_settled = FALSE
              ORDER BY p.collection_date";
    
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
            $row['collector_name']
        ]);
    }
} else {
    // Export settled payments
    fputcsv($output, [
        'Settlement ID', 'Date', 'Total Amount', 'Cash Amount', 
        'Online Amount', 'Settled By', 'Notes'
    ]);
    
    $query = "SELECT s.id, s.settlement_date, s.total_amount, s.cash_amount, 
                     s.online_amount, m.fullname as settled_by_name, s.notes
              FROM settlements s
              JOIN rssimyaccount_members m ON s.settled_by = m.associatenumber
              ORDER BY s.settlement_date DESC";
    
    $result = pg_query($con, $query);
    while ($row = pg_fetch_assoc($result)) {
        fputcsv($output, [
            $row['id'],
            date('d-M-Y', strtotime($row['settlement_date'])),
            $row['total_amount'],
            $row['cash_amount'],
            $row['online_amount'],
            $row['settled_by_name'],
            $row['notes'] ?: 'N/A'
        ]);
    }
}

fclose($output);
exit;